<?php
require_once 'config.php';

// Get filter parameters
$language_filter = isset($_GET['language']) ? (int)$_GET['language'] : 0;
$mood_filter = isset($_GET['mood']) ? (int)$_GET['mood'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'popular';

// Build query conditions
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.artist LIKE ? OR t.album LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($language_filter) {
    $where_conditions[] = "t.language_id = ?";
    $params[] = $language_filter;
}

if ($mood_filter) {
    $where_conditions[] = "t.mood_id = ?";
    $params[] = $mood_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Determine sort order
$order_clause = match($sort) {
    'newest' => 'ORDER BY t.created_at DESC',
    'oldest' => 'ORDER BY t.created_at ASC',
    'alphabetical' => 'ORDER BY t.title ASC',
    'artist' => 'ORDER BY t.artist ASC',
    'most_played' => 'ORDER BY t.plays_count DESC',
    default => 'ORDER BY t.plays_count DESC, t.created_at DESC'
};

// Get tracks
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image"
           . (isLoggedIn() ? ",
           CASE WHEN f.track_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite" : ",
           0 as is_favorite") . "
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    " . (isLoggedIn() ? "LEFT JOIN favorites f ON t.id = f.track_id AND f.user_id = " . $_SESSION['user_id'] : "") . "
    $where_clause
    $order_clause
    LIMIT 100
");
$stmt->execute($params);
$tracks = $stmt->fetchAll();

// Get languages and moods for filters
$stmt = $pdo->query("SELECT * FROM languages ORDER BY name");
$languages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM moods ORDER BY name");
$moods = $stmt->fetchAll();

$pageTitle = 'Enhanced Music Player';
include 'includes/header.php';
?>

<!-- Enhanced Music Player Interface -->
<div class="enhanced-player-container">
    <!-- Filter Controls -->
    <div class="player-filters mb-4">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label text-white">
                    <i class="fas fa-search me-2"></i>Search Music
                </label>
                <input type="text" class="form-control search-input" id="musicSearch" 
                       placeholder="Search tracks, artists..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-white">
                    <i class="fas fa-language me-2"></i>Language
                </label>
                <select class="form-control filter-select" id="languageFilter">
                    <option value="">All Languages</option>
                    <?php foreach ($languages as $language): ?>
                    <option value="<?php echo $language['id']; ?>" <?php echo $language_filter == $language['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($language['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-white">
                    <i class="fas fa-heart me-2"></i>Mood
                </label>
                <select class="form-control filter-select" id="moodFilter">
                    <option value="">All Moods</option>
                    <?php foreach ($moods as $mood): ?>
                    <option value="<?php echo $mood['id']; ?>" <?php echo $mood_filter == $mood['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mood['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label text-white">
                    <i class="fas fa-sort me-2"></i>Sort By
                </label>
                <select class="form-control filter-select" id="sortFilter">
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="alphabetical" <?php echo $sort == 'alphabetical' ? 'selected' : ''; ?>>A-Z</option>
                    <option value="artist" <?php echo $sort == 'artist' ? 'selected' : ''; ?>>By Artist</option>
                    <option value="most_played" <?php echo $sort == 'most_played' ? 'selected' : ''; ?>>Most Played</option>
                </select>
            </div>
        </div>
        
        <!-- Quick Filter Buttons -->
        <div class="quick-filters">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-music"></i> All Music
            </button>
            <?php if (isLoggedIn()): ?>
            <button class="filter-btn" data-filter="favorites">
                <i class="fas fa-heart"></i> My Favorites
            </button>
            <button class="filter-btn" data-filter="recent">
                <i class="fas fa-clock"></i> Recently Played
            </button>
            <?php endif; ?>
            <button class="filter-btn" data-filter="trending">
                <i class="fas fa-fire"></i> Trending
            </button>
        </div>
    </div>

    <!-- Player Controls Section -->
    <div class="player-controls-section mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="player-actions">
                    <button class="btn btn-success me-2" id="playAllBtn">
                        <i class="fas fa-play me-2"></i>Play All
                    </button>
                    <button class="btn btn-outline-primary me-2" id="shuffleAllBtn">
                        <i class="fas fa-random me-2"></i>Shuffle All
                    </button>
                    <?php if (isLoggedIn()): ?>
                    <button class="btn btn-outline-warning me-2" id="addToPlaylistBtn">
                        <i class="fas fa-plus me-2"></i>Add to Playlist
                    </button>
                    <button class="btn btn-outline-danger" id="addAllFavoritesBtn">
                        <i class="fas fa-heart me-2"></i>Add All to Favorites
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="results-info">
                    <span class="text-white">
                        <i class="fas fa-music me-2"></i>
                        <span id="resultsCount"><?php echo count($tracks); ?></span> tracks found
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Music Grid -->
    <div class="music-grid" id="musicGrid">
        <?php foreach ($tracks as $index => $track): ?>
        <div class="music-card" data-track-id="<?php echo $track['id']; ?>" data-index="<?php echo $index; ?>">
            <div class="music-card-cover">
                <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                     alt="<?php echo htmlspecialchars($track['title']); ?>">
                <div class="music-card-overlay">
                    <button class="play-btn-large" onclick="playTrackFromGrid(<?php echo $index; ?>)">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
                <div class="play-count">
                    <i class="fas fa-play"></i>
                    <span><?php echo number_format($track['plays_count']); ?></span>
                </div>
            </div>
            
            <div class="music-card-info">
                <h6 class="track-title"><?php echo htmlspecialchars($track['title']); ?></h6>
                <p class="track-artist"><?php echo htmlspecialchars($track['artist']); ?></p>
                
                <?php if ($track['album']): ?>
                <p class="track-album"><?php echo htmlspecialchars($track['album']); ?></p>
                <?php endif; ?>
                
                <div class="track-tags">
                    <?php if ($track['language_name']): ?>
                    <span class="tag lang-tag"><?php echo $track['language_name']; ?></span>
                    <?php endif; ?>
                    <?php if ($track['mood_name']): ?>
                    <span class="tag mood-tag" style="background-color: <?php echo $track['mood_color']; ?>20; color: <?php echo $track['mood_color']; ?>;">
                        <?php echo $track['mood_name']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="music-card-actions">
                <?php if (isLoggedIn()): ?>
                <button class="action-btn favorite-btn <?php echo $track['is_favorite'] ? 'favorited' : ''; ?>" 
                        onclick="toggleFavorite(<?php echo $track['id']; ?>, this)">
                    <i class="<?php echo $track['is_favorite'] ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
                <button class="action-btn playlist-btn" onclick="addToPlaylistModal(<?php echo $track['id']; ?>)">
                    <i class="fas fa-plus"></i>
                </button>
                <?php endif; ?>
                <button class="action-btn download-btn" onclick="downloadTrack(<?php echo $track['id']; ?>)">
                    <i class="fas fa-download"></i>
                </button>
                <button class="action-btn share-btn" onclick="shareTrack(<?php echo $track['id']; ?>)">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Loading indicator -->
    <div class="loading-indicator text-center py-5" id="loadingIndicator" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-white">Loading music...</p>
    </div>

    <!-- Empty state -->
    <div class="empty-state text-center py-5" id="emptyState" style="display: none;">
        <i class="fas fa-music-slash" style="font-size: 5rem; opacity: 0.3;"></i>
        <h4 class="mt-3 text-white">No tracks found</h4>
        <p class="text-muted">Try adjusting your filters or search terms.</p>
    </div>
</div>

<!-- Add to Playlist Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="addToPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Add to Playlist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-white">Select Playlist</label>
                    <select class="form-control" id="playlistSelect">
                        <option value="">Loading playlists...</option>
                    </select>
                </div>
                <div class="text-center">
                    <button class="btn btn-primary" onclick="createNewPlaylist()">
                        <i class="fas fa-plus me-2"></i>Create New Playlist
                    </button>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addToSelectedPlaylist()">Add to Playlist</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Enhanced Player Styles */
.enhanced-player-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem;
    min-height: 100vh;
}

.player-filters {
    background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 25px;
    padding: 2.5rem;
    margin-bottom: 3rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}

.player-filters::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.search-input, .filter-select {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    font-size: 0.95rem;
}

.search-input:focus, .filter-select:focus {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.04));
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.3rem rgba(29, 185, 84, 0.15), 0 8px 25px rgba(0, 0, 0, 0.2);
    color: white;
    transform: translateY(-2px);
}

.filter-select option {
    background: var(--background-dark);
    color: white;
}

.quick-filters {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 2rem;
    justify-content: center;
}

.filter-btn {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    border-radius: 30px;
    padding: 0.75rem 2rem;
    transition: all 0.4s ease;
    cursor: pointer;
    backdrop-filter: blur(10px);
    font-weight: 600;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.filter-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 50%;
    transition: all 0.4s ease;
    transform: translate(-50%, -50%);
    z-index: -1;
}

.filter-btn:hover::before, .filter-btn.active::before {
    width: 300px;
    height: 300px;
}

.filter-btn:hover, .filter-btn.active {
    color: white;
    border-color: var(--primary-color);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(29, 185, 84, 0.4);
}

.player-controls-section {
    background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 3rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.music-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    width: 100%;
    margin: 0;
    padding: 0;
    justify-items: stretch;
    align-content: start;
}

.music-card {
    background: linear-gradient(145deg, var(--background-card), rgba(255, 255, 255, 0.02));
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    cursor: pointer;
    width: 100%;
    display: flex;
    flex-direction: column;
    height: auto;
    position: relative;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.music-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(29, 185, 84, 0.2);
    border-color: var(--primary-color);
}

.music-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(29, 185, 84, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    z-index: 1;
}

.music-card:hover::before {
    opacity: 1;
}

.music-card-cover {
    position: relative;
    overflow: hidden;
    border-radius: 20px 20px 0 0;
}

.music-card-cover img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: transform 0.4s ease;
    filter: brightness(0.9);
}

.music-card:hover .music-card-cover img {
    transform: scale(1.08);
    filter: brightness(1.1);
}

.music-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0, 0, 0, 0.8) 0%, rgba(29, 185, 84, 0.3) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.4s ease;
    backdrop-filter: blur(5px);
    z-index: 2;
}

