<?php
require_once 'config.php';
require_once 'includes/seo.php';

// Set homepage SEO
setPageSEO(
    'Media Crack Zone - Free Music Downloads & Streaming | High Quality MP3',
    'Stream and download high-quality music for free. Explore unlimited MP3 songs across different moods and languages on Media Crack Zone.',
    'free music download, mp3 songs, music streaming, high quality music, free mp3, music platform, online music'
);

$pageTitle = 'Home';
include 'includes/header.php';

// Get featured tracks
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    ORDER BY t.plays_count DESC, t.created_at DESC 
    LIMIT 12
");
$stmt->execute();
$featured_tracks = $stmt->fetchAll();

// Get popular moods
$stmt = $pdo->prepare("
    SELECT m.*, COUNT(t.id) as track_count 
    FROM moods m 
    LEFT JOIN tracks t ON m.id = t.mood_id 
    GROUP BY m.id 
    ORDER BY track_count DESC 
    LIMIT 8
");
$stmt->execute();
$popular_moods = $stmt->fetchAll();

// Get popular languages
$stmt = $pdo->prepare("
    SELECT l.*, COUNT(t.id) as track_count 
    FROM languages l 
    LEFT JOIN tracks t ON l.id = t.language_id 
    GROUP BY l.id 
    ORDER BY track_count DESC 
    LIMIT 8
");
$stmt->execute();
$popular_languages = $stmt->fetchAll();

// Get recent uploads
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    ORDER BY t.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$recent_tracks = $stmt->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section-enhanced">
    <div class="hero-background">
        <div class="floating-elements">
            <div class="floating-note note-1"><i class="fas fa-music"></i></div>
            <div class="floating-note note-2"><i class="fas fa-headphones"></i></div>
            <div class="floating-note note-3"><i class="fas fa-compact-disc"></i></div>
            <div class="floating-note note-4"><i class="fas fa-heart"></i></div>
            <div class="floating-note note-5"><i class="fas fa-play"></i></div>
            <div class="floating-note note-6"><i class="fas fa-microphone"></i></div>
            <div class="floating-note note-7"><i class="fas fa-volume-up"></i></div>
            <div class="floating-note note-8"><i class="fas fa-star"></i></div>
        </div>
        <div class="hero-gradient"></div>
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </div>
    
    <div class="container hero-content">
        <div class="row justify-content-center align-items-center min-vh-75">
            <div class="col-lg-6 text-center text-lg-start">
                <div class="hero-badge mb-4">
                    <span class="badge-text">🎵 Premium Music Experience</span>
                </div>
                <h1 class="hero-title mb-4">
                    Welcome to <span class="brand-highlight">Media Crack Zone</span>
                </h1>
                <p class="hero-subtitle mb-5">
                    Discover unlimited music from around the world. Stream, create playlists, and immerse yourself in the ultimate audio experience with crystal-clear sound quality.
                </p>
                
                <div class="hero-features mb-4">
                    <div class="feature-item">
                        <i class="fas fa-infinity"></i>
                        <span>Unlimited Streaming</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-download"></i>
                        <span>Offline Downloads</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-heart"></i>
                        <span>Personal Favorites</span>
                    </div>
                </div>
                
                <?php if (!isLoggedIn()): ?>
                <div class="hero-actions">
                    <a href="register.php" class="btn btn-primary-enhanced btn-lg me-3">
                        <i class="fas fa-rocket me-2"></i>Get Started
                        <div class="btn-shine"></div>
                    </a>
                    <a href="browse.php" class="btn btn-outline-enhanced btn-lg">
                        <i class="fas fa-compass me-2"></i>Browse Music
                    </a>
                </div>
                <?php else: ?>
                <div class="hero-actions">
                    <a href="enhanced_player.php" class="btn btn-primary-enhanced btn-lg me-3">
                        <i class="fas fa-search me-2"></i>Enhanced Player
                        <div class="btn-shine"></div>
                    </a>
                    <a href="favorites.php" class="btn btn-outline-enhanced btn-lg">
                        <i class="fas fa-heart me-2"></i>My Favorites
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="hero-stats mt-5">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" data-target="<?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM tracks");
                                    echo $stmt->fetchColumn();
                                ?>">0</div>
                                <div class="stat-label">Tracks</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" data-target="<?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
                                    echo $stmt->fetchColumn();
                                ?>">0</div>
                                <div class="stat-label">Users</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <div class="stat-number" data-target="<?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM languages");
                                    echo $stmt->fetchColumn();
                                ?>">0</div>
                                <div class="stat-label">Languages</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-social mt-4">
                    <p class="social-text">Join our community</p>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 text-center">
                <div class="hero-visual">
                    <div class="music-player-mockup">
                        <div class="player-screen">
                            <div class="now-playing">
                                <div class="track-art">
                                    <img src="https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=200&h=200&fit=crop" alt="Now Playing">
                                    <div class="play-pulse"></div>
                                </div>
                                <div class="track-info">
                                    <div class="track-name">Amazing Track</div>
                                    <div class="artist-name">Featured Artist</div>
                                </div>
                            </div>
                            <div class="player-controls">
                                <i class="fas fa-step-backward"></i>
                                <i class="fas fa-play"></i>
                                <i class="fas fa-step-forward"></i>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <div class="volume-control">
                                <i class="fas fa-volume-up"></i>
                                <div class="volume-bar">
                                    <div class="volume-fill"></div>
                                </div>
                            </div>
                        </div>
                        <div class="player-equalizer">
                            <div class="eq-bar"></div>
                            <div class="eq-bar"></div>
                            <div class="eq-bar"></div>
                            <div class="eq-bar"></div>
                            <div class="eq-bar"></div>
                        </div>
                    </div>
                    <div class="floating-cards">
                        <div class="music-card card-1" style="--rotation: 15deg;">
                            <i class="fas fa-music"></i>
                            <span>Rock</span>
                        </div>
                        <div class="music-card card-2" style="--rotation: -10deg;">
                            <i class="fas fa-heart"></i>
                            <span>Pop</span>
                        </div>
                        <div class="music-card card-3" style="--rotation: 20deg;">
                            <i class="fas fa-guitar"></i>
                            <span>Jazz</span>
                        </div>
                        <div class="music-card card-4" style="--rotation: -15deg;">
                            <i class="fas fa-drum"></i>
                            <span>Hip Hop</span>
                        </div>
                    </div>
                    <div class="hero-audio-waves">
                        <div class="wave wave-1"></div>
                        <div class="wave wave-2"></div>
                        <div class="wave wave-3"></div>
                        <div class="wave wave-4"></div>
                        <div class="wave wave-5"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Music Section -->
<?php if (!empty($featured_tracks)): ?>
<section class="mb-5">
    <div class="section-header">
        <div class="section-title-wrapper">
            <div class="section-icon">
                <i class="fas fa-fire"></i>
            </div>
            <div>
                <h2 class="section-title">Popular Tracks</h2>
                <p class="section-subtitle">Trending music everyone's talking about</p>
            </div>
        </div>
        <a href="browse.php" class="btn btn-outline-primary-enhanced">
            <span>View All</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
    
    <div class="tracks-grid">
        <?php foreach (array_slice($featured_tracks, 0, 6) as $index => $track): ?>
        <div class="track-card-enhanced" data-track-id="<?php echo $track['id']; ?>">
            <div class="track-cover-wrapper">
                <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                     alt="<?php echo htmlspecialchars($track['title']); ?>" 
                     class="track-cover-image">
                <div class="track-overlay">
                    <button class="play-btn-enhanced" onclick="playTrackFromCard(<?php echo $index; ?>)">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="track-actions-quick">
                        <?php if (isLoggedIn()): ?>
                        <button class="action-btn-quick favorite-btn" onclick="addToFavorites(<?php echo $track['id']; ?>)">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>
                        <button class="action-btn-quick share-btn" onclick="shareTrack(<?php echo $track['id']; ?>, '<?php echo htmlspecialchars($track['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($track['artist'], ENT_QUOTES); ?>')">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="popularity-indicator">
                    <i class="fas fa-chart-line"></i>
                    <span><?php echo number_format($track['plays_count'] ?? 0); ?></span>
                </div>
            </div>
            <div class="track-info-enhanced">
                <h6 class="track-title-enhanced"><?php echo htmlspecialchars($track['title']); ?></h6>
                <p class="track-artist-enhanced"><?php echo htmlspecialchars($track['artist']); ?></p>
                <div class="track-tags">
                    <?php if ($track['mood_name']): ?>
                    <span class="track-tag mood-tag"><?php echo $track['mood_name']; ?></span>
                    <?php endif; ?>
                    <?php if ($track['language_name']): ?>
                    <span class="track-tag lang-tag"><?php echo $track['language_name']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Browse by Mood Section -->
<?php if (!empty($popular_moods)): ?>
<section class="mb-5">
    <div class="section-header">
        <div class="section-title-wrapper">
            <div class="section-icon">
                <i class="fas fa-palette"></i>
            </div>
            <div>
                <h2 class="section-title">Browse by Mood</h2>
                <p class="section-subtitle">Find music that matches your vibe</p>
            </div>
        </div>
        <a href="browse.php?category=mood" class="btn btn-outline-primary-enhanced">
            <span>All Moods</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
    
    <div class="mood-grid">
        <?php foreach (array_slice($popular_moods, 0, 8) as $mood): ?>
        <a href="browse.php?mood=<?php echo $mood['id']; ?>" class="mood-card-link">
            <div class="mood-card" style="--mood-color: <?php echo $mood['color']; ?>">
                <div class="mood-background"></div>
                <div class="mood-content">
                    <div class="mood-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h5 class="mood-name"><?php echo htmlspecialchars($mood['name']); ?></h5>
                    <div class="mood-stats">
                        <span class="track-count"><?php echo $mood['track_count']; ?> tracks</span>
                    </div>
                </div>
                <div class="mood-wave"></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Browse by Language Section -->
<?php if (!empty($popular_languages)): ?>
<section class="mb-5">
    <div class="section-header">
        <div class="section-title-wrapper">
            <div class="section-icon">
                <i class="fas fa-globe"></i>
            </div>
            <div>
                <h2 class="section-title">Browse by Language</h2>
                <p class="section-subtitle">Explore music from around the world</p>
            </div>
        </div>
        <a href="browse.php?category=language" class="btn btn-outline-primary-enhanced">
            <span>All Languages</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
    
    <div class="language-grid">
        <?php foreach (array_slice($popular_languages, 0, 8) as $language): ?>
        <a href="browse.php?language=<?php echo $language['id']; ?>" class="language-card-link">
            <div class="language-card">
                <div class="language-flag">
                    <i class="fas fa-language"></i>
                </div>
                <div class="language-content">
                    <h5 class="language-name"><?php echo htmlspecialchars($language['name']); ?></h5>
                    <div class="language-stats">
                        <span class="track-count"><?php echo $language['track_count']; ?> tracks</span>
                    </div>
                </div>
                <div class="language-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Recently Added Section -->
<?php if (!empty($recent_tracks)): ?>
<section class="mb-5">
    <div class="section-header">
        <div class="section-title-wrapper">
            <div class="section-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h2 class="section-title">Recently Added</h2>
                <p class="section-subtitle">Fresh tracks just dropped</p>
            </div>
        </div>
        <a href="browse.php?sort=newest" class="btn btn-outline-primary-enhanced">
            <span>View All</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
    
    <div class="recent-tracks-list">
        <?php foreach ($recent_tracks as $index => $track): ?>
        <div class="recent-track-item" data-track-id="<?php echo $track['id']; ?>">
            <div class="track-number">
                <span><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="track-cover-mini">
                <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                     alt="<?php echo htmlspecialchars($track['title']); ?>">
                <div class="mini-play-overlay">
                    <button class="mini-play-btn" onclick="playTrackFromRecent(<?php echo $index; ?>)">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
            </div>
            <div class="track-details">
                <div class="track-primary-info">
                    <h6 class="track-name"><?php echo htmlspecialchars($track['title']); ?></h6>
                    <p class="artist-name"><?php echo htmlspecialchars($track['artist']); ?></p>
                </div>
                <div class="track-meta">
                    <?php if ($track['language_name']): ?>
                        <span class="meta-tag lang"><?php echo $track['language_name']; ?></span>
                    <?php endif; ?>
                    <?php if ($track['mood_name']): ?>
                        <span class="meta-tag mood"><?php echo $track['mood_name']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="track-duration">
                <span><?php echo formatDuration($track['duration'] ?? 0); ?></span>
            </div>
            <div class="track-actions-list">
                <?php if (isLoggedIn()): ?>
                <button class="action-btn-list favorite-btn" onclick="addToFavorites(<?php echo $track['id']; ?>)" title="Add to Favorites">
                    <i class="far fa-heart"></i>
                </button>
                <button class="action-btn-list playlist-btn" title="Add to Playlist">
                    <i class="fas fa-plus"></i>
                </button>
                <?php endif; ?>
                <button class="action-btn-list share-btn" onclick="shareTrack(<?php echo $track['id']; ?>, '<?php echo htmlspecialchars($track['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($track['artist'], ENT_QUOTES); ?>')" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Enhanced Stats Section -->
<section class="stats-section-enhanced mb-5">
    <div class="container">
        <div class="stats-header text-center mb-5">
            <h2 class="stats-main-title">Platform Statistics</h2>
            <p class="stats-subtitle">Join millions of music lovers worldwide</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon tracks">
                    <i class="fas fa-music"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="<?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM tracks");
                        echo $stmt->fetchColumn();
                    ?>">0</div>
                    <div class="stat-label">Total Tracks</div>
                    <div class="stat-description">High-quality music collection</div>
                </div>
                <div class="stat-glow tracks-glow"></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="<?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
                        echo $stmt->fetchColumn();
                    ?>">0</div>
                    <div class="stat-label">Active Users</div>
                    <div class="stat-description">Growing community daily</div>
                </div>
                <div class="stat-glow users-glow"></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon languages">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="<?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM languages");
                        echo $stmt->fetchColumn();
                    ?>">0</div>
                    <div class="stat-label">Languages</div>
                    <div class="stat-description">Diverse cultural content</div>
                </div>
                <div class="stat-glow languages-glow"></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon moods">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="<?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM moods");
                        echo $stmt->fetchColumn();
                    ?>">0</div>
                    <div class="stat-label">Moods</div>
                    <div class="stat-description">Every emotion covered</div>
                </div>
                <div class="stat-glow moods-glow"></div>
            </div>
        </div>
        
        <div class="stats-footer text-center mt-5">
            <div class="achievement-badges">
                <div class="badge-item">
                    <i class="fas fa-award"></i>
                    <span>Top Rated Platform</span>
                </div>
                <div class="badge-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure & Trusted</span>
                </div>
                <div class="badge-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Mobile Optimized</span>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>About Us</h5>
                    <p>
                        Media Crack Zone is your number one source for all things music. We're dedicated to giving you the very best of music streaming, with a focus on dependability, user experience, and uniqueness.
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Contact Us</h5>
                    <p>
                        Have questions or feedback? Reach out to us at:
                    </p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> Email: support@mediacrackzone.com</li>
                        <li><i class="fas fa-phone"></i> Phone: +1 (234) 567-890</li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p>&copy; 2023 Media Crack Zone. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
