<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

// Handle track deletion
if (isset($_POST['delete_track'])) {
    $track_id = (int)$_POST['track_id'];
    
    // Get track info for file deletion
    $stmt = $pdo->prepare("SELECT file_path, cover_image FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    $track = $stmt->fetch();
    
    if ($track) {
        // Delete files
        $music_file = UPLOAD_PATH . 'music/' . $track['file_path'];
        $cover_file = UPLOAD_PATH . 'covers/' . $track['cover_image'];
        
        if (file_exists($music_file)) {
            unlink($music_file);
        }
        if ($track['cover_image'] !== 'default-cover.jpg' && file_exists($cover_file)) {
            unlink($cover_file);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM tracks WHERE id = ?");
        $stmt->execute([$track_id]);
        
        $success = 'Track deleted successfully!';
    }
}

// Get all tracks with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$language_filter = isset($_GET['language']) ? (int)$_GET['language'] : 0;
$mood_filter = isset($_GET['mood']) ? (int)$_GET['mood'] : 0;

// Build query
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

// Get total count
$count_query = "SELECT COUNT(*) FROM tracks t $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_tracks = $stmt->fetchColumn();

// Get tracks
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
           u.username as uploaded_by_name,
           COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    LEFT JOIN users u ON t.uploaded_by = u.id
    $where_clause
    ORDER BY t.created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$tracks = $stmt->fetchAll();

$total_pages = ceil($total_tracks / $per_page);

// Get languages and moods for filters
$stmt = $pdo->query("SELECT * FROM languages ORDER BY name");
$languages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM moods ORDER BY name");
$moods = $stmt->fetchAll();

$pageTitle = 'Manage Tracks';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold">
            <i class="fas fa-music me-2 text-primary"></i>Manage Tracks
        </h1>
        <p class="text-muted mb-0"><?php echo number_format($total_tracks); ?> total tracks</p>
    </div>
    <div>
        <button type="button" id="bulkEditBtn" class="btn btn-warning me-2" style="display: none;" onclick="openBulkEdit()">
            <i class="fas fa-edit me-2"></i>Bulk Edit
        </button>
        <a href="upload.php" class="btn btn-primary me-2">
            <i class="fas fa-upload me-2"></i>Upload Track
        </a>
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
        </a>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card-custom p-4 mb-4">
    <form method="GET" action="" class="row align-items-end">
        <div class="col-md-4 mb-3">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search tracks, artists...">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Language</label>
            <select class="form-control" name="language">
                <option value="">All Languages</option>
                <?php foreach ($languages as $language): ?>
                <option value="<?php echo $language['id']; ?>" <?php echo $language_filter == $language['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($language['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Mood</label>
            <select class="form-control" name="mood">
                <option value="">All Moods</option>
                <?php foreach ($moods as $mood): ?>
                <option value="<?php echo $mood['id']; ?>" <?php echo $mood_filter == $mood['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($mood['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-2"></i>Filter
            </button>
        </div>
    </form>
</div>

<!-- Tracks Table -->
<div class="card-custom p-4">
    <?php if (empty($tracks)): ?>
    <div class="text-center py-5">
        <i class="fas fa-music text-muted" style="font-size: 5rem;"></i>
        <h4 class="mt-3 mb-2">No tracks found</h4>
        <p class="text-muted">No tracks match your search criteria.</p>
        <a href="upload.php" class="btn btn-primary">Upload First Track</a>
    </div>
    <?php else: ?>
    
    <div class="table-responsive">
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input" 
                               onchange="toggleAllTracks(this)" title="Select All">
                    </th>
                    <th style="width: 80px;">Cover</th>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Album</th>
                    <th>Language</th>
                    <th>Mood</th>
                    <th>Plays</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tracks as $track): ?>
                <tr data-track-id="<?php echo $track['id']; ?>">
                    <td class="align-middle">
                        <input type="checkbox" class="form-check-input" 
                               name="selected_tracks[]" 
                               value="<?php echo $track['id']; ?>"
                               onchange="toggleTrackSelection(<?php echo $track['id']; ?>, this)">
                    </td>
                    <td class="align-middle">
                        <?php 
                        // Construct proper cover URL with fallback
                        $cover_image = $track['cover_image'];
                        $default_svg = 'data:image/svg+xml;base64,' . base64_encode('
                            <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#1db954"/>
                                        <stop offset="100%" style="stop-color:#191414"/>
                                    </linearGradient>
                                </defs>
                                <rect width="60" height="60" fill="url(#grad)" rx="8"/>
                                <circle cx="30" cy="25" r="8" fill="white" opacity="0.8"/>
                                <rect x="22" y="35" width="16" height="2" rx="1" fill="white" opacity="0.8"/>
                                <rect x="24" y="39" width="12" height="2" rx="1" fill="white" opacity="0.6"/>
                                <rect x="26" y="43" width="8" height="2" rx="1" fill="white" opacity="0.6"/>
                            </svg>
                        ');
                        
                        if (empty($cover_image) || $cover_image === 'default-cover.jpg') {
                            $cover_url = $default_svg;
                            $is_default = true;
                        } else {
                            $cover_url = UPLOAD_URL . 'covers/' . $cover_image;
                            $is_default = false;
                        }
                        ?>
                        <div class="cover-container">
                            <img src="<?php echo $cover_url; ?>" 
                                 alt="<?php echo htmlspecialchars($track['title']); ?>" 
                                 class="rounded track-cover-img" 
                                 style="width: 60px; height: 60px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);"
                                 data-fallback="<?php echo $default_svg; ?>"
                                 onerror="this.src=this.dataset.fallback; this.onerror=null;">
                            <?php if ($is_default): ?>
                            <div class="cover-status" title="No cover image">
                                <i class="fas fa-image text-muted" style="font-size: 10px;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="align-middle">
                        <div class="fw-semibold"><?php echo htmlspecialchars($track['title']); ?></div>
                        <small class="text-muted">ID: <?php echo $track['id']; ?></small>
                    </td>
                    <td class="align-middle"><?php echo htmlspecialchars($track['artist']); ?></td>
                    <td class="align-middle">
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
                        <span class="badge bg-info"><?php echo number_format($track['plays_count']); ?></span>
                    </td>
                    <td class="align-middle">
                        <div>
                            <small><?php echo date('M j, Y', strtotime($track['created_at'])); ?></small>
                        </div>
                        <?php if ($track['uploaded_by_name']): ?>
                        <small class="text-muted">by <?php echo htmlspecialchars($track['uploaded_by_name']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex gap-2">
                            <a href="edit_track.php?id=<?php echo $track['id']; ?>" 
                               class="btn btn-warning btn-sm" 
                               title="Edit Track">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-info btn-sm" 
                                    onclick="openQuickEdit(<?php echo $track['id']; ?>)"
                                    title="Quick Edit">
                                <i class="fas fa-lightning-bolt"></i>
                            </button>
                            <a href="../browse.php?search=<?php echo urlencode($track['title']); ?>" 
                               class="btn btn-sm btn-outline-info" title="View in Browse">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form method="POST" action="" style="display: inline;" 
                                  onsubmit="return confirm('Delete this track? This action cannot be undone.')">
                                <input type="hidden" name="track_id" value="<?php echo $track['id']; ?>">
                                <button type="submit" name="delete_track" class="btn btn-sm btn-outline-danger" title="Delete Track">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav>
            <ul class="pagination pagination-sm">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo $language_filter; ?>&mood=<?php echo $mood_filter; ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page || ($i <= 3) || ($i > $total_pages - 3) || (abs($i - $page) <= 2)): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo $language_filter; ?>&mood=<?php echo $mood_filter; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php elseif ($i == 4 && $page > 6): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php elseif ($i == $total_pages - 3 && $page < $total_pages - 5): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo $language_filter; ?>&mood=<?php echo $mood_filter; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<!-- Page Info -->
<div class="row mt-4">
    <div class="col-md-6">
        <p class="text-muted">
            Showing <?php echo number_format(min($total_tracks, ($page - 1) * $per_page + 1)); ?> 
            to <?php echo number_format(min($total_tracks, $page * $per_page)); ?> 
            of <?php echo number_format($total_tracks); ?> tracks
        </p>
    </div>
    <div class="col-md-6 text-end">
        <div class="d-flex justify-content-end gap-3">
            <div class="text-center">
                <div class="h5 mb-0 text-primary"><?php echo number_format(array_sum(array_column($tracks, 'plays_count'))); ?></div>
                <small class="text-muted">Total Plays (This Page)</small>
            </div>
            <div class="text-center">
                <div class="h5 mb-0 text-success"><?php echo count(array_unique(array_column($tracks, 'artist'))); ?></div>
                <small class="text-muted">Unique Artists</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Edit Modal -->
<div class="modal fade" id="quickEditModal" tabindex="-1" aria-labelledby="quickEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="quickEditModalLabel">
                    <i class="fas fa-edit me-2"></i>Quick Edit Track
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="quickEditForm">
                    <input type="hidden" id="quick_track_id" name="track_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quick_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="quick_title" name="title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quick_artist" class="form-label">Artist</label>
                            <input type="text" class="form-control" id="quick_artist" name="artist" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quick_album" class="form-label">Album</label>
                            <input type="text" class="form-control" id="quick_album" name="album">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quick_language" class="form-label">Language</label>
                            <select class="form-control" id="quick_language" name="language_id">
                                <option value="">Select Language</option>
                                <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['id']; ?>">
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quick_mood" class="form-label">Mood</label>
                        <select class="form-control" id="quick_mood" name="mood_id">
                            <option value="">Select Mood</option>
                            <?php foreach ($moods as $mood): ?>
                            <option value="<?php echo $mood['id']; ?>" data-color="<?php echo $mood['color']; ?>">
                                <?php echo htmlspecialchars($mood['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="saveQuickEdit()">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkEditModalLabel">
                    <i class="fas fa-edit me-2"></i>Bulk Edit Tracks
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Leave fields empty to keep current values. Only filled fields will be updated.
                </div>
                
                <form id="bulkEditForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bulk_language" class="form-label">Language</label>
                            <select class="form-control" id="bulk_language" name="language_id">
                                <option value="">Keep current language</option>
                                <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['id']; ?>">
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bulk_mood" class="form-label">Mood</label>
                            <select class="form-control" id="bulk_mood" name="mood_id">
                                <option value="">Keep current mood</option>
                                <?php foreach ($moods as $mood): ?>
                                <option value="<?php echo $mood['id']; ?>" data-color="<?php echo $mood['color']; ?>">
                                    <?php echo htmlspecialchars($mood['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk_album" class="form-label">Album</label>
                        <input type="text" class="form-control" id="bulk_album" name="album" 
                               placeholder="Leave empty to keep current album">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="saveBulkEdit()">
                    <i class="fas fa-save me-2"></i>Update Selected Tracks
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.pagination .page-link {
    background-color: var(--background-card);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-light);
}

.pagination .page-link:hover {
    background-color: var(--background-hover);
    border-color: var(--primary-color);
    color: var(--text-white);
}

.pagination .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--text-white);
}

/* Enhanced cover image styles */
.cover-container {
    position: relative;
    display: inline-block;
}

.cover-container:hover .cover-overlay {
    opacity: 1 !important;
}

.cover-status {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.track-cover-img {
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.track-cover-img:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 16px rgba(29, 185, 84, 0.3);
}

/* Loading state for images */
.track-cover-img[src*="data:image"] {
    background: linear-gradient(135deg, #1db954 0%, #191414 100%);
    border: 1px solid rgba(29, 185, 84, 0.3);
}

/* Image loading animation */
.track-cover-img:not(.loaded) {
    animation: pulse-loading 1.5s ease-in-out infinite;
}

@keyframes pulse-loading {
    0% { opacity: 0.5; }
    50% { opacity: 0.8; }
    100% { opacity: 0.5; }
}

/* Table row hover effects */
.table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
    border-left: 3px solid var(--primary-color);
}

/* Badge enhancements */
.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
    border-radius: 0.25rem;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

/* Action buttons enhancement */
.btn-sm {
    transition: all 0.3s ease;
}

.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.btn-outline-info:hover {
    background-color: #17a2b8;
    border-color: #17a2b8;
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
}

.btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Upload button enhancement */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(29, 185, 84, 0.3);
}

/* Stats display enhancement */
.h5 {
    font-weight: 700;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Filter form enhancements */
.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
}

.form-control {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-white);
}

.form-control::placeholder {
    color: var(--text-light);
}

/* Empty state enhancement */
.text-center i.fa-music {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .track-cover-img {
        width: 45px !important;
        height: 45px !important;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced image loading with retry mechanism
    function handleImageError(img) {
        if (!img.dataset.retried) {
            img.dataset.retried = 'true';
            // Try with a different approach - reload the same src
            const originalSrc = img.src;
            img.src = '';
            setTimeout(() => {
                img.src = originalSrc + '?t=' + Date.now();
            }, 100);
        } else {
            // Use fallback SVG
            img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1zbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImdyYWQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMxZGI5NTQiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMxOTE0MTQiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIGZpbGw9InVybCgjZ3JhZCkiLz48dGV4dCB4PSIzMCIgeT0iMzUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjIwIj7wn46VPC90ZXh0Pjwvc3ZnPg==';
        }
    }

    // Apply error handling to all track cover images
    document.querySelectorAll('.track-cover-img').forEach(img => {
        img.addEventListener('error', function() {
            handleImageError(this);
        });
        
        // Add loading indicator
        img.addEventListener('load', function() {
            this.style.opacity = '1';
            this.classList.add('loaded');
        });
        
        // Initial opacity for loading effect
        if (!img.complete) {
            img.style.opacity = '0.5';
            img.style.transition = 'opacity 0.3s ease';
        }
    });

    // Enhanced delete confirmation
    document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const trackTitle = this.closest('tr').querySelector('.fw-semibold').textContent;
            
            if (confirm(`Are you sure you want to delete "${trackTitle}"?\n\nThis action cannot be undone and will permanently remove:\n• The track file\n• Cover image\n• All play history\n• User favorites`)) {
                this.submit();
            }
        });
        
        // Remove the inline onsubmit handler
        form.removeAttribute('onsubmit');
    });

    // Add loading states for action buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.type === 'submit') {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + this.textContent.trim();
                this.disabled = true;
            }
        });
    });

    // Filter form auto-submit on change
    const filterInputs = document.querySelectorAll('select[name="language"], select[name="mood"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // Search input debounce
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.closest('form').submit();
                }
            }, 500);
        });
    }

    // Cover image preview on hover
    document.querySelectorAll('.track-cover-img').forEach(img => {
        img.addEventListener('mouseenter', function() {
            if (!this.src.includes('data:image')) {
                this.style.transform = 'scale(1.1)';
                this.style.zIndex = '10';
                this.style.boxShadow = '0 8px 32px rgba(29, 185, 84, 0.4)';
            }
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.zIndex = '1';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.3)';
        });
    });

    // Table row click to highlight
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('a')) {
                this.classList.toggle('table-active');
            }
        });
    });

    // Bulk actions (future enhancement)
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    if (checkboxes.length > 0) {
        // Add bulk action functionality here if needed
    }

    // Smooth scroll for pagination
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        });
    });

    // Real-time stats update
    function updateStats() {
        const visibleRows = document.querySelectorAll('tbody tr:not(.d-none)');
        const totalPlaysElement = document.querySelector('.h5.text-primary');
        const uniqueArtistsElement = document.querySelector('.h5.text-success');
        
        if (totalPlaysElement && uniqueArtistsElement) {
            let totalPlays = 0;
            const artists = new Set();
            
            visibleRows.forEach(row => {
                const playsText = row.querySelector('.badge.bg-info').textContent.replace(/,/g, '');
                totalPlays += parseInt(playsText) || 0;
                
                const artistCell = row.cells[2];
                if (artistCell) {
                    artists.add(artistCell.textContent.trim());
                }
            });
            
            totalPlaysElement.textContent = totalPlays.toLocaleString();
            uniqueArtistsElement.textContent = artists.size;
        }
    }

    // Update stats on page load
    updateStats();

    console.log('Admin tracks page enhanced with cover image fixes and interactive features');
});

