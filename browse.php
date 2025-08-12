<?php
require_once 'config.php';

// Get filter parameters
$language_filter = isset($_GET['language']) ? (int)$_GET['language'] : 0;
$mood_filter = isset($_GET['mood']) ? (int)$_GET['mood'] : 0;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'popular';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Get all languages and moods for filter references
$stmt = $pdo->query("SELECT * FROM languages ORDER BY name");
$languages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM moods ORDER BY name");
$moods = $stmt->fetchAll();

// Set SEO for browse page
$filters = [];
if (!empty($language_filter)) {
    $langData = array_filter($languages, function($lang) use ($language_filter) {
        return $lang['id'] == $language_filter;
    });
    if (!empty($langData)) {
        $filters['language'] = reset($langData)['name'];
    }
}
if (!empty($mood_filter)) {
    $moodData = array_filter($moods, function($mood) use ($mood_filter) {
        return $mood['id'] == $mood_filter;
    });
    if (!empty($moodData)) {
        $filters['mood'] = reset($moodData)['name'];
    }
}
if (!empty($search_query)) {
    $filters['search'] = $search_query;
}

$browseSEO = MusicSEO::generateBrowseSEO($filters);
setSEO($browseSEO['title'], $browseSEO['description'], $browseSEO['keywords'], $browseSEO['image']);

$pageTitle = $browseSEO['title'];
include 'includes/header.php';

// Build query
$where_conditions = [];
$params = [];

if ($language_filter) {
    $where_conditions[] = "t.language_id = ?";
    $params[] = $language_filter;
}

if ($mood_filter) {
    $where_conditions[] = "t.mood_id = ?";
    $params[] = $mood_filter;
}

if ($search_query) {
    $where_conditions[] = "(t.title LIKE ? OR t.artist LIKE ? OR t.album LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Sort options
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'newest':
        $order_clause .= "t.created_at DESC";
        break;
    case 'oldest':
        $order_clause .= "t.created_at ASC";
        break;
    case 'alphabetical':
        $order_clause .= "t.title ASC";
        break;
    case 'artist':
        $order_clause .= "t.artist ASC";
        break;
    default: // popular
        $order_clause .= "t.plays_count DESC, t.created_at DESC";
}

// Get tracks
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    $where_clause
    $order_clause
");
$stmt->execute($params);
$tracks = $stmt->fetchAll();
?>