<script>
// Track data for player
const featuredTracks = <?php echo json_encode($featured_tracks); ?>;
const recentTracks = <?php echo json_encode($recent_tracks); ?>;

// Prepare track data for player - only update cover images, file_path handled by player
featuredTracks.forEach(track => {
    track.cover_image = '<?php echo UPLOAD_URL; ?>covers/' + track.cover_image;
});

recentTracks.forEach(track => {
    track.cover_image = '<?php echo UPLOAD_URL; ?>covers/' + track.cover_image;
});

function playTrackFromCard(index) {
    if (featuredTracks[index]) {
        setPlaylist(featuredTracks, index);
    } else {
        console.error('Track not found at index:', index);
    }
}

function playTrackFromRecent(index) {
    if (recentTracks[index]) {
        setPlaylist(recentTracks, index);
    } else {
        console.error('Track not found at index:', index);
    }
}

// Add to favorites function
function addToFavorites(trackId) {
    <?php if (isLoggedIn()): ?>
    fetch('<?php echo SITE_URL; ?>/api/toggle_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ track_id: trackId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Find all favorite buttons for this track
            const favoriteButtons = document.querySelectorAll(`[onclick*="addToFavorites(${trackId})"]`);
            favoriteButtons.forEach(btn => {
                const icon = btn.querySelector('i');
                if (data.is_favorite) {
                    icon.className = 'fas fa-heart';
                    btn.style.color = '#ff4757';
                    showToast('Added to favorites', 'success');
                } else {
                    icon.className = 'far fa-heart';
                    btn.style.color = '';
                    showToast('Removed from favorites', 'info');
                }
            });
        } else {
            showToast('Error updating favorites', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating favorites', 'error');
    });
    <?php else: ?>
    showToast('Please login to add favorites', 'warning');
    <?php endif; ?>
}

