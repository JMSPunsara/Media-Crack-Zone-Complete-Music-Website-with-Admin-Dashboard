<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'My Favorites';
include 'includes/header.php';

// Get user's favorite tracks
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image,
           f.created_at as favorited_at
    FROM favorites f
    JOIN tracks t ON f.track_id = t.id
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$favorite_tracks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold">
            <i class="fas fa-heart me-2 text-danger"></i>My Favorites
        </h1>
        <p class="text-white-75 mb-0"><?php echo count($favorite_tracks); ?> favorite tracks</p>
    </div>
    
    <?php if (!empty($favorite_tracks)): ?>
    <button class="btn btn-primary" onclick="playAllFavorites()">
        <i class="fas fa-play me-2"></i>Play All
    </button>
    <?php endif; ?>
</div>

<?php if (empty($favorite_tracks)): ?>
<div class="text-center py-5">
    <i class="fas fa-heart text-white-75" style="font-size: 5rem;"></i>
    <h4 class="mt-3 mb-2 text-white">No favorites yet</h4>
    <p class="text-white-75 mb-4">Start adding tracks to your favorites to see them here.</p>
    <a href="browse.php" class="btn btn-primary">
        <i class="fas fa-compass me-2"></i>Browse Music
    </a>
</div>
<?php else: ?>

<div class="row">
    <div class="col-12">
        <!-- List View -->
        <div class="card-custom p-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th style="width: 80px;">Cover</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Album</th>
                            <th>Language</th>
                            <th>Mood</th>
                            <th>Added</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($favorite_tracks as $index => $track): ?>
                        <tr data-track-id="<?php echo $track['id']; ?>" class="track-row">
                            <td class="align-middle">
                                <button class="btn btn-sm btn-outline-primary" onclick="playFavoriteTrack(<?php echo $index; ?>)">
                                    <i class="fas fa-play"></i>
                                </button>
                            </td>
                            <td class="align-middle">
                                <img src="<?php echo UPLOAD_URL . 'covers/' . $track['cover_image']; ?>" 
                                     alt="Cover" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                            </td>
                            <td class="align-middle">
                                <div class="fw-semibold text-white"><?php echo htmlspecialchars($track['title']); ?></div>
                                <small class="text-white-75">
                                    <i class="fas fa-play me-1"></i><?php echo number_format($track['plays_count']); ?> plays
                                </small>
                            </td>
                            <td class="align-middle text-white"><?php echo htmlspecialchars($track['artist']); ?></td>
                            <td class="align-middle text-white">
                                <?php echo $track['album'] ? htmlspecialchars($track['album']) : '-'; ?>
                            </td>
                            <td class="align-middle">
                                <?php if ($track['language_name']): ?>
                                <span class="badge bg-secondary"><?php echo $track['language_name']; ?></span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <?php if ($track['mood_name']): ?>
                                <span class="badge" style="background-color: <?php echo $track['mood_color']; ?>">
                                    <?php echo $track['mood_name']; ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <small class="text-white-75">
                                    <?php echo date('M j, Y', strtotime($track['favorited_at'])); ?>
                                </small>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromFavorites(<?php echo $track['id']; ?>)" title="Remove from favorites">
                                        <i class="fas fa-heart-broken"></i>
                                    </button>
                                    <a href="browse.php?search=<?php echo urlencode($track['artist']); ?>" 
                                       class="btn btn-sm btn-outline-info" title="More by artist">
                                        <i class="fa-solid fa-palette"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Favorite Statistics -->
<div class="row mt-4">
    <div class="col-md-3 mb-3">
        <div class="card-custom p-3 text-center">
            <h5 class="text-primary mb-1"><?php echo count($favorite_tracks); ?></h5>
            <small class="stats-text">Total Favorites</small>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-custom p-3 text-center">
            <h5 class="text-success mb-1">
                <?php 
                $languages = array_unique(array_filter(array_column($favorite_tracks, 'language_name')));
                echo count($languages);
                ?>
            </h5>
            <small class="stats-text">Languages</small>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-custom p-3 text-center">
            <h5 class="text-warning mb-1">
                <?php 
                $moods = array_unique(array_filter(array_column($favorite_tracks, 'mood_name')));
                echo count($moods);
                ?>
            </h5>
            <small class="stats-text">Moods</small>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card-custom p-3 text-center">
            <h5 class="text-info mb-1">
                <?php 
                $artists = array_unique(array_column($favorite_tracks, 'artist'));
                echo count($artists);
                ?>
            </h5>
            <small class="stats-text">Artists</small>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Favorite tracks data for player
const favoriteTracks = <?php echo json_encode($favorite_tracks); ?>;

// Prepare track data for player
favoriteTracks.forEach(track => {
    track.file_path = '<?php echo UPLOAD_URL; ?>music/' + track.file_path;
    track.cover_image = '<?php echo UPLOAD_URL; ?>covers/' + track.cover_image;
});

function playFavoriteTrack(index) {
    setPlaylist(favoriteTracks, index);
}

function playAllFavorites() {
    if (favoriteTracks.length > 0) {
        setPlaylist(favoriteTracks, 0);
    }
}

function removeFromFavorites(trackId) {
    if (confirm('Remove this track from your favorites?')) {
        fetch('<?php echo SITE_URL; ?>/api/toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ track_id: trackId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table
                const row = document.querySelector(`tr[data-track-id="${trackId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Update counters
                const remaining = document.querySelectorAll('.track-row').length;
                if (remaining === 0) {
                    location.reload(); // Reload to show empty state
                }
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>Track removed from favorites
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content .container-fluid').insertBefore(alert, document.querySelector('.main-content .container-fluid').firstChild);
            }
        })
        .catch(error => {
            console.error('Error removing favorite:', error);
            alert('Failed to remove from favorites. Please try again.');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