.music-card:hover .music-card-overlay {
    opacity: 1;
}

.play-btn-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s ease;
    box-shadow: 0 8px 32px rgba(29, 185, 84, 0.6);
    position: relative;
    overflow: hidden;
}

.play-btn-large::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transition: all 0.4s ease;
    transform: translate(-50%, -50%);
}

.play-btn-large:hover {
    transform: scale(1.15);
    box-shadow: 0 12px 40px rgba(29, 185, 84, 0.8);
}

.play-btn-large:hover::before {
    width: 100%;
    height: 100%;
}

.play-btn-large i {
    position: relative;
    z-index: 2;
    margin-left: 4px;
}

.play-count {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.9), rgba(29, 185, 84, 0.3));
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 600;
    z-index: 3;
}

.music-card-info {
    padding: 2rem 1.5rem 1.5rem;
    background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.05) 100%);
    position: relative;
    z-index: 2;
}

.track-title {
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: white;
    font-size: 1.2rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.track-artist {
    color: var(--text-light);
    margin-bottom: 0.75rem;
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.9;
}

.track-album {
    color: var(--text-light);
    margin-bottom: 1.25rem;
    font-size: 0.9rem;
    opacity: 0.7;
    font-style: italic;
}

.track-tags {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.tag {
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: all 0.3s ease;
}

.lang-tag {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.mood-tag {
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

.music-card-actions {
    padding: 0 1.5rem 2rem;
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    align-items: center;
}

.action-btn {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s ease;
    cursor: pointer;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    transition: all 0.4s ease;
    transform: translate(-50%, -50%);
}

.action-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.action-btn:hover::before {
    width: 100%;
    height: 100%;
}

.action-btn:hover {
    background: var(--primary-color);
    border-color: var(--primary-color);
    box-shadow: 0 8px 25px rgba(29, 185, 84, 0.4);
}

.favorite-btn.favorited {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    border-color: #e74c3c;
    color: white;
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
}

.download-btn:hover {
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-color: #3498db;
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
}

.share-btn:hover {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    border-color: #9b59b6;
    box-shadow: 0 8px 25px rgba(155, 89, 182, 0.4);
}

.results-info {
    background: rgba(255, 255, 255, 0.05);
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Responsive Design */
@media (max-width: 1400px) {
    .music-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
}

@media (max-width: 1200px) {
    .music-grid {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
}

@media (max-width: 768px) {
    .enhanced-player-container {
        padding: 1.5rem;
    }
    
    .music-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1.5rem;
    }
    
    .quick-filters {
        justify-content: center;
        gap: 1rem;
    }
    
    .filter-btn {
        padding: 0.6rem 1.5rem;
        font-size: 0.9rem;
    }
    
    .player-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .player-actions .btn {
        width: 100%;
        margin-bottom: 0;
    }
    
    .player-filters {
        padding: 2rem 1.5rem;
    }
    
    .music-card-cover img {
        height: 200px;
    }
    
    .play-btn-large {
        width: 70px;
        height: 70px;
        font-size: 1.6rem;
    }
}

@media (max-width: 480px) {
    .enhanced-player-container {
        padding: 1rem;
    }
    
    .music-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .player-filters .row > div {
        margin-bottom: 1.5rem;
    }
    
    .quick-filters {
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
    
    .filter-btn {
        width: 200px;
        text-align: center;
    }
    
    .music-card-info {
        padding: 1.5rem 1rem 1rem;
    }
    
    .music-card-actions {
        padding: 0 1rem 1.5rem;
        gap: 1rem;
    }
    
    .action-btn {
        width: 50px;
        height: 50px;
    }
}

/* Loading and Empty States */
.loading-indicator {
    background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 20px;
    margin: 2rem 0;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.empty-state {
    background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 20px;
    margin: 2rem 0;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Enhanced scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
}

/* Loading Animations */
.loading-spinner {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
}

.spinner-ring {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 64px;
    height: 64px;
    margin: 8px;
    border: 6px solid transparent;
    border-radius: 50%;
    animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-top-color: var(--primary-color);
}

.spinner-ring:nth-child(1) {
    animation-delay: -0.45s;
    border-top-color: var(--primary-color);
}

.spinner-ring:nth-child(2) {
    animation-delay: -0.3s;
    border-top-color: var(--accent-color);
}

.spinner-ring:nth-child(3) {
    animation-delay: -0.15s;
    border-top-color: #ff6b9d;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Smooth fade-in animation for cards */
.music-card {
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;
    transform: translateY(30px);
}

.music-card:nth-child(odd) {
    animation-delay: 0.1s;
}

.music-card:nth-child(even) {
    animation-delay: 0.2s;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced focus states for accessibility */
.filter-btn:focus,
.action-btn:focus,
.play-btn-large:focus {
    outline: 2px solid var(--accent-color);
    outline-offset: 2px;
}

/* Selection highlight */
::selection {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
}
</style>

<script>
// Enhanced Player JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const musicGrid = document.getElementById('musicGrid');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const emptyState = document.getElementById('emptyState');
    const resultsCount = document.getElementById('resultsCount');
    
    // Store all tracks for playlist management
    let allTracks = <?php echo json_encode($tracks); ?>;
    let filteredTracks = [...allTracks];
    
    console.log('Enhanced Player Debug:');
    console.log('Total tracks loaded:', allTracks.length);
    console.log('Sample track:', allTracks[0] || 'No tracks');
    
    // Debug filteredTracks
    console.log('Filtered tracks initialized:', filteredTracks.length);
    
    // Utility function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Prepare track data for player
    allTracks.forEach(track => {
        track.file_path = '<?php echo UPLOAD_URL; ?>music/' + track.file_path;
        track.cover_image = '<?php echo UPLOAD_URL; ?>covers/' + track.cover_image;
    });
    
    // Filter functionality
    const filters = {
        language: document.getElementById('languageFilter'),
        mood: document.getElementById('moodFilter'),
        sort: document.getElementById('sortFilter'),
        search: document.getElementById('musicSearch')
    };
    
    // Add event listeners for filters
    Object.values(filters).forEach(filter => {
        filter.addEventListener('change', applyFilters);
    });
    
    // Debounced search
    let searchTimeout;
    filters.search.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });
    
    // Quick filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filterType = this.dataset.filter;
            applyQuickFilter(filterType);
        });
    });
    
    // Player action buttons
    document.getElementById('playAllBtn').addEventListener('click', () => {
        if (filteredTracks.length > 0) {
            window.setPlaylist(filteredTracks, 0);
        }
    });
    
    document.getElementById('shuffleAllBtn').addEventListener('click', () => {
        if (filteredTracks.length > 0) {
            const shuffled = [...filteredTracks].sort(() => Math.random() - 0.5);
            window.setPlaylist(shuffled, 0);
        }
    });
    
    <?php if (isLoggedIn()): ?>
    document.getElementById('addAllFavoritesBtn').addEventListener('click', addAllToFavorites);
    <?php endif; ?>
    
    function applyFilters() {
        showLoading();
        
        // Get filter values
        const language = filters.language.value;
        const mood = filters.mood.value;
        const sort = filters.sort.value;
        const search = filters.search.value.toLowerCase().trim();
        
        // Filter tracks
        filteredTracks = allTracks.filter(track => {
            const matchesLanguage = !language || track.language_id == language;
            const matchesMood = !mood || track.mood_id == mood;
            const matchesSearch = !search || 
                track.title.toLowerCase().includes(search) ||
                track.artist.toLowerCase().includes(search) ||
                (track.album && track.album.toLowerCase().includes(search));
            
            return matchesLanguage && matchesMood && matchesSearch;
        });
        
        // Sort tracks
        sortTracks(sort);
        
        // Update display
        setTimeout(() => {
            updateMusicGrid();
            hideLoading();
        }, 300);
        
        // Update URL without reload
        updateURL();
    }
    
    function applyQuickFilter(filterType) {
        showLoading();
        
        switch(filterType) {
            case 'all':
                filteredTracks = [...allTracks];
                break;
            case 'favorites':
                filteredTracks = allTracks.filter(track => track.is_favorite);
                break;
            case 'recent':
                // This would need recent play history from backend
                filteredTracks = [...allTracks].sort((a, b) => 
                    new Date(b.created_at) - new Date(a.created_at)
                ).slice(0, 50);
                break;
            case 'trending':
                filteredTracks = [...allTracks].sort((a, b) => 
                    b.plays_count - a.plays_count
                ).slice(0, 50);
                break;
        }
        
        setTimeout(() => {
            updateMusicGrid();
            hideLoading();
        }, 300);
    }
    
    function sortTracks(sortType) {
        switch(sortType) {
            case 'newest':
                filteredTracks.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case 'oldest':
                filteredTracks.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                break;
            case 'alphabetical':
                filteredTracks.sort((a, b) => a.title.localeCompare(b.title));
                break;
            case 'artist':
                filteredTracks.sort((a, b) => a.artist.localeCompare(b.artist));
                break;
            case 'most_played':
                filteredTracks.sort((a, b) => b.plays_count - a.plays_count);
                break;
            default: // popular
                filteredTracks.sort((a, b) => {
                    if (b.plays_count !== a.plays_count) {
                        return b.plays_count - a.plays_count;
                    }
                    return new Date(b.created_at) - new Date(a.created_at);
                });
        }
    }
    
    function updateMusicGrid() {
        if (filteredTracks.length === 0) {
            musicGrid.innerHTML = '';
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
            renderTracks();
        }
        
        resultsCount.textContent = filteredTracks.length;
    }
    
    function renderTracks() {
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        
        musicGrid.innerHTML = filteredTracks.map((track, index) => {
            const albumHtml = track.album ? `<p class="track-album">${escapeHtml(track.album)}</p>` : '';
            const languageTag = track.language_name ? `<span class="tag lang-tag">${escapeHtml(track.language_name)}</span>` : '';
            const moodTag = track.mood_name ? `<span class="tag mood-tag" style="background-color: ${track.mood_color}20; color: ${track.mood_color};">${escapeHtml(track.mood_name)}</span>` : '';
            
            const favoriteBtn = isLoggedIn ? `
                <button class="action-btn favorite-btn ${track.is_favorite ? 'favorited' : ''}" 
                        onclick="toggleFavorite(${track.id}, this)">
                    <i class="${track.is_favorite ? 'fas' : 'far'} fa-heart"></i>
                </button>
                <button class="action-btn playlist-btn" onclick="addToPlaylistModal(${track.id})">
                    <i class="fas fa-plus"></i>
                </button>` : '';
            
            return `
                <div class="music-card" data-track-id="${track.id}" data-index="${index}">
                    <div class="music-card-cover">
                        <img src="${track.cover_image}" alt="${escapeHtml(track.title)}">
                        <div class="music-card-overlay">
                            <button class="play-btn-large" onclick="playTrackFromGrid(${index})">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                        <div class="play-count">
                            <i class="fas fa-play"></i>
                            <span>${track.plays_count.toLocaleString()}</span>
                        </div>
                    </div>
                    
                    <div class="music-card-info">
                        <h6 class="track-title">${escapeHtml(track.title)}</h6>
                        <p class="track-artist">${escapeHtml(track.artist)}</p>
                        ${albumHtml}
                        
                        <div class="track-tags">
                            ${languageTag}
                            ${moodTag}
                        </div>
                    </div>
                    
                    <div class="music-card-actions">
                        ${favoriteBtn}
                        <button class="action-btn download-btn" onclick="downloadTrack(${track.id})">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="action-btn share-btn" onclick="shareTrack(${track.id})">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function showLoading() {
        loadingIndicator.style.display = 'block';
        musicGrid.style.opacity = '0.5';
    }
    
    function hideLoading() {
        loadingIndicator.style.display = 'none';
        musicGrid.style.opacity = '1';
    }
    
    function updateURL() {
        const params = new URLSearchParams();
        if (filters.language.value) params.set('language', filters.language.value);
        if (filters.mood.value) params.set('mood', filters.mood.value);
        if (filters.sort.value !== 'popular') params.set('sort', filters.sort.value);
        if (filters.search.value) params.set('search', filters.search.value);
        
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newURL);
    }
    
    // Global functions
    window.playTrackFromGrid = function(index) {
        console.log('playTrackFromGrid called with index:', index);
        console.log('filteredTracks length:', filteredTracks.length);
        console.log('Track to play:', filteredTracks[index]);
        
        if (filteredTracks && filteredTracks.length > index) {
            if (typeof window.setPlaylist === 'function') {
                window.setPlaylist(filteredTracks, index);
            } else {
                console.error('window.setPlaylist function not found!');
                showToast('Player not initialized', 'error');
            }
        } else {
            console.error('Invalid track index or no tracks available');
            showToast('Track not available', 'error');
        }
    };
    
    window.toggleFavorite = function(trackId, button) {
        <?php if (isLoggedIn()): ?>
        fetch('<?php echo SITE_URL; ?>/api/toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ track_id: trackId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.toggle('favorited', data.is_favorite);
                const icon = button.querySelector('i');
                icon.classList.toggle('fas', data.is_favorite);
                icon.classList.toggle('far', !data.is_favorite);
                
                // Update track data
                const track = allTracks.find(t => t.id == trackId);
                if (track) track.is_favorite = data.is_favorite;
                
                // Show feedback
                const message = data.is_favorite ? 'Added to favorites' : 'Removed from favorites';
                const type = data.is_favorite ? 'success' : 'info';
                showToast(message, type);
            } else {
                showToast('Error updating favorites', 'error');
            }
        })
        .catch(error => {
            console.error('Error toggling favorite:', error);
            showToast('Error updating favorites', 'error');
        });
        <?php else: ?>
        showToast('Please login to add favorites', 'warning');
        <?php endif; ?>
    };
    
    window.downloadTrack = function(trackId) {
        // Use the download API endpoint
        const downloadUrl = '<?php echo SITE_URL; ?>/api/download.php?track_id=' + trackId;
        
        // Create a temporary link and trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.style.display = 'none';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show download notification
        if (typeof showToast === 'function') {
            showToast('Download started!', 'success');
        } else {
            console.log('Download started!');
        }
    };
    
    window.shareTrack = function(trackId) {
        const track = allTracks.find(t => t.id == trackId);
        if (!track) {
            showToast('Track not found', 'error');
            return;
        }
        
        const shareData = {
            title: track.title,
            text: `Listen to "${track.title}" by ${track.artist}`,
            url: window.location.origin + '<?php echo SITE_URL; ?>/track.php?id=' + trackId
        };
        
        if (navigator.share) {
            navigator.share(shareData).catch(err => {
                console.log('Error sharing:', err);
                fallbackShare(shareData.url);
            });
        } else {
            fallbackShare(shareData.url);
        }
    };
    
    function fallbackShare(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Track link copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Unable to copy link', 'error');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast('Track link copied to clipboard!', 'success');
            } catch (err) {
                showToast('Unable to copy link', 'error');
            }
            document.body.removeChild(textArea);
        }
    }
    
    <?php if (isLoggedIn()): ?>
    function addAllToFavorites() {
        if (filteredTracks.length === 0) {
            showToast('No tracks to add to favorites', 'warning');
            return;
        }
        
        if (confirm(`Add all ${filteredTracks.length} tracks to favorites?`)) {
            const trackIds = filteredTracks.map(t => t.id);
            
            fetch('<?php echo SITE_URL; ?>/api/bulk_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ track_ids: trackIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`${data.added_count} tracks added to favorites!`, 'success');
                    // Update UI
                    filteredTracks.forEach(track => {
                        track.is_favorite = true;
                        const trackInAll = allTracks.find(t => t.id === track.id);
                        if (trackInAll) trackInAll.is_favorite = true;
                    });
                    updateMusicGrid();
                } else {
                    showToast('Error adding tracks to favorites', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding to favorites:', error);
                showToast('Error adding tracks to favorites', 'error');
            });
        }
    }
    <?php endif; ?>
    
    // Playlist modal functions
    <?php if (isLoggedIn()): ?>
    window.addToPlaylistModal = function(trackId) {
        // Store the track ID for later use
        window.currentTrackForPlaylist = trackId;
        
        // Load user playlists (you can implement this later)
        const playlistSelect = document.getElementById('playlistSelect');
        if (playlistSelect) {
            playlistSelect.innerHTML = '<option value="">Select a playlist...</option>';
            // Add code here to load user playlists from API
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('addToPlaylistModal'));
        modal.show();
    };
    
    window.createNewPlaylist = function() {
        const playlistName = prompt('Enter playlist name:');
        if (playlistName && playlistName.trim()) {
            // Add code here to create new playlist via API
            showToast('Playlist creation feature coming soon!', 'info');
        }
    };
    
    window.addToSelectedPlaylist = function() {
        const playlistSelect = document.getElementById('playlistSelect');
        const selectedPlaylist = playlistSelect.value;
        
        if (!selectedPlaylist) {
            showToast('Please select a playlist', 'warning');
            return;
        }
        
        // Add code here to add track to selected playlist via API
        showToast('Add to playlist feature coming soon!', 'info');
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addToPlaylistModal'));
        modal.hide();
    };
    <?php endif; ?>
});

// Toast notification function
function showToast(message, type = 'info') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;

    // Add to body
    document.body.appendChild(toast);

    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastIcon(type) {
    switch(type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
