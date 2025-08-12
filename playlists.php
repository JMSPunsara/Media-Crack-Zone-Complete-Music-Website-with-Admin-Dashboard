<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'My Playlists';
$currentPage = 'playlists';

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_playlist'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        if (empty($name)) {
            $error_message = "Playlist name is required.";
        } elseif (strlen($name) > 100) {
            $error_message = "Playlist name must be 100 characters or less.";
        } else {
            // Check if playlist name already exists for this user
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE user_id = ? AND name = ?");
            $stmt->execute([$_SESSION['user_id'], $name]);
            
            if ($stmt->fetch()) {
                $error_message = "A playlist with this name already exists.";
            } else {
                // Create new playlist
                $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name, description, is_public, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$_SESSION['user_id'], $name, $description, $is_public])) {
                    $success_message = "Playlist '$name' created successfully!";
                } else {
                    $error_message = "Failed to create playlist.";
                }
            }
        }
    }
    
    if (isset($_POST['delete_playlist'])) {
        $playlist_id = intval($_POST['playlist_id']);
        
        // Check if user owns this playlist
        $stmt = $pdo->prepare("SELECT name FROM playlists WHERE id = ? AND user_id = ?");
        $stmt->execute([$playlist_id, $_SESSION['user_id']]);
        $playlist = $stmt->fetch();
        
        if ($playlist) {
            // Delete playlist tracks first
            $stmt = $pdo->prepare("DELETE FROM playlist_tracks WHERE playlist_id = ?");
            $stmt->execute([$playlist_id]);
            
            // Delete playlist
            $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$playlist_id, $_SESSION['user_id']])) {
                $success_message = "Playlist '{$playlist['name']}' deleted successfully!";
            } else {
                $error_message = "Failed to delete playlist.";
            }
        } else {
            $error_message = "Playlist not found or you don't have permission to delete it.";
        }
    }
    
    if (isset($_POST['update_playlist'])) {
        $playlist_id = intval($_POST['playlist_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        if (empty($name)) {
            $error_message = "Playlist name is required.";
        } else {
            // Check if user owns this playlist
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                // Check if new name conflicts with existing playlist (excluding current one)
                $stmt = $pdo->prepare("SELECT id FROM playlists WHERE user_id = ? AND name = ? AND id != ?");
                $stmt->execute([$_SESSION['user_id'], $name, $playlist_id]);
                
                if ($stmt->fetch()) {
                    $error_message = "A playlist with this name already exists.";
                } else {
                    // Update playlist
                    $stmt = $pdo->prepare("UPDATE playlists SET name = ?, description = ?, is_public = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                    if ($stmt->execute([$name, $description, $is_public, $playlist_id, $_SESSION['user_id']])) {
                        $success_message = "Playlist updated successfully!";
                    } else {
                        $error_message = "Failed to update playlist.";
                    }
                }
            } else {
                $error_message = "Playlist not found or you don't have permission to edit it.";
            }
        }
    }
    
    if (isset($_POST['remove_track'])) {
        $playlist_id = intval($_POST['playlist_id']);
        $track_id = intval($_POST['track_id']);
        
        // Check if user owns this playlist
        $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
        $stmt->execute([$playlist_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Remove track from playlist
            $stmt = $pdo->prepare("DELETE FROM playlist_tracks WHERE playlist_id = ? AND track_id = ?");
            if ($stmt->execute([$playlist_id, $track_id])) {
                // Reorder remaining tracks
                $stmt = $pdo->prepare("
                    UPDATE playlist_tracks 
                    SET position = position - 1 
                    WHERE playlist_id = ? AND position > (
                        SELECT COALESCE(MAX(position), 0) 
                        FROM (SELECT position FROM playlist_tracks WHERE playlist_id = ? AND track_id = ?) t
                    )
                ");
                $stmt->execute([$playlist_id, $playlist_id, $track_id]);
                
                $success_message = "Track removed from playlist successfully!";
            } else {
                $error_message = "Failed to remove track from playlist.";
            }
        } else {
            $error_message = "Playlist not found or you don't have permission to edit it.";
        }
    }
}

// Get user's playlists
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(pt.track_id) as track_count,
               COALESCE(SUM(t.duration), 0) as total_duration
        FROM playlists p
        LEFT JOIN playlist_tracks pt ON p.id = pt.playlist_id
        LEFT JOIN tracks t ON pt.track_id = t.id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $playlists = $stmt->fetchAll();
    
    // Get favorite tracks count for quick add
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites_count = $stmt->fetchColumn();
    
    // Get recently played tracks for quick add
    $stmt = $pdo->prepare("
        SELECT t.*, COUNT(*) as play_count
        FROM play_history ph
        JOIN tracks t ON ph.track_id = t.id
        WHERE ph.user_id = ?
        AND ph.played_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY t.id
        ORDER BY play_count DESC, ph.played_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_tracks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $playlists = [];
    $favorites_count = 0;
    $recent_tracks = [];
    $error_message = "Failed to load playlists.";
}

include 'includes/header.php';
?>

<div class="container-fluid playlists-page">
    <div class="row">
        <!-- Page Header -->
        <div class="col-12">
            <div class="page-header">
                <div class="container">
                    <div class="row align-items-center py-4">
                        <div class="col-md-8">
                            <h1 class="page-title">
                                <i class="fas fa-list-music me-3"></i>My Playlists
                            </h1>
                            <p class="page-subtitle">Create and manage your music collections</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="header-actions">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlaylistModal">
                                    <i class="fas fa-plus me-2"></i>Create Playlist
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Playlists Grid -->
            <div class="col-lg-9">
                <?php if (empty($playlists)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="text-center py-5">
                            <i class="fas fa-list-music empty-icon"></i>
                            <h3 class="mt-4 mb-3">No playlists yet</h3>
                            <p class="text-muted mb-4">Start organizing your music by creating your first playlist</p>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createPlaylistModal">
                                <i class="fas fa-plus me-2"></i>Create Your First Playlist
                            </button>
                        </div>
                        
                        <?php if ($favorites_count > 0): ?>
                        <div class="quick-actions mt-5">
                            <h4 class="text-center mb-4">Quick Start</h4>
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="quick-action-card">
                                        <i class="fas fa-heart"></i>
                                        <h5>Create from Favorites</h5>
                                        <p>Turn your <?php echo $favorites_count; ?> favorite tracks into a playlist</p>
                                        <button class="btn btn-outline-primary" onclick="createFromFavorites()">
                                            Create Playlist
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Playlists Grid -->
                    <div class="playlists-grid">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="playlist-card" data-playlist-id="<?php echo $playlist['id']; ?>">
                                <div class="playlist-cover">
                                    <div class="cover-placeholder">
                                        <i class="fas fa-music"></i>
                                    </div>
                                    <div class="playlist-overlay">
                                        <button class="play-btn" onclick="playPlaylist(<?php echo $playlist['id']; ?>)">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="playlist-info">
                                    <h4 class="playlist-name">
                                        <a href="playlist.php?id=<?php echo $playlist['id']; ?>">
                                            <?php echo htmlspecialchars($playlist['name']); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if ($playlist['description']): ?>
                                        <p class="playlist-description">
                                            <?php echo htmlspecialchars($playlist['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="playlist-meta">
                                        <span class="track-count">
                                            <i class="fas fa-music me-1"></i>
                                            <?php echo number_format($playlist['track_count']); ?> 
                                            <?php echo $playlist['track_count'] == 1 ? 'track' : 'tracks'; ?>
                                        </span>
                                        
                                        <?php if ($playlist['total_duration'] > 0): ?>
                                            <span class="duration">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo formatDuration($playlist['total_duration']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($playlist['is_public']): ?>
                                            <span class="visibility">
                                                <i class="fas fa-globe me-1"></i>
                                                Public
                                            </span>
                                        <?php else: ?>
                                            <span class="visibility">
                                                <i class="fas fa-lock me-1"></i>
                                                Private
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="playlist-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editPlaylist(<?php echo htmlspecialchars(json_encode($playlist)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeletePlaylist(<?php echo $playlist['id']; ?>, '<?php echo htmlspecialchars($playlist['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="playlist.php?id=<?php echo $playlist['id']; ?>">
                                                    <i class="fas fa-eye me-2"></i>View Playlist
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="sharePlaylist(<?php echo $playlist['id']; ?>)">
                                                    <i class="fas fa-share me-2"></i>Share
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="duplicatePlaylist(<?php echo $playlist['id']; ?>)">
                                                    <i class="fas fa-copy me-2"></i>Duplicate
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" onclick="exportPlaylist(<?php echo $playlist['id']; ?>)">
                                                    <i class="fas fa-download me-2"></i>Export
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="playlist-footer">
                                        <small class="text-muted">
                                            Updated <?php echo date('M j, Y', strtotime($playlist['updated_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Quick Stats -->
                <div class="sidebar-card">
                    <h5><i class="fas fa-chart-bar me-2"></i>Your Library</h5>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo count($playlists); ?></span>
                            <span class="stat-label">Playlists</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($favorites_count); ?></span>
                            <span class="stat-label">Favorites</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo array_sum(array_column($playlists, 'track_count')); ?></span>
                            <span class="stat-label">Total Tracks</span>
                        </div>
                    </div>
                </div>

                <!-- Recently Played -->
                <?php if (!empty($recent_tracks)): ?>
                <div class="sidebar-card">
                    <h5><i class="fas fa-history me-2"></i>Recently Played</h5>
                    <div class="recent-tracks">
                        <?php foreach (array_slice($recent_tracks, 0, 5) as $track): ?>
                            <div class="recent-track">
                                <div class="track-info">
                                    <div class="track-title"><?php echo htmlspecialchars($track['title']); ?></div>
                                    <div class="track-artist"><?php echo htmlspecialchars($track['artist']); ?></div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="showAddToPlaylistModal(<?php echo $track['id']; ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="quick-actions-list">
                        <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createPlaylistModal">
                            <i class="fas fa-plus me-2"></i>New Playlist
                        </button>
                        <?php if ($favorites_count > 0): ?>
                        <button class="btn btn-outline-secondary w-100 mb-2" onclick="createFromFavorites()">
                            <i class="fas fa-heart me-2"></i>From Favorites
                        </button>
                        <?php endif; ?>
                        <a href="browse.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-search me-2"></i>Discover Music
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Playlist Modal -->
<div class="modal fade" id="createPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="playlistModalTitle">Create New Playlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="playlistForm">
                <div class="modal-body">
                    <input type="hidden" name="playlist_id" id="playlistId" value="">
                    
                    <div class="mb-3">
                        <label for="playlistName" class="form-label">Playlist Name *</label>
                        <input type="text" class="form-control" id="playlistName" name="name" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="playlistDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="playlistDescription" name="description" rows="3" maxlength="500"></textarea>
                        <div class="form-text">Optional description for your playlist</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="playlistPublic" name="is_public">
                        <label class="form-check-label" for="playlistPublic">
                            Make this playlist public
                        </label>
                        <div class="form-text">Public playlists can be discovered by other users</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_playlist" id="playlistSubmitBtn" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Playlist
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add to Playlist Modal -->
<div class="modal fade" id="addToPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add to Playlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedTrackId" value="">
                
                <div class="playlist-selection">
                    <?php foreach ($playlists as $playlist): ?>
                        <div class="playlist-option" onclick="addTrackToPlaylist(<?php echo $playlist['id']; ?>)">
                            <div class="playlist-icon">
                                <i class="fas fa-music"></i>
                            </div>
                            <div class="playlist-details">
                                <div class="playlist-name"><?php echo htmlspecialchars($playlist['name']); ?></div>
                                <div class="playlist-count"><?php echo $playlist['track_count']; ?> tracks</div>
                            </div>
                            <div class="playlist-action">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($playlists)): ?>
                        <div class="text-center py-3">
                            <p class="text-muted">No playlists available</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlaylistModal">
                                Create Playlist
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Playlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deletePlaylistName"></span>"?</p>
                <p class="text-muted">This action cannot be undone. All tracks in this playlist will be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="playlist_id" id="deletePlaylistId" value="">
                    <button type="submit" name="delete_playlist" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Playlist
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Playlists Page Styles -->
<style>
.playlists-page {
    min-height: 100vh;
    background: var(--background-main);
}

.page-header {
    background: linear-gradient(135deg, var(--background-card) 0%, var(--background-hover) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 0;
}

.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.page-subtitle {
    color: var(--text-light);
    margin: 0;
    font-size: 1.1rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 0;
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-light);
    opacity: 0.5;
}

.empty-state h3 {
    color: var(--text-white);
}

.quick-action-card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.quick-action-card:hover {
    background: var(--background-hover);
    transform: translateY(-2px);
}

.quick-action-card i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.quick-action-card h5 {
    color: var(--text-white);
    margin-bottom: 1rem;
}

/* Playlists Grid */
.playlists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.playlist-card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.playlist-card:hover {
    background: var(--background-hover);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.playlist-cover {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    display: flex;
    align-items: center;
    justify-content: center;
}

.cover-placeholder {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.8);
}

.playlist-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.playlist-card:hover .playlist-overlay {
    opacity: 1;
}

.play-btn {
    background: var(--primary-color);
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
    color: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

.play-btn:hover {
    background: var(--accent-color);
    transform: scale(1.1);
}

.playlist-info {
    padding: 1.5rem;
}

.playlist-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.playlist-name a {
    color: var(--text-white);
    text-decoration: none;
    transition: color 0.3s ease;
}

.playlist-name a:hover {
    color: var(--primary-color);
}

.playlist-description {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.playlist-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: var(--text-light);
}

.playlist-actions {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.playlist-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1rem;
}

/* Sidebar */
.sidebar-card {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.sidebar-card h5 {
    color: var(--text-white);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--background-hover);
    border-radius: 8px;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9rem;
}

.recent-tracks {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.recent-track {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--background-hover);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.recent-track:hover {
    background: rgba(29, 185, 84, 0.1);
}

.track-info {
    flex: 1;
}

.track-title {
    color: var(--text-white);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.track-artist {
    color: var(--text-light);
    font-size: 0.8rem;
}

.quick-actions-list .btn {
    border-radius: 8px;
    transition: all 0.3s ease;
}

.quick-actions-list .btn:hover {
    transform: translateY(-2px);
}

/* Modal Styles */
.modal-content {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header, .modal-footer {
    border-color: rgba(255, 255, 255, 0.1);
}

.modal-title {
    color: var(--text-white);
}

.modal-body {
    color: var(--text-white);
}

.form-label {
    color: var(--text-white);
    font-weight: 500;
}

.form-control, .form-select {
    background: var(--background-hover);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-white);
    border-radius: 8px;
}

.form-control:focus, .form-select:focus {
    background: var(--background-hover);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
    color: var(--text-white);
}

.form-check-input {
    background-color: var(--background-hover);
    border-color: rgba(255, 255, 255, 0.3);
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-label {
    color: var(--text-white);
}

.playlist-selection {
    max-height: 400px;
    overflow-y: auto;
}

.playlist-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--background-hover);
    border-radius: 8px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.playlist-option:hover {
    background: rgba(29, 185, 84, 0.1);
    border-color: var(--primary-color);
}

.playlist-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.playlist-details {
    flex: 1;
}

.playlist-option .playlist-name {
    color: var(--text-white);
    font-weight: 500;
    margin: 0;
}

.playlist-count {
    color: var(--text-light);
    font-size: 0.85rem;
}

.playlist-action {
    color: var(--primary-color);
    font-size: 1.2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .playlists-grid {
        grid-template-columns: 1fr;
    }
    
    .playlist-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .playlist-actions {
        justify-content: center;
    }
    
    .header-actions {
        text-align: center;
        margin-top: 1rem;
    }
}

/* Animations */
.playlist-card {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Button styles */
.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dropdown-menu {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.dropdown-item {
    color: var(--text-white);
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: var(--background-hover);
    color: var(--primary-color);
}
</style>

<!-- Playlists JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        });
    }, 5000);
});

// Edit playlist function
function editPlaylist(playlist) {
    document.getElementById('playlistModalTitle').textContent = 'Edit Playlist';
    document.getElementById('playlistId').value = playlist.id;
    document.getElementById('playlistName').value = playlist.name;
    document.getElementById('playlistDescription').value = playlist.description || '';
    document.getElementById('playlistPublic').checked = playlist.is_public == 1;
    document.getElementById('playlistSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Playlist';
    document.getElementById('playlistSubmitBtn').name = 'update_playlist';
    
    const modal = new bootstrap.Modal(document.getElementById('createPlaylistModal'));
    modal.show();
}

// Reset form when modal is hidden
document.getElementById('createPlaylistModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('playlistModalTitle').textContent = 'Create New Playlist';
    document.getElementById('playlistId').value = '';
    document.getElementById('playlistName').value = '';
    document.getElementById('playlistDescription').value = '';
    document.getElementById('playlistPublic').checked = false;
    document.getElementById('playlistSubmitBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Create Playlist';
    document.getElementById('playlistSubmitBtn').name = 'create_playlist';
});

// Delete playlist confirmation
function confirmDeletePlaylist(playlistId, playlistName) {
    document.getElementById('deletePlaylistId').value = playlistId;
    document.getElementById('deletePlaylistName').textContent = playlistName;
    
    const modal = new bootstrap.Modal(document.getElementById('deletePlaylistModal'));
    modal.show();
}

// Show add to playlist modal
function showAddToPlaylistModal(trackId) {
    document.getElementById('selectedTrackId').value = trackId;
    const modal = new bootstrap.Modal(document.getElementById('addToPlaylistModal'));
    modal.show();
}

// Add track to playlist
function addTrackToPlaylist(playlistId) {
    const trackId = document.getElementById('selectedTrackId').value;
    
    if (!trackId) {
        alert('No track selected');
        return;
    }
    
    // Make AJAX request to add track to playlist
    fetch('api/add_to_playlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            playlist_id: playlistId,
            track_id: trackId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and show success message
            bootstrap.Modal.getInstance(document.getElementById('addToPlaylistModal')).hide();
            showNotification('Track added to playlist successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to add track to playlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Create playlist from favorites
function createFromFavorites() {
    const playlistName = prompt('Enter a name for your favorites playlist:');
    
    if (!playlistName) {
        return;
    }
    
    // Make AJAX request to create playlist from favorites
    fetch('api/create_from_favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: playlistName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Playlist created from favorites!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Failed to create playlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Play playlist
function playPlaylist(playlistId) {
    if (typeof window.playPlaylistGlobal === 'function') {
        window.playPlaylistGlobal(playlistId);
    } else {
        // Redirect to playlist page
        window.location.href = `playlist.php?id=${playlistId}&play=1`;
    }
}

// Share playlist
function sharePlaylist(playlistId) {
    const url = `${window.location.origin}/playlist.php?id=${playlistId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Check out this playlist!',
            url: url
        });
    } else {
        // Fallback to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Playlist link copied to clipboard!', 'success');
        });
    }
}

// Duplicate playlist
function duplicatePlaylist(playlistId) {
    const newName = prompt('Enter a name for the duplicated playlist:');
    
    if (!newName) {
        return;
    }
    
    fetch('api/duplicate_playlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            playlist_id: playlistId,
            name: newName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Playlist duplicated successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Failed to duplicate playlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Export playlist
function exportPlaylist(playlistId) {
    window.open(`api/export_playlist.php?id=${playlistId}`, '_blank');
}

// Show notification
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.container');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>
