<?php
require_once '../config.php';

// Check if user is admin (you'll need to implement admin authentication)
// For now, we'll assume this is protected by your admin system

$pageTitle = 'SEO Management';
$pageDescription = 'Manage SEO settings and automatically optimize all music tracks for search engines.';

// Handle bulk SEO generation
if ($_POST['action'] === 'generate_bulk_seo') {
    $result = MusicSEO::bulkGenerateSEO($pdo);
    $message = $result['success'] ? $result['message'] : 'Error: ' . $result['error'];
    $messageType = $result['success'] ? 'success' : 'danger';
}

// Handle individual track SEO update
if ($_POST['action'] === 'update_track_seo' && isset($_POST['track_id'])) {
    $trackId = (int)$_POST['track_id'];
    $seoTitle = $_POST['seo_title'];
    $seoDescription = $_POST['seo_description'];
    $seoKeywords = $_POST['seo_keywords'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tracks SET 
                seo_title = ?,
                seo_description = ?,
                seo_keywords = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$seoTitle, $seoDescription, $seoKeywords, $trackId]);
        
        $message = "SEO data updated successfully for track ID: $trackId";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Error updating SEO data: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get tracks with SEO data
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name,
           CASE WHEN t.seo_title IS NOT NULL AND t.seo_title != '' THEN 1 ELSE 0 END as has_seo
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$tracks = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->query("SELECT COUNT(*) as total FROM tracks");
$totalTracks = $countStmt->fetch()['total'];
$totalPages = ceil($totalTracks / $limit);

