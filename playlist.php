<?php
require_once 'config.php';

// Check if playlist ID is provided
if (!isset($_GET['id'])) {
    redirect('playlists.php');
}

$playlist_id = intval($_GET['id']);

// Get playlist information
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username as creator_name,
               COUNT(pt.track_id) as track_count,
               COALESCE(SUM(t.duration), 0) as total_duration
        FROM playlists p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN playlist_tracks pt ON p.id = pt.playlist_id
        LEFT JOIN tracks t ON pt.track_id = t.id
        WHERE p.id = ? AND (p.is_public = 1 OR p.user_id = ?)
        GROUP BY p.id
    ");
    $stmt->execute([$playlist_id, $_SESSION['user_id'] ?? 0]);
    $playlist = $stmt->fetch();
    
    if (!$playlist) {
        redirect('playlists.php');
    }
    
    // Get playlist tracks
    $stmt = $pdo->prepare("
        SELECT t.*, pt.position, pt.id as playlist_track_id,
               f.id as is_favorite
        FROM playlist_tracks pt
        JOIN tracks t ON pt.track_id = t.id
        LEFT JOIN favorites f ON t.id = f.track_id AND f.user_id = ?
        WHERE pt.playlist_id = ?
        ORDER BY pt.position
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 0, $playlist_id]);
    $tracks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Playlist view error: " . $e->getMessage());
    redirect('playlists.php');
}

$pageTitle = $playlist['name'];
$currentPage = 'playlists';
$isOwner = isLoggedIn() && $playlist['user_id'] == $_SESSION['user_id'];