// Debug function to check image URLs
function debugImageURL(trackId) {
    const img = document.querySelector(`tr[data-track-id="${trackId}"] .track-cover-img`);
    if (img) {
        console.log('Image URL:', img.src);
        console.log('Image complete:', img.complete);
        console.log('Image naturalWidth:', img.naturalWidth);
        console.log('Image naturalHeight:', img.naturalHeight);
    }
}

// Quick Edit functionality
function openQuickEdit(trackId) {
    // Find the track data from the table row
    const row = document.querySelector(`tr[data-track-id="${trackId}"]`);
    if (!row) {
        console.error('Track row not found');
        return;
    }
    
    // Extract data from the row
    const title = row.querySelector('td:nth-child(2)').textContent.trim();
    const artist = row.querySelector('td:nth-child(3)').textContent.trim();
    const album = row.querySelector('td:nth-child(4)').textContent.trim();
    const language = row.querySelector('td:nth-child(5)').textContent.trim();
    const mood = row.querySelector('td:nth-child(6)').textContent.trim();
    
    // Populate the modal
    document.getElementById('quick_track_id').value = trackId;
    document.getElementById('quick_title').value = title;
    document.getElementById('quick_artist').value = artist;
    document.getElementById('quick_album').value = album;
    
    // Set language dropdown
    const languageSelect = document.getElementById('quick_language');
    for (let option of languageSelect.options) {
        if (option.text === language) {
            option.selected = true;
            break;
        }
    }
    
    // Set mood dropdown
    const moodSelect = document.getElementById('quick_mood');
    for (let option of moodSelect.options) {
        if (option.text === mood) {
            option.selected = true;
            break;
        }
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('quickEditModal'));
    modal.show();
}