// Share track function
function shareTrack(trackId, title, artist) {
    const trackUrl = `<?php echo SITE_URL; ?>/track.php?id=${trackId}`;
    const shareText = `Check out "${title}" by ${artist} on Media Crack Zone!`;
    
    // Check if Web Share API is supported
    if (navigator.share) {
        navigator.share({
            title: shareText,
            text: shareText,
            url: trackUrl
        }).then(() => {
            showToast('Track shared successfully!', 'success');
        }).catch((error) => {
            if (error.name !== 'AbortError') {
                fallbackShare(trackUrl, shareText);
            }
        });
    } else {
        fallbackShare(trackUrl, shareText);
    }
}

// Fallback share function for browsers without Web Share API
function fallbackShare(url, text) {
    // Create share modal
    const modal = document.createElement('div');
    modal.className = 'share-modal-overlay';
    modal.innerHTML = `
        <div class="share-modal">
            <div class="share-modal-header">
                <h5><i class="fas fa-share-alt me-2"></i>Share Track</h5>
                <button class="close-share-modal" onclick="closeShareModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="share-modal-body">
                <div class="share-options">
                    <button class="share-option" onclick="shareToSocial('facebook', '${url}', '${encodeURIComponent(text)}')">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </button>
                    <button class="share-option" onclick="shareToSocial('twitter', '${url}', '${encodeURIComponent(text)}')">
                        <i class="fab fa-twitter"></i>
                        <span>Twitter</span>
                    </button>
                    <button class="share-option" onclick="shareToSocial('whatsapp', '${url}', '${encodeURIComponent(text)}')">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </button>
                    <button class="share-option" onclick="shareToSocial('telegram', '${url}', '${encodeURIComponent(text)}')">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram</span>
                    </button>
                </div>
                <div class="share-link-section">
                    <label>Copy Link:</label>
                    <div class="share-link-input">
                        <input type="text" value="${url}" readonly id="shareUrlInput">
                        <button onclick="copyShareLink()" class="copy-link-btn">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

// Close share modal
function closeShareModal() {
    const modal = document.querySelector('.share-modal-overlay');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Share to social media
function shareToSocial(platform, url, text) {
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(url)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${text}%20${encodeURIComponent(url)}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${text}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400,scrollbars=yes,resizable=yes');
        closeShareModal();
        showToast('Opening share window...', 'info');
    }
}

// Copy share link
function copyShareLink() {
    const input = document.getElementById('shareUrlInput');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('Link copied to clipboard!', 'success');
        closeShareModal();
    }).catch(() => {
        // Fallback for older browsers
        document.execCommand('copy');
        showToast('Link copied to clipboard!', 'success');
        closeShareModal();
    });
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('share-modal-overlay')) {
        closeShareModal();
    }
});

// Toast notification function (duplicate from footer for index page)
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

// Counter animation for hero stats
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number[data-target]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / 100;
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.floor(current) + '+';
                setTimeout(updateCounter, 20);
            } else {
                counter.textContent = target + '+';
            }
        };
        
        updateCounter();
    });
}

// Intersection Observer for counter animation
const observerOptions = {
    threshold: 0.5,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe hero stats section
const heroStats = document.querySelector('.hero-stats');
if (heroStats) {
    observer.observe(heroStats);
}

// Interactive music player mockup
document.addEventListener('DOMContentLoaded', function() {
    const playBtn = document.querySelector('.player-controls .fa-play');
    const pauseBtn = document.querySelector('.player-controls .fa-pause');
    const progressFill = document.querySelector('.progress-fill');
    const volumeFill = document.querySelector('.volume-fill');
    const eqBars = document.querySelectorAll('.eq-bar');
    
    // Play/Pause functionality
    if (playBtn) {
        playBtn.addEventListener('click', function() {
            this.classList.remove('fa-play');
            this.classList.add('fa-pause');
            
            // Animate equalizer bars
            eqBars.forEach(bar => {
                bar.style.animationPlayState = 'running';
            });
        });
    }
    
    // Volume control interaction
    const volumeBar = document.querySelector('.volume-bar');
    if (volumeBar) {
        volumeBar.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            volumeFill.style.width = (percent * 100) + '%';
        });
    }
    
    // Progress bar interaction
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            progressFill.style.width = (percent * 100) + '%';
        });
    }
});

// Smooth scroll for hero actions
document.querySelectorAll('.hero-actions a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Parallax effect for floating elements
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallaxElements = document.querySelectorAll('.floating-note, .particle');
    
    parallaxElements.forEach((element, index) => {
        const speed = (index + 1) * 0.5;
        const yPos = -(scrolled * speed / 100);
        element.style.transform = `translateY(${yPos}px)`;
    });
});

// Social links hover effects
document.querySelectorAll('.social-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) scale(1.1)';
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