// Handle auto-play
$autoPlay = isset($_GET['play']);

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submissions (only for owners)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isOwner) {
    if (isset($_POST['reorder_tracks'])) {
        $track_order = json_decode($_POST['track_order'], true);
        
        if (is_array($track_order)) {
            try {
                $pdo->beginTransaction();
                
                $position = 1;
                foreach ($track_order as $track_id) {
                    $stmt = $pdo->prepare("UPDATE playlist_tracks SET position = ? WHERE playlist_id = ? AND track_id = ?");
                    $stmt->execute([$position, $playlist_id, intval($track_id)]);
                    $position++;
                }
                
                $pdo->commit();
                $success_message = "Track order updated successfully!";
                
                // Refresh tracks
                $stmt = $pdo->prepare("
                    SELECT t.*, pt.position, pt.id as playlist_track_id,
                           f.id as is_favorite
                    FROM playlist_tracks pt
                    JOIN tracks t ON pt.track_id = t.id
                    LEFT JOIN favorites f ON t.id = f.track_id AND f.user_id = ?
                    WHERE pt.playlist_id = ?
                    ORDER BY pt.position
                ");
                $stmt->execute([$_SESSION['user_id'], $playlist_id]);
                $tracks = $stmt->fetchAll();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Failed to update track order.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid playlist-view">
    <!-- Playlist Header -->
    <div class="playlist-header">
        <div class="container">
            <div class="row align-items-end py-5">
                <div class="col-md-3">
                    <div class="playlist-cover-large">
                        <div class="cover-placeholder">
                            <i class="fas fa-music"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="playlist-info-large">
                        <span class="playlist-type">Playlist</span>
                        <h1 class="playlist-title"><?php echo htmlspecialchars($playlist['name']); ?></h1>
                        
                        <?php if ($playlist['description']): ?>
                            <p class="playlist-description"><?php echo htmlspecialchars($playlist['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="playlist-metadata">
                            <span class="creator">
                                <strong><?php echo htmlspecialchars($playlist['creator_name']); ?></strong>
                            </span>
                            <span class="separator">•</span>
                            <span class="track-count">
                                <?php echo number_format($playlist['track_count']); ?> 
                                <?php echo $playlist['track_count'] == 1 ? 'song' : 'songs'; ?>
                            </span>
                            <?php if ($playlist['total_duration'] > 0): ?>
                                <span class="separator">•</span>
                                <span class="duration">
                                    <?php echo formatTotalDuration($playlist['total_duration']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="separator">•</span>
                            <span class="visibility">
                                <?php if ($playlist['is_public']): ?>
                                    <i class="fas fa-globe me-1"></i>Public
                                <?php else: ?>
                                    <i class="fas fa-lock me-1"></i>Private
                                <?php endif; ?>
                            </span>
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

        <!-- Playlist Controls -->
        <div class="playlist-controls mb-4">
            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($tracks)): ?>
                    <button class="btn btn-primary btn-lg play-all-btn" onclick="playAllTracks()">
                        <i class="fas fa-play me-2"></i>Play All
                    </button>
                    <button class="btn btn-outline-primary btn-lg shuffle-btn" onclick="shufflePlay()">
                        <i class="fas fa-random me-2"></i>Shuffle
                    </button>
                <?php endif; ?>
                
                <?php if ($isOwner): ?>
                    <button class="btn btn-outline-secondary" onclick="editPlaylistInfo()">
                        <i class="fas fa-edit me-2"></i>Edit Info
                    </button>
                    <button class="btn btn-outline-secondary" id="reorderToggle" onclick="toggleReorder()">
                        <i class="fas fa-arrows-alt me-2"></i>Reorder
                    </button>
                <?php endif; ?>
                
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h me-2"></i>More
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="sharePlaylist()">
                            <i class="fas fa-share me-2"></i>Share Playlist
                        </a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a class="dropdown-item" href="#" onclick="duplicatePlaylist()">
                            <i class="fas fa-copy me-2"></i>Duplicate Playlist
                        </a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="#" onclick="exportPlaylist()">
                            <i class="fas fa-download me-2"></i>Export Playlist
                        </a></li>
                        <?php if ($isOwner): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="deletePlaylist()">
                            <i class="fas fa-trash me-2"></i>Delete Playlist
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Track List -->
        <?php if (empty($tracks)): ?>
            <div class="empty-playlist">
                <div class="text-center py-5">
                    <i class="fas fa-music empty-icon"></i>
                    <h3 class="mt-4 mb-3">This playlist is empty</h3>
                    <?php if ($isOwner): ?>
                        <p class="text-muted mb-4">Start adding tracks to build your perfect playlist</p>
                        <a href="browse.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Browse Music
                        </a>
                    <?php else: ?>
                        <p class="text-muted">The creator hasn't added any tracks yet</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="track-list-container">
                <!-- Track List Header -->
                <div class="track-list-header">
                    <div class="row align-items-center">
                        <div class="col-1 text-center">
                            <span class="track-number-header">#</span>
                        </div>
                        <div class="col-5">
                            <span class="track-header">Title</span>
                        </div>
                        <div class="col-3">
                            <span class="track-header">Album</span>
                        </div>
                        <div class="col-2">
                            <span class="track-header">Duration</span>
                        </div>
                        <div class="col-1"></div>
                    </div>
                </div>

                <!-- Tracks -->
                <div class="track-list" id="trackList" <?php echo $isOwner ? 'data-sortable="true"' : ''; ?>>
                    <?php foreach ($tracks as $index => $track): ?>
                        <div class="track-item" 
                             data-track-id="<?php echo $track['id']; ?>"
                             data-position="<?php echo $track['position']; ?>">
                            <div class="row align-items-center">
                                <div class="col-1 text-center">
                                    <div class="track-number-container">
                                        <span class="track-number"><?php echo $index + 1; ?></span>
                                        <button class="play-track-btn" onclick="playTrack(<?php echo $track['id']; ?>, <?php echo $index; ?>)">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <?php if ($isOwner): ?>
                                            <div class="drag-handle">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="track-info">
                                        <div class="track-title"><?php echo htmlspecialchars($track['title']); ?></div>
                                        <div class="track-artist"><?php echo htmlspecialchars($track['artist']); ?></div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="track-album">
                                        <?php echo htmlspecialchars($track['album'] ?: 'Unknown Album'); ?>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="track-duration">
                                        <?php echo formatDuration($track['duration']); ?>
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="track-actions">
                                        <?php if (isLoggedIn()): ?>
                                            <button class="btn btn-sm btn-link favorite-btn <?php echo $track['is_favorite'] ? 'favorited' : ''; ?>"
                                                    onclick="toggleFavorite(<?php echo $track['id']; ?>, this)">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if (isLoggedIn()): ?>
                                                <li><a class="dropdown-item" href="#" onclick="addToQueue(<?php echo $track['id']; ?>)">
                                                    <i class="fas fa-plus me-2"></i>Add to Queue
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="addToPlaylist(<?php echo $track['id']; ?>)">
                                                    <i class="fas fa-list me-2"></i>Add to Playlist
                                                </a></li>
                                                <?php endif; ?>
                                                <li><a class="dropdown-item" href="#" onclick="shareTrack(<?php echo $track['id']; ?>)">
                                                    <i class="fas fa-share me-2"></i>Share Track
                                                </a></li>
                                                <?php if ($isOwner): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="removeFromPlaylist(<?php echo $track['id']; ?>, '<?php echo htmlspecialchars($track['title']); ?>')">
                                                    <i class="fas fa-trash me-2"></i>Remove from Playlist
                                                </a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($isOwner): ?>
                    <div class="reorder-controls" id="reorderControls" style="display: none;">
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" onclick="saveReorder()">
                                <i class="fas fa-save me-2"></i>Save Order
                            </button>
                            <button class="btn btn-secondary" onclick="cancelReorder()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden form for reordering -->
<?php if ($isOwner): ?>
<form method="POST" id="reorderForm" style="display: none;">
    <input type="hidden" name="reorder_tracks" value="1">
    <input type="hidden" name="track_order" id="trackOrder" value="">
</form>
<?php endif; ?>

<!-- Playlist View Styles -->
<style>
.playlist-view {
    min-height: 100vh;
    background: var(--background-main);
}

.playlist-header {
    background: linear-gradient(180deg, rgba(29, 185, 84, 0.3) 0%, var(--background-main) 100%);
    color: white;
}

.playlist-cover-large {
    width: 232px;
    height: 232px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
}

.playlist-cover-large .cover-placeholder {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.8);
}

.playlist-info-large .playlist-type {
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: bold;
    letter-spacing: 1px;
    color: var(--text-light);
}

.playlist-title {
    font-size: 3rem;
    font-weight: 900;
    margin: 0.5rem 0 1rem 0;
    line-height: 1.1;
}

.playlist-description {
    color: var(--text-light);
    font-size: 1rem;
    margin-bottom: 1rem;
}

.playlist-metadata {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-light);
}

.playlist-metadata .separator {
    margin: 0 0.25rem;
}

.playlist-controls {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 1.5rem;
}

.play-all-btn, .shuffle-btn {
    min-width: 140px;
    border-radius: 50px;
    font-weight: 600;
    padding: 0.75rem 2rem;
}

.play-all-btn {
    background: var(--primary-color);
    border: none;
}

.play-all-btn:hover {
    background: var(--accent-color);
    transform: scale(1.05);
}

/* Track List */
.track-list-container {
    margin-top: 2rem;
}

.track-list-header {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
}

.track-number-header, .track-header {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.track-item {
    padding: 0.75rem 0;
    border-radius: 4px;
    transition: all 0.2s ease;
    cursor: default;
}

.track-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.track-item.reorder-mode {
    cursor: move;
}

.track-item.dragging {
    opacity: 0.5;
    background: rgba(29, 185, 84, 0.2);
}

.track-number-container {
    position: relative;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.track-number {
    color: var(--text-light);
    font-size: 0.9rem;
    transition: opacity 0.2s ease;
}

.play-track-btn {
    position: absolute;
    top: 0;
    left: 0;
    width: 24px;
    height: 24px;
    background: var(--primary-color);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 0.7rem;
    opacity: 0;
    transition: opacity 0.2s ease;
    cursor: pointer;
}

.track-item:hover .track-number {
    opacity: 0;
}

.track-item:hover .play-track-btn {
    opacity: 1;
}

.drag-handle {
    position: absolute;
    top: 0;
    left: -20px;
    width: 16px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    opacity: 0;
    transition: opacity 0.2s ease;
    cursor: move;
}

.track-item.reorder-mode .drag-handle {
    opacity: 1;
}

.track-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.track-title {
    color: var(--text-white);
    font-weight: 500;
    font-size: 1rem;
}

.track-artist {
    color: var(--text-light);
    font-size: 0.9rem;
}

.track-album, .track-duration {
    color: var(--text-light);
    font-size: 0.9rem;
}

.track-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.track-item:hover .track-actions {
    opacity: 1;
}

.favorite-btn {
    color: var(--text-light);
    padding: 0.25rem;
    transition: all 0.2s ease;
}

.favorite-btn:hover, .favorite-btn.favorited {
    color: var(--primary-color);
}

.favorite-btn.favorited {
    animation: favoriteAnimation 0.3s ease;
}

@keyframes favoriteAnimation {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Empty State */
.empty-playlist {
    text-align: center;
    padding: 4rem 0;
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-light);
    opacity: 0.5;
}

/* Reorder Controls */
.reorder-controls {
    text-align: center;
    padding: 1.5rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* Dropdown Menus */
.dropdown-menu {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.dropdown-item {
    color: var(--text-white);
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: var(--background-hover);
    color: var(--primary-color);
}

.dropdown-item.text-danger:hover {
    color: #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
    .playlist-title {
        font-size: 2rem;
    }
    
    .playlist-cover-large {
        width: 150px;
        height: 150px;
    }
    
    .playlist-cover-large .cover-placeholder {
        font-size: 2.5rem;
    }
    
    .playlist-metadata {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .playlist-metadata .separator {
        display: none;
    }
    
    .track-list-header .col-3,
    .track-item .col-3 {
        display: none;
    }
    
    .track-list-header .col-5,
    .track-item .col-5 {
        flex: 0 0 auto;
        width: 60%;
    }
    
    .track-list-header .col-2,
    .track-item .col-2 {
        flex: 0 0 auto;
        width: 25%;
    }
}

/* Animation Classes */
.track-item {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- Playlist JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Playlist data
const playlistData = {
    id: <?php echo $playlist_id; ?>,
    name: <?php echo json_encode($playlist['name']); ?>,
    tracks: <?php echo json_encode($tracks); ?>,
    isOwner: <?php echo json_encode($isOwner); ?>
};

let sortableInstance = null;
let isReorderMode = false;

document.addEventListener('DOMContentLoaded', function() {
    // Auto-play if requested
    <?php if ($autoPlay && !empty($tracks)): ?>
    playAllTracks();
    <?php endif; ?>
    
    // Initialize sortable if owner
    if (playlistData.isOwner) {
        initializeSortable();
    }
});

// Play all tracks
function playAllTracks() {
    if (playlistData.tracks.length === 0) return;
    
    // Implement play all functionality
    console.log('Playing all tracks from playlist:', playlistData.name);
    // This would integrate with your music player
}

// Shuffle play
function shufflePlay() {
    if (playlistData.tracks.length === 0) return;
    
    console.log('Shuffle playing playlist:', playlistData.name);
    // This would implement shuffle functionality
}

// Play specific track
function playTrack(trackId, index) {
    console.log('Playing track:', trackId, 'at position:', index);
    // This would integrate with your music player
}

// Toggle favorite
function toggleFavorite(trackId, button) {
    if (!<?php echo json_encode(isLoggedIn()); ?>) {
        window.location.href = 'login.php';
        return;
    }
    
    fetch('api/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ track_id: trackId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('favorited');
            if (button.classList.contains('favorited')) {
                button.style.animation = 'favoriteAnimation 0.3s ease';
                setTimeout(() => {
                    button.style.animation = '';
                }, 300);
            }
        } else {
            showNotification('Failed to update favorite', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Reorder functionality
function toggleReorder() {
    isReorderMode = !isReorderMode;
    const trackList = document.getElementById('trackList');
    const reorderControls = document.getElementById('reorderControls');
    const reorderToggle = document.getElementById('reorderToggle');
    
    if (isReorderMode) {
        trackList.classList.add('reorder-mode');
        document.querySelectorAll('.track-item').forEach(item => {
            item.classList.add('reorder-mode');
        });
        reorderControls.style.display = 'block';
        reorderToggle.innerHTML = '<i class="fas fa-times me-2"></i>Cancel';
        sortableInstance.option('disabled', false);
    } else {
        trackList.classList.remove('reorder-mode');
        document.querySelectorAll('.track-item').forEach(item => {
            item.classList.remove('reorder-mode');
        });
        reorderControls.style.display = 'none';
        reorderToggle.innerHTML = '<i class="fas fa-arrows-alt me-2"></i>Reorder';
        sortableInstance.option('disabled', true);
    }
}

function initializeSortable() {
    const trackList = document.getElementById('trackList');
    
    sortableInstance = Sortable.create(trackList, {
        handle: '.drag-handle',
        disabled: true,
        ghostClass: 'dragging',
        onEnd: function(evt) {
            // Update track numbers
            updateTrackNumbers();
        }
    });
}

function updateTrackNumbers() {
    const trackItems = document.querySelectorAll('.track-item');
    trackItems.forEach((item, index) => {
        const trackNumber = item.querySelector('.track-number');
        trackNumber.textContent = index + 1;
    });
}

function saveReorder() {
    const trackItems = document.querySelectorAll('.track-item');
    const trackOrder = Array.from(trackItems).map(item => item.dataset.trackId);
    
    document.getElementById('trackOrder').value = JSON.stringify(trackOrder);
    document.getElementById('reorderForm').submit();
}

function cancelReorder() {
    location.reload();
}

// Remove from playlist
function removeFromPlaylist(trackId, trackTitle) {
    if (!confirm(`Remove "${trackTitle}" from this playlist?`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="remove_track" value="1">
        <input type="hidden" name="playlist_id" value="${playlistData.id}">
        <input type="hidden" name="track_id" value="${trackId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Share playlist
function sharePlaylist() {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: `Check out "${playlistData.name}" playlist!`,
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Playlist link copied to clipboard!', 'success');
        });
    }
}

// Duplicate playlist
function duplicatePlaylist() {
    if (!<?php echo json_encode(isLoggedIn()); ?>) {
        window.location.href = 'login.php';
        return;
    }
    
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
            playlist_id: playlistData.id,
            name: newName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Playlist duplicated successfully!', 'success');
            setTimeout(() => {
                window.location.href = `playlist.php?id=${data.playlist_id}`;
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
function exportPlaylist() {
    window.open(`api/export_playlist.php?id=${playlistData.id}`, '_blank');
}

// Delete playlist
function deletePlaylist() {
    if (!confirm(`Delete "${playlistData.name}" playlist? This action cannot be undone.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'playlists.php';
    form.innerHTML = `
        <input type="hidden" name="delete_playlist" value="1">
        <input type="hidden" name="playlist_id" value="${playlistData.id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Edit playlist info
function editPlaylistInfo() {
    window.location.href = `playlists.php#edit-${playlistData.id}`;
}

// Add to playlist/queue functions
function addToQueue(trackId) {
    showNotification('Added to queue!', 'success');
}

function addToPlaylist(trackId) {
    // This would open the add to playlist modal
    showNotification('Add to playlist functionality would open here', 'info');
}

function shareTrack(trackId) {
    showNotification('Track link copied!', 'success');
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
