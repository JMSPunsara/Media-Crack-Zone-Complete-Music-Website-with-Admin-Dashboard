<?php
require_once 'config.php';
require_once 'includes/seo.php';

// Get track ID from URL or slug
$trackId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trackSlug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

// Get track details
if ($trackSlug) {
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
               COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        WHERE t.seo_slug = ?
    ");
    $stmt->execute([$trackSlug]);
} else if ($trackId) {
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
               COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$trackId]);
} else {
    header('Location: index.php');
    exit;
}

$track = $stmt->fetch();

if (!$track) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Set SEO for this track
setTrackSEO($track);
$pageTitle = $track['seo_title'] ?: ($track['title'] . ' - ' . $track['artist']);
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="track-detail-card">
                <div class="track-detail-header">
                    <div class="track-cover-large">
                        <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                             alt="<?php echo htmlspecialchars($track['title']); ?>" 
                             class="track-image">
                        <div class="play-overlay">
                            <button class="play-btn-detail" onclick="playTrack()">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="track-info-large">
                        <div class="track-meta-small">TRACK</div>
                        <h1 class="track-title-large"><?php echo htmlspecialchars($track['title']); ?></h1>
                        <h2 class="track-artist-large"><?php echo htmlspecialchars($track['artist']); ?></h2>
                        
                        <div class="track-stats">
                            <span class="stat-item">
                                <i class="fas fa-play"></i>
                                <?php echo number_format($track['plays_count'] ?? 0); ?> plays
                            </span>
                            <?php if ($track['language_name']): ?>
                            <span class="stat-item">
                                <i class="fas fa-language"></i>
                                <?php echo $track['language_name']; ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($track['mood_name']): ?>
                            <span class="stat-item">
                                <i class="fas fa-heart"></i>
                                <?php echo $track['mood_name']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="track-actions-detail">
                            <button class="btn btn-primary-enhanced btn-lg" onclick="playTrack()">
                                <i class="fas fa-play me-2"></i>Play
                            </button>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-outline-light" onclick="addToFavorites(<?php echo $track['id']; ?>)">
                                <i class="far fa-heart me-2"></i>Add to Favorites
                            </button>
                            <button class="btn btn-outline-light" onclick="addToPlaylist(<?php echo $track['id']; ?>)">
                                <i class="fas fa-plus me-2"></i>Add to Playlist
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-light" onclick="shareTrack(<?php echo $track['id']; ?>, '<?php echo htmlspecialchars($track['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($track['artist'], ENT_QUOTES); ?>')">
                                <i class="fas fa-share-alt me-2"></i>Share
                            </button>
                            <button class="btn btn-outline-light" onclick="loadSimilarTracks()">
                                <i class="fas fa-music me-2"></i>Similar Tracks
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($track['description']): ?>
                <div class="track-description">
                    <h5>About this track</h5>
                    <p><?php echo nl2br(htmlspecialchars($track['description'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Similar Tracks Section -->
                <div id="similarTracksSection" class="similar-tracks-section" style="display: none;">
                    <div class="section-header">
                        <h5><i class="fas fa-music me-2"></i>Similar Tracks</h5>
                        <button class="btn btn-sm btn-outline-light" onclick="hideSimilarTracks()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="similarTracks" class="similar-tracks-grid">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading similar tracks...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JSON-LD Structured Data for Track -->
<script type="application/ld+json">
<?php echo MusicSEO::generateTrackJsonLd($track); ?>
</script>

<!-- Breadcrumb JSON-LD -->
<script type="application/ld+json">
<?php 
$breadcrumbs = [
    ['name' => 'Home', 'url' => SITE_URL],
    ['name' => 'Browse Music', 'url' => SITE_URL . '/browse'],
    ['name' => $track['artist'], 'url' => SITE_URL . '/artist/' . urlencode($track['artist'])],
    ['name' => $track['title'], 'url' => SITE_URL . '/track/' . $track['id']]
];
echo MusicSEO::generateBreadcrumbJsonLd($breadcrumbs);
?>
</script>

<style>
.track-detail-card {
    background: linear-gradient(135deg, var(--background-card) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 20px;
    overflow: hidden;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.track-detail-header {
    display: flex;
    padding: 2rem;
    gap: 2rem;
    align-items: flex-end;
}

.track-cover-large {
    position: relative;
    flex-shrink: 0;
    width: 250px;
    height: 250px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.track-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.track-cover-large:hover .play-overlay {
    opacity: 1;
}

.play-btn-detail {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border: none;
    color: white;
    font-size: 1.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.play-btn-detail:hover {
    transform: scale(1.1);
}

.track-info-large {
    flex: 1;
}

.track-meta-small {
    font-size: 0.8rem;
    color: var(--text-light);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

.track-title-large {
    font-size: 3rem;
    font-weight: 900;
    color: var(--text-white);
    margin-bottom: 0.5rem;
    line-height: 1.1;
}

.track-artist-large {
    font-size: 1.5rem;
    font-weight: 400;
    color: var(--text-light);
    margin-bottom: 1.5rem;
}

.track-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    color: var(--text-light);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.track-actions-detail {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.track-description {
    padding: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.track-description h5 {
    color: var(--text-white);
    margin-bottom: 1rem;
}

.track-description p {
    color: var(--text-light);
    line-height: 1.6;
}

@media (max-width: 768px) {
    .track-detail-header {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    
    .track-cover-large {
        width: 200px;
        height: 200px;
    }
    
    .track-title-large {
        font-size: 2rem;
    }
    
    .track-actions-detail {
        justify-content: center;
    }
}

.similar-tracks-section {
    padding: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h5 {
    color: var(--text-white);
    margin: 0;
}

.similar-tracks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.similar-track-item {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
}

.similar-track-item:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.similar-track-cover {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.similar-track-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.similar-track-info {
    flex: 1;
}

.similar-track-title {
    color: var(--text-white);
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.similar-track-artist {
    color: var(--text-light);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.similar-track-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-light);
}

.similar-track-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.loading-spinner {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem;
    color: var(--text-light);
}
</style>

<script>
const track = <?php echo json_encode([
    'id' => $track['id'],
    'title' => $track['title'],
    'artist' => $track['artist'],
    'file_path' => UPLOAD_URL . 'music/' . $track['file_path'],
    'cover_image' => UPLOAD_URL . 'covers/' . $track['cover_image'],
    'duration' => $track['duration'] ?? 0,
    'language_id' => $track['language_id'],
    'mood_id' => $track['mood_id']
]); ?>;

function playTrack() {
    if (typeof window.playTrackGlobal === 'function') {
        window.playTrackGlobal(track);
    } else if (typeof setPlaylist === 'function') {
        setPlaylist([track], 0);
    } else {
        // Fallback if player is not available
        window.location.href = 'browse.php';
    }
}

function loadSimilarTracks() {
    const section = document.getElementById('similarTracksSection');
    const container = document.getElementById('similarTracks');
    
    section.style.display = 'block';
    container.innerHTML = '<div class="loading-spinner"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading similar tracks...</p></div>';
    
    fetch('<?php echo SITE_URL; ?>/api/get_similar_tracks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            track_id: track.id,
            filters: {
                language_id: track.language_id,
                mood_id: track.mood_id
            },
            exclude_played: [track.id],
            limit: 8
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tracks.length > 0) {
            let html = '';
            data.tracks.forEach((similarTrack, index) => {
                html += `
                    <div class="similar-track-item" onclick="playSimilarTrack(${index})">
                        <div class="similar-track-cover">
                            <img src="${similarTrack.cover_image}" alt="${similarTrack.title}">
                        </div>
                        <div class="similar-track-info">
                            <div class="similar-track-title">${similarTrack.title}</div>
                            <div class="similar-track-artist">${similarTrack.artist}</div>
                            <div class="similar-track-stats">
                                <span><i class="fas fa-play"></i> ${similarTrack.plays_count || 0}</span>
                                ${similarTrack.language_name ? `<span><i class="fas fa-language"></i> ${similarTrack.language_name}</span>` : ''}
                            </div>
                        </div>
                        <div class="similar-track-actions">
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); playSimilarTrack(${index})">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            
            // Store similar tracks for playing
            window.similarTracks = data.tracks;
        } else {
            container.innerHTML = '<div class="text-center py-4"><p class="text-white-75">No similar tracks found.</p></div>';
        }
    })
    .catch(error => {
        console.error('Error loading similar tracks:', error);
        container.innerHTML = '<div class="text-center py-4"><p class="text-white-75">Error loading similar tracks.</p></div>';
    });
}

function hideSimilarTracks() {
    document.getElementById('similarTracksSection').style.display = 'none';
}

function playSimilarTrack(index) {
    if (window.similarTracks && window.similarTracks[index]) {
        if (typeof window.setPlaylist === 'function') {
            window.setPlaylist(window.similarTracks, index);
        } else if (typeof window.playTrackGlobal === 'function') {
            window.playTrackGlobal(window.similarTracks[index]);
        }
    }
}

function addToPlaylist(trackId) {
    <?php if (isLoggedIn()): ?>
    // You can implement playlist functionality here
    showToast('Playlist functionality coming soon!', 'info');
    <?php else: ?>
    showToast('Please login to add to playlist', 'warning');
    <?php endif; ?>
}

// Add the same share and favorites functions from index.php
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

function shareTrack(trackId, title, artist) {
    const trackUrl = `<?php echo SITE_URL; ?>/track.php?id=${trackId}`;
    const shareText = `Check out "${title}" by ${artist} on Media Crack Zone!`;
    
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

function fallbackShare(url, text) {
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

function closeShareModal() {
    const modal = document.querySelector('.share-modal-overlay');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

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

function copyShareLink() {
    const input = document.getElementById('shareUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('Link copied to clipboard!', 'success');
        closeShareModal();
    }).catch(() => {
        document.execCommand('copy');
        showToast('Link copied to clipboard!', 'success');
        closeShareModal();
    });
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('share-modal-overlay')) {
        closeShareModal();
    }
});

function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
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
