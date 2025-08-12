<?php
require_once '../config.php';
require_once '../includes/auto_sitemap.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM tracks");
$total_tracks = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM languages");
$total_languages = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM moods");
$total_moods = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(plays_count) FROM tracks");
$total_plays = $stmt->fetchColumn() ?: 0;

// Get sitemap statistics
$sitemap = new AutoSitemap();
$sitemap_stats = $sitemap->getStats();

// Recent uploads
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name 
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_tracks = $stmt->fetchAll();

// Recent users
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE is_admin = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i>Admin Dashboard
    </h1>
    <div>
        <a href="upload.php" class="btn btn-primary me-2">
            <i class="fas fa-upload me-2"></i>Upload Music
        </a>
        <a href="sitemap.php" class="btn btn-outline-info me-2">
            <i class="fas fa-sitemap me-2"></i>Sitemap
        </a>
        <a href="manage-categories.php" class="btn btn-outline-primary">
            <i class="fas fa-tags me-2"></i>Manage Categories
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-5">
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold text-primary mb-2"><?php echo number_format($total_tracks); ?></div>
            <div class="small stats-text">Total Tracks</div>
            <i class="fas fa-music text-primary mt-2" style="font-size: 2rem;"></i>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold text-success mb-2"><?php echo number_format($total_users); ?></div>
            <div class="small stats-text">Total Users</div>
            <i class="fas fa-users text-success mt-2" style="font-size: 2rem;"></i>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold text-warning mb-2"><?php echo number_format($total_languages); ?></div>
            <div class="small stats-text">Languages</div>
            <i class="fas fa-language text-warning mt-2" style="font-size: 2rem;"></i>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold text-info mb-2"><?php echo number_format($total_moods); ?></div>
            <div class="small stats-text">Moods</div>
            <i class="fas fa-palette text-info mt-2" style="font-size: 2rem;"></i>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold text-danger mb-2"><?php echo number_format($total_plays); ?></div>
            <div class="small stats-text">Total Plays</div>
            <i class="fas fa-play-circle text-danger mt-2" style="font-size: 2rem;"></i>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <div class="h2 fw-bold <?php echo $sitemap_stats['sitemap_exists'] ? 'text-success' : 'text-warning'; ?> mb-2">
                <?php echo number_format($sitemap_stats['url_count']); ?>
            </div>
            <div class="small stats-text">Sitemap URLs</div>
            <i class="fas fa-sitemap <?php echo $sitemap_stats['sitemap_exists'] ? 'text-success' : 'text-warning'; ?> mt-2" style="font-size: 2rem;"></i>
            <?php if ($sitemap_stats['needs_update']): ?>
            <div class="small text-warning mt-1">
                <i class="fas fa-exclamation-triangle"></i> Needs Update
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
        <div class="card-custom p-4 text-center">
            <a href="tracks.php" class="text-decoration-none">
                <div class="h2 fw-bold text-secondary mb-2">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="small stats-text">Manage</div>
            </a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-5">
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <h5 class="mb-4">
                <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
            </h5>
            <div class="row">
                <div class="col-6 mb-3">
                    <a href="upload.php" class="btn btn-primary w-100">
                        <i class="fas fa-upload mb-2 d-block"></i>
                        Upload Music
                    </a>
                </div>
                <div class="col-6 mb-3">
                    <a href="tracks.php" class="btn btn-success w-100">
                        <i class="fas fa-list mb-2 d-block"></i>
                        Manage Tracks
                    </a>
                </div>
                <div class="col-6 mb-3">
                    <a href="users.php" class="btn btn-info w-100">
                        <i class="fas fa-users mb-2 d-block"></i>
                        Manage Users
                    </a>
                </div>
                <div class="col-6 mb-3">
                    <a href="manage-categories.php" class="btn btn-warning w-100">
                        <i class="fas fa-tags mb-2 d-block"></i>
                        Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <h5 class="mb-4">
                <i class="fas fa-chart-line me-2 text-success"></i>Quick Stats
            </h5>
            <div class="row text-center">
                <div class="col-4 mb-3">
                    <div class="h4 fw-bold text-primary">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM tracks WHERE DATE(created_at) = CURDATE()");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <small class="stats-text">Today's Uploads</small>
                </div>
                <div class="col-4 mb-3">
                    <div class="h4 fw-bold text-success">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <small class="stats-text">New Users</small>
                </div>
                <div class="col-4 mb-3">
                    <div class="h4 fw-bold text-warning">
                        <?php 
                        $stmt = $pdo->query("SELECT AVG(plays_count) FROM tracks");
                        echo number_format($stmt->fetchColumn(), 1);
                        ?>
                    </div>
                    <small class="stats-text">Avg. Plays</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Content -->
<div class="row">
    <!-- Recent Tracks -->
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-music me-2 text-primary"></i>Recent Uploads
                </h5>
                <a href="tracks.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            
            <?php if (empty($recent_tracks)): ?>
            <p class="text-white-75 text-center py-3">No tracks uploaded yet</p>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_tracks as $track): ?>
                <div class="list-group-item bg-transparent border-0 px-0 py-2">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo UPLOAD_URL . 'covers/' . ($track['cover_image'] ?: 'default-cover.jpg'); ?>" 
                             alt="Cover" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-white"><?php echo htmlspecialchars($track['title']); ?></h6>
                            <small class="text-white-75"><?php echo htmlspecialchars($track['artist']); ?></small>
                            <div class="small">
                                <?php if ($track['language_name']): ?>
                                <span class="badge bg-secondary me-1"><?php echo $track['language_name']; ?></span>
                                <?php endif; ?>
                                <?php if ($track['mood_name']): ?>
                                <span class="badge bg-secondary"><?php echo $track['mood_name']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <small class="text-white-75"><?php echo date('M j', strtotime($track['created_at'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="col-md-6 mb-4">
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2 text-success"></i>Recent Users
                </h5>
                <a href="users.php" class="btn btn-outline-success btn-sm">View All</a>
            </div>
            
            <?php if (empty($recent_users)): ?>
            <p class="text-white-75 text-center py-3">No users registered yet</p>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_users as $user): ?>
                <div class="list-group-item bg-transparent border-0 px-0 py-2">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center me-3" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-white"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                            <small class="text-white-75">@<?php echo htmlspecialchars($user['username']); ?></small>
                        </div>
                        <small class="text-white-75"><?php echo date('M j', strtotime($user['created_at'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