function saveQuickEdit() {
    const form = document.getElementById('quickEditForm');
    const formData = new FormData(form);
    
    // Add action parameter
    formData.append('action', 'quick_update');
    
    fetch('edit_track.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the table row with new data
            updateTableRow(formData.get('track_id'), {
                title: formData.get('title'),
                artist: formData.get('artist'),
                album: formData.get('album'),
                language: document.getElementById('quick_language').selectedOptions[0].text,
                mood: document.getElementById('quick_mood').selectedOptions[0].text
            });
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('quickEditModal')).hide();
            
            // Show success message
            showToast('Track updated successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to update track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating the track', 'error');
    });
}

function updateTableRow(trackId, data) {
    const row = document.querySelector(`tr[data-track-id="${trackId}"]`);
    if (row) {
        row.querySelector('td:nth-child(2)').textContent = data.title;
        row.querySelector('td:nth-child(3)').textContent = data.artist;
        row.querySelector('td:nth-child(4)').textContent = data.album;
        row.querySelector('td:nth-child(5)').textContent = data.language;
        row.querySelector('td:nth-child(6)').textContent = data.mood;
    }
}

function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Bulk Edit Functions
let selectedTracks = new Set();

function toggleTrackSelection(trackId, checkbox) {
    if (checkbox.checked) {
        selectedTracks.add(trackId);
    } else {
        selectedTracks.delete(trackId);
    }
    updateBulkEditButton();
}