// Get SEO statistics
$seoStats = $pdo->query("
    SELECT 
        COUNT(*) as total_tracks,
        SUM(CASE WHEN seo_title IS NOT NULL AND seo_title != '' THEN 1 ELSE 0 END) as tracks_with_seo,
        SUM(CASE WHEN seo_title IS NULL OR seo_title = '' THEN 1 ELSE 0 END) as tracks_without_seo
    FROM tracks
")->fetch();

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-white">SEO Management</h1>
                    <p class="text-white-75 mb-0">Optimize your music platform for search engines</p>
                </div>
                <a href="../" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Site
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($message)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SEO Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-custom p-4 text-center">
                <i class="fas fa-music text-primary mb-3" style="font-size: 2rem;"></i>
                <h4 class="text-white mb-1"><?php echo number_format($seoStats['total_tracks']); ?></h4>
                <p class="text-white-75 mb-0">Total Tracks</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-4 text-center">
                <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                <h4 class="text-white mb-1"><?php echo number_format($seoStats['tracks_with_seo']); ?></h4>
                <p class="text-white-75 mb-0">SEO Optimized</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-4 text-center">
                <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
                <h4 class="text-white mb-1"><?php echo number_format($seoStats['tracks_without_seo']); ?></h4>
                <p class="text-white-75 mb-0">Needs SEO</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-4 text-center">
                <i class="fas fa-percentage text-info mb-3" style="font-size: 2rem;"></i>
                <h4 class="text-white mb-1"><?php echo $seoStats['total_tracks'] > 0 ? round(($seoStats['tracks_with_seo'] / $seoStats['total_tracks']) * 100, 1) : 0; ?>%</h4>
                <p class="text-white-75 mb-0">SEO Coverage</p>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-custom p-4">
                <h5 class="text-white mb-3">
                    <i class="fas fa-bolt me-2"></i>Bulk SEO Actions
                </h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="generate_bulk_seo">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('This will update SEO data for all tracks. Continue?')">
                                <i class="fas fa-magic me-2"></i>Generate SEO for All Tracks
                            </button>
                        </form>
                        <p class="small text-white-75 mt-2 mb-0">Automatically generates optimized SEO titles, descriptions, and keywords for all tracks.</p>
                    </div>
                    
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success" onclick="generateSitemap()">
                            <i class="fas fa-sitemap me-2"></i>Generate Sitemap
                        </button>
                        <p class="small text-white-75 mt-2 mb-0">Creates an XML sitemap for search engines to index your content.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracks SEO Management -->
    <div class="row">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <h5 class="text-white mb-0">
                        <i class="fas fa-list me-2"></i>Tracks SEO Management
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Track</th>
                                    <th>SEO Status</th>
                                    <th>SEO Title</th>
                                    <th>SEO Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tracks as $track): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo UPLOAD_URL . 'covers/' . ($track['cover_image'] ?: 'default-cover.jpg'); ?>" 
                                                 alt="Cover" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <div class="text-white fw-semibold"><?php echo htmlspecialchars($track['title']); ?></div>
                                                <div class="text-white-75 small"><?php echo htmlspecialchars($track['artist']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($track['has_seo']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Optimized
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation me-1"></i>Needs SEO
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-white small" style="max-width: 200px;">
                                            <?php echo $track['seo_title'] ? htmlspecialchars(substr($track['seo_title'], 0, 50)) . '...' : '<em class="text-white-50">Not set</em>'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-white small" style="max-width: 250px;">
                                            <?php echo $track['seo_description'] ? htmlspecialchars(substr($track['seo_description'], 0, 80)) . '...' : '<em class="text-white-50">Not set</em>'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editTrackSEO(<?php echo htmlspecialchars(json_encode($track)); ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit SEO
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- SEO Edit Modal -->
<div class="modal fade" id="seoEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">Edit Track SEO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="seoEditForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_track_seo">
                    <input type="hidden" name="track_id" id="editTrackId">
                    
                    <div class="mb-3">
                        <label class="form-label text-white">SEO Title</label>
                        <input type="text" class="form-control" name="seo_title" id="editSeoTitle" maxlength="255">
                        <div class="form-text">Optimal length: 50-60 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white">SEO Description</label>
                        <textarea class="form-control" name="seo_description" id="editSeoDescription" rows="3" maxlength="320"></textarea>
                        <div class="form-text">Optimal length: 150-160 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white">SEO Keywords</label>
                        <textarea class="form-control" name="seo_keywords" id="editSeoKeywords" rows="2"></textarea>
                        <div class="form-text">Separate keywords with commas</div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-secondary" onclick="generateAutoSEO()">
                            <i class="fas fa-magic me-1"></i>Auto-Generate SEO
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save SEO Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentEditTrack = null;

function editTrackSEO(track) {
    currentEditTrack = track;
    
    document.getElementById('editTrackId').value = track.id;
    document.getElementById('editSeoTitle').value = track.seo_title || '';
    document.getElementById('editSeoDescription').value = track.seo_description || '';
    document.getElementById('editSeoKeywords').value = track.seo_keywords || '';
    
    new bootstrap.Modal(document.getElementById('seoEditModal')).show();
}

function generateAutoSEO() {
    if (!currentEditTrack) return;
    
    const track = currentEditTrack;
    
    // Auto-generate SEO title
    const title = `${track.title} by ${track.artist} - Stream & Download`;
    document.getElementById('editSeoTitle').value = title;
    
    // Auto-generate SEO description
    let description = `Listen to '${track.title}' by ${track.artist} on Media Crack Zone. `;
    if (track.album) {
        description += `From the album '${track.album}'. `;
    }
    if (track.mood_name) {
        description += `Perfect for ${track.mood_name} moments. `;
    }
    description += `Stream, download, and add to your playlist.`;
    document.getElementById('editSeoDescription').value = description;
    
    // Auto-generate keywords
    let keywords = [
        track.title,
        track.artist,
        `${track.title} song`,
        `${track.artist} songs`,
        `download ${track.title}`,
        `stream ${track.title}`
    ];
    
    if (track.album) {
        keywords.push(track.album);
    }
    if (track.language_name) {
        keywords.push(`${track.language_name} music`);
    }
    if (track.mood_name) {
        keywords.push(`${track.mood_name} music`);
    }
    
    document.getElementById('editSeoKeywords').value = keywords.join(', ');
}

function generateSitemap() {
    if (confirm('Generate XML sitemap for search engines?')) {
        fetch('generate_sitemap.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sitemap generated successfully!');
            } else {
                alert('Error generating sitemap: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

// Character count indicators
document.getElementById('editSeoTitle').addEventListener('input', function() {
    const length = this.value.length;
    const optimal = length >= 50 && length <= 60;
    this.style.borderColor = optimal ? '#28a745' : (length > 60 ? '#dc3545' : '#ffc107');
});

document.getElementById('editSeoDescription').addEventListener('input', function() {
    const length = this.value.length;
    const optimal = length >= 150 && length <= 160;
    this.style.borderColor = optimal ? '#28a745' : (length > 160 ? '#dc3545' : '#ffc107');
});
</script>

<?php include '../includes/footer.php'; ?>