<div class="row">
    <!-- Filters Sidebar -->
    <div class="col-lg-3 mb-4">
        <div class="card-custom p-4 sticky-top" style="top: 100px;">
            <h5 class="mb-4">
                <i class="fas fa-filter me-2"></i>Filters
            </h5>
            
            <form method="GET" action="" id="filterForm">
                <!-- Search -->
                <div class="mb-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search tracks, artists...">
                </div>
                
                <!-- Language Filter -->
                <div class="mb-4">
                    <label class="form-label">Language</label>
                    <select class="form-control" name="language" onchange="this.form.submit()">
                        <option value="">All Languages</option>
                        <?php foreach ($languages as $language): ?>
                        <option value="<?php echo $language['id']; ?>" 
                                <?php echo $language_filter == $language['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Mood Filter -->
                <div class="mb-4">
                    <label class="form-label">Mood</label>
                    <select class="form-control" name="mood" onchange="this.form.submit()">
                        <option value="">All Moods</option>
                        <?php foreach ($moods as $mood): ?>
                        <option value="<?php echo $mood['id']; ?>" 
                                <?php echo $mood_filter == $mood['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mood['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="mb-4">
                    <label class="form-label">Sort By</label>
                    <select class="form-control" name="sort" onchange="this.form.submit()">
                        <option value="popular" <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="alphabetical" <?php echo $sort_by == 'alphabetical' ? 'selected' : ''; ?>>A-Z</option>
                        <option value="artist" <?php echo $sort_by == 'artist' ? 'selected' : ''; ?>>By Artist</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                
                <a href="browse.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-undo me-2"></i>Clear Filters
                </a>
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-lg-9">
        <!-- Results Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <?php if ($search_query): ?>
                        Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php elseif ($language_filter || $mood_filter): ?>
                        Filtered Music
                    <?php else: ?>
                        All Music
                    <?php endif; ?>
                </h2>
                <p class="text-white-75 mb-0"><?php echo count($tracks); ?> tracks found</p>
            </div>
            
            <?php if (!empty($tracks)): ?>
            <button class="btn btn-success" onclick="playAllTracks()" type="button">
                <i class="fas fa-play me-2"></i>Play All
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Active Filters -->
        <?php if ($language_filter || $mood_filter || $search_query): ?>
        <div class="mb-4">
            <h6 class="mb-2">Active Filters:</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($search_query): ?>
                <span class="badge bg-primary fs-6 p-2">
                    Search: "<?php echo htmlspecialchars($search_query); ?>"
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="text-white ms-2">×</a>
                </span>
                <?php endif; ?>
                
                <?php if ($language_filter): ?>
                <?php 
                $lang_name = '';
                foreach ($languages as $lang) {
                    if ($lang['id'] == $language_filter) {
                        $lang_name = $lang['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-success fs-6 p-2">
                    Language: <?php echo $lang_name; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['language' => ''])); ?>" class="text-white ms-2">×</a>
                </span>
                <?php endif; ?>
                
                <?php if ($mood_filter): ?>
                <?php 
                $mood_name = '';
                foreach ($moods as $mood) {
                    if ($mood['id'] == $mood_filter) {
                        $mood_name = $mood['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-warning fs-6 p-2">
                    Mood: <?php echo $mood_name; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['mood' => ''])); ?>" class="text-white ms-2">×</a>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Track Results -->
        <?php if (empty($tracks)): ?>
        <div class="text-center py-5">
            <i class="fas fa-music text-white-75" style="font-size: 5rem;"></i>
            <h4 class="mt-3 mb-2 text-white">No tracks found</h4>
            <p class="text-white-75">Try adjusting your filters or search terms.</p>
            <a href="browse.php" class="btn btn-primary">Browse All Music</a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($tracks as $index => $track): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card-custom h-100" data-track-id="<?php echo $track['id']; ?>">
                    <div class="position-relative">
                        <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                             alt="<?php echo htmlspecialchars($track['title']); ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: cover;">
                        <div class="position-absolute top-50 start-50 translate-middle opacity-0 hover-show">
                            <button class="play-btn" onclick="playTrackFromBrowse(<?php echo $index; ?>)" type="button">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="card-title mb-2 text-white"><?php echo htmlspecialchars($track['title']); ?></h6>
                        <p class="card-text small text-white-75 mb-2"><?php echo htmlspecialchars($track['artist']); ?></p>
                        
                        <?php if ($track['album']): ?>
                        <p class="small text-white-75 mb-2"><?php echo htmlspecialchars($track['album']); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <?php if ($track['language_name']): ?>
                                <span class="badge bg-secondary small me-1"><?php echo $track['language_name']; ?></span>
                                <?php endif; ?>
                                <?php if ($track['mood_name']): ?>
                                <span class="badge small" style="background-color: <?php echo $track['mood_color']; ?>">
                                    <?php echo $track['mood_name']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <small class="text-white-75">
                                <i class="fas fa-play me-1"></i><?php echo number_format($track['plays_count']); ?>
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-outline-primary btn-sm" onclick="playTrackFromBrowse(<?php echo $index; ?>)" type="button">
                                <i class="fas fa-play me-1"></i>Play
                            </button>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-outline-secondary btn-sm favorite-btn" onclick="addToFavorites(<?php echo $track['id']; ?>)" type="button">
                                <i class="far fa-heart"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-show {
    transition: opacity 0.3s ease;
}

.card-custom:hover .hover-show {
    opacity: 1 !important;
}

.sticky-top {
    position: sticky;
}
</style>

<script>
// Track data for player
const browseTracks = <?php echo json_encode($tracks); ?>;

console.log('🎵 Browse page loaded with', browseTracks.length, 'tracks');

// Prepare track data for player with proper URLs
browseTracks.forEach(track => {
    // Clean the file path and ensure proper URL structure
    if (track.file_path && !track.file_path.startsWith('http')) {
        // Remove any existing path prefixes
        const cleanPath = track.file_path.replace(/^(uploads\/music\/|music\/|\/)/g, '');
        track.file_path = cleanPath; // Keep it clean, let the player handle URL construction
    }
    
    // Ensure cover image URL is properly set
    if (track.cover_image && !track.cover_image.startsWith('http')) {
        track.cover_image = '<?php echo UPLOAD_URL; ?>covers/' + track.cover_image;
    }
    
    console.log('Prepared track:', track.title, 'with file:', track.file_path);
});

function playTrackFromBrowse(index) {
    console.log('🎵 Playing track from browse:', index, browseTracks[index]);
    
    if (!browseTracks[index]) {
        console.error('❌ Track not found at index:', index);
        if (typeof showToast === 'function') {
            showToast('Track not found', 'error');
        }
        return;
    }
    
    // Check if global functions exist
    if (typeof window.setPlaylist === 'function') {
        console.log('✅ Using setPlaylist function');
        window.setPlaylist(browseTracks, index);
    } else if (typeof window.playTrackGlobal === 'function') {
        console.log('✅ Using playTrackGlobal function');
        window.playTrackGlobal(browseTracks[index]);
    } else {
        console.error('❌ No player functions available');
        alert('Music player not loaded. Please refresh the page.');
    }
}

function playAllTracks() {
    console.log('🎵 Playing all tracks from browse page');
    
    if (browseTracks.length === 0) {
        console.warn('❌ No tracks available');
        if (typeof showToast === 'function') {
            showToast('No tracks available to play', 'warning');
        }
        return;
    }
    
    if (typeof window.setPlaylist === 'function') {
        console.log('✅ Setting playlist with', browseTracks.length, 'tracks');
        window.setPlaylist(browseTracks, 0);
    } else {
        console.error('❌ setPlaylist function not available');
        alert('Music player not loaded. Please refresh the page.');
    }
}

// Wait for DOM and player to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 Browse page DOM loaded');
    
    // Wait a bit for the music player to initialize
    setTimeout(function() {
        // Check if player functions are available
        const playerReady = typeof window.setPlaylist === 'function' || typeof window.playTrackGlobal === 'function';
        console.log('🎵 Music player ready:', playerReady);
        
        if (!playerReady) {
            console.warn('⚠️ Music player functions not available, retrying...');
            
            // Retry after a longer delay
            setTimeout(function() {
                const retryReady = typeof window.setPlaylist === 'function' || typeof window.playTrackGlobal === 'function';
                if (!retryReady) {
                    console.error('❌ Music player failed to load');
                }
            }, 3000);
        }
    }, 1000);
    
    // Auto-submit search form with delay
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
});

// Add debugging for click events
document.addEventListener('click', function(e) {
    if (e.target.closest('.play-btn') || e.target.closest('button[onclick*="playTrackFromBrowse"]')) {
        console.log('🎵 Play button clicked');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