function toggleAllTracks(selectAllCheckbox) {
    const trackCheckboxes = document.querySelectorAll('input[name="selected_tracks[]"]');
    selectedTracks.clear();
    
    trackCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
        if (selectAllCheckbox.checked) {
            selectedTracks.add(checkbox.value);
        }
    });
    
    updateBulkEditButton();
}

function updateBulkEditButton() {
    const bulkEditBtn = document.getElementById('bulkEditBtn');
    const selectedCount = selectedTracks.size;
    
    if (selectedCount > 0) {
        bulkEditBtn.style.display = 'inline-block';
        bulkEditBtn.innerHTML = `<i class="fas fa-edit me-2"></i>Edit ${selectedCount} Track${selectedCount > 1 ? 's' : ''}`;
    } else {
        bulkEditBtn.style.display = 'none';
    }
}

function openBulkEdit() {
    if (selectedTracks.size === 0) {
        showToast('Please select tracks to edit', 'warning');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('bulkEditModal'));
    modal.show();
}

function saveBulkEdit() {
    const form = document.getElementById('bulkEditForm');
    const formData = new FormData(form);
    
    // Add selected tracks
    selectedTracks.forEach(trackId => {
        formData.append('track_ids[]', trackId);
    });
    
    formData.append('action', 'bulk_update');
    
    fetch('edit_track.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show updated data
            location.reload();
        } else {
            showToast(data.message || 'Failed to update tracks', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating tracks', 'error');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
