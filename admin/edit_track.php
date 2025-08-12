<?php
require_once '../config.php';
require_once '../includes/auto_sitemap.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

// Handle AJAX requests for quick edit and bulk edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Quick Edit
    if (isset($_POST['action']) && $_POST['action'] == 'quick_update') {
        header('Content-Type: application/json');
        
        $track_id = (int)$_POST['track_id'];
        $title = sanitize($_POST['title']);
        $artist = sanitize($_POST['artist']);
        $album = sanitize($_POST['album']);
        $language_id = $_POST['language_id'] ? (int)$_POST['language_id'] : null;
        $mood_id = $_POST['mood_id'] ? (int)$_POST['mood_id'] : null;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE tracks 
                SET title = ?, artist = ?, album = ?, language_id = ?, mood_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $artist, $album, $language_id, $mood_id, $track_id]);
            
            // Trigger automatic sitemap update
            SitemapHooks::onTrackChange();
            
            echo json_encode(['success' => true, 'message' => 'Track updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update track: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Bulk Edit
    if (isset($_POST['action']) && $_POST['action'] == 'bulk_update') {
        header('Content-Type: application/json');
        
        $track_ids = $_POST['track_ids'] ?? [];
        $language_id = $_POST['language_id'] ? (int)$_POST['language_id'] : null;
        $mood_id = $_POST['mood_id'] ? (int)$_POST['mood_id'] : null;
        $album = sanitize($_POST['album']);
        
        if (empty($track_ids)) {
            echo json_encode(['success' => false, 'message' => 'No tracks selected']);
            exit;
        }
        
        try {
            // Build dynamic update query
            $updates = [];
            $params = [];
            
            if ($language_id) {
                $updates[] = "language_id = ?";
                $params[] = $language_id;
            }
            
            if ($mood_id) {
                $updates[] = "mood_id = ?";
                $params[] = $mood_id;
            }
            
            if (!empty($album)) {
                $updates[] = "album = ?";
                $params[] = $album;
            }
            
            if (empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            // Add track IDs to params
            $placeholders = str_repeat('?,', count($track_ids) - 1) . '?';
            $params = array_merge($params, $track_ids);
            
            $sql = "UPDATE tracks SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $updated_count = $stmt->rowCount();
            echo json_encode(['success' => true, 'message' => "Updated $updated_count tracks successfully"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update tracks: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Get track ID
$track_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$track_id) {
    redirect('tracks.php');
}

// Get track information
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color 
    FROM tracks t 
    LEFT JOIN languages l ON t.language_id = l.id 
    LEFT JOIN moods m ON t.mood_id = m.id 
    WHERE t.id = ?
");
$stmt->execute([$track_id]);
$track = $stmt->fetch();

if (!$track) {
    redirect('tracks.php');
}

// Get languages and moods for dropdowns
$stmt = $pdo->query("SELECT * FROM languages ORDER BY name");
$languages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM moods ORDER BY name");
$moods = $stmt->fetchAll();

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_track'])) {
        $title = sanitize($_POST['title']);
        $artist = sanitize($_POST['artist']);
        $album = sanitize($_POST['album']);
        $language_id = $_POST['language_id'] ? (int)$_POST['language_id'] : null;
        $mood_id = $_POST['mood_id'] ? (int)$_POST['mood_id'] : null;
        $duration = $_POST['duration'] ? (int)$_POST['duration'] : null;
        
        // Validate required fields
        if (empty($title) || empty($artist)) {
            $error_message = "Title and Artist are required fields.";
        } else {
            try {
                // Handle cover image upload
                $cover_filename = $track['cover_image'];
                
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
                    $upload_result = uploadFile($_FILES['cover_image'], 'covers');
                    
                    if ($upload_result['success']) {
                        // Delete old cover image (if not default)
                        if ($track['cover_image'] && $track['cover_image'] !== 'default-cover.jpg') {
                            $old_cover = UPLOAD_PATH . 'covers/' . $track['cover_image'];
                            if (file_exists($old_cover)) {
                                unlink($old_cover);
                            }
                        }
                        $cover_filename = $upload_result['filename'];
                    } else {
                        $error_message = $upload_result['error'];
                    }
                }
                
                if (!$error_message) {
                    // Update track in database
                    $stmt = $pdo->prepare("
                        UPDATE tracks 
                        SET title = ?, artist = ?, album = ?, language_id = ?, mood_id = ?, 
                            cover_image = ?, duration = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $title, $artist, $album, $language_id, $mood_id, 
                        $cover_filename, $duration, $track_id
                    ]);
                    
                    $success_message = "Track updated successfully!";
                    
                    // Refresh track data
                    $stmt = $pdo->prepare("
                        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color 
                        FROM tracks t 
                        LEFT JOIN languages l ON t.language_id = l.id 
                        LEFT JOIN moods m ON t.mood_id = m.id 
                        WHERE t.id = ?
                    ");
                    $stmt->execute([$track_id]);
                    $track = $stmt->fetch();
                }
                
            } catch (Exception $e) {
                $error_message = "An error occurred while updating the track: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Track - ' . htmlspecialchars($track['title']);
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold">
            <i class="fas fa-edit me-2 text-warning"></i>Edit Track
        </h1>
        <p class="text-muted mb-0">Modify track details and cover image</p>
    </div>
    <div>
        <a href="tracks.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tracks
        </a>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Track Details Form -->
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <h4 class="mb-4">
                <i class="fas fa-music me-2"></i>Track Information
            </h4>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($track['title']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="artist" class="form-label">Artist <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="artist" name="artist" 
                               value="<?php echo htmlspecialchars($track['artist']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="album" class="form-label">Album</label>
                        <input type="text" class="form-control" id="album" name="album" 
                               value="<?php echo htmlspecialchars($track['album']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="duration" class="form-label">Duration (seconds)</label>
                        <input type="number" class="form-control" id="duration" name="duration" 
                               value="<?php echo $track['duration']; ?>" min="1">
                        <div class="form-text">
                            Current: <?php echo $track['duration'] ? formatDuration($track['duration']) : 'Not set'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="language_id" class="form-label">Language</label>
                        <select class="form-control" id="language_id" name="language_id">
                            <option value="">Select Language</option>
                            <?php foreach ($languages as $language): ?>
                            <option value="<?php echo $language['id']; ?>" 
                                    <?php echo $track['language_id'] == $language['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($language['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="mood_id" class="form-label">Mood</label>
                        <select class="form-control" id="mood_id" name="mood_id">
                            <option value="">Select Mood</option>
                            <?php foreach ($moods as $mood): ?>
                            <option value="<?php echo $mood['id']; ?>" 
                                    <?php echo $track['mood_id'] == $mood['id'] ? 'selected' : ''; ?>
                                    data-color="<?php echo $mood['color']; ?>">
                                <?php echo htmlspecialchars($mood['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="cover_image" class="form-label">Cover Image</label>
                    <input type="file" class="form-control" id="cover_image" name="cover_image" 
                           accept="image/*" onchange="previewImage(this)">
                    <div class="form-text">
                        Upload a new cover image to replace the current one. Supported formats: JPG, PNG, GIF. Max size: 5MB.
                    </div>
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" name="update_track" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Update Track
                    </button>
                    <a href="tracks.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Current Track Info -->
    <div class="col-lg-4">
        <div class="card-custom p-4">
            <h4 class="mb-4">
                <i class="fas fa-info-circle me-2"></i>Current Track
            </h4>
            
            <!-- Cover Image Preview -->
            <div class="text-center mb-4">
                <div class="cover-preview-container">
                    <?php 
                    $cover_image = $track['cover_image'];
                    if (empty($cover_image) || $cover_image === 'default-cover.jpg') {
                        $cover_url = 'data:image/svg+xml;base64,' . base64_encode('
                            <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#1db954"/>
                                        <stop offset="100%" style="stop-color:#191414"/>
                                    </linearGradient>
                                </defs>
                                <rect width="200" height="200" fill="url(#grad)" rx="12"/>
                                <circle cx="100" cy="80" r="25" fill="white" opacity="0.8"/>
                                <rect x="70" y="120" width="60" height="8" rx="4" fill="white" opacity="0.8"/>
                                <rect x="75" y="135" width="50" height="6" rx="3" fill="white" opacity="0.6"/>
                                <rect x="80" y="148" width="40" height="6" rx="3" fill="white" opacity="0.6"/>
                                <text x="100" y="175" text-anchor="middle" fill="white" font-size="12" opacity="0.7">No Cover</text>
                            </svg>
                        ');
                    } else {
                        $cover_url = UPLOAD_URL . 'covers/' . $cover_image;
                    }
                    ?>
                    <img src="<?php echo $cover_url; ?>" 
                         alt="Track Cover" 
                         class="img-fluid rounded shadow cover-preview" 
                         id="coverPreview"
                         style="max-width: 200px; max-height: 200px; object-fit: cover;">
                </div>
            </div>
            
            <!-- Track Details -->
            <div class="track-details">
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Title:</label>
                    <div class="detail-value"><?php echo htmlspecialchars($track['title']); ?></div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Artist:</label>
                    <div class="detail-value"><?php echo htmlspecialchars($track['artist']); ?></div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Album:</label>
                    <div class="detail-value"><?php echo $track['album'] ? htmlspecialchars($track['album']) : 'Not specified'; ?></div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Language:</label>
                    <div class="detail-value">
                        <?php if ($track['language_name']): ?>
                            <span class="badge language-badge bg-secondary">
                                <?php echo htmlspecialchars($track['language_name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Mood:</label>
                    <div class="detail-value">
                        <?php if ($track['mood_name']): ?>
                            <?php
                            // Calculate text color based on background brightness
                            $mood_color = isset($track['mood_color']) ? $track['mood_color'] : '#6c757d';
                            $text_color = '#ffffff'; // default white
                            
                            if ($mood_color) {
                                // Remove # if present
                                $hex = ltrim($mood_color, '#');
                                
                                // Convert to RGB
                                if (strlen($hex) == 6) {
                                    $r = hexdec(substr($hex, 0, 2));
                                    $g = hexdec(substr($hex, 2, 2));
                                    $b = hexdec(substr($hex, 4, 2));
                                    
                                    // Calculate brightness (0-255)
                                    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                    
                                    // Use dark text for bright backgrounds
                                    if ($brightness > 128) {
                                        $text_color = '#000000';
                                    }
                                }
                            }
                            ?>
                            <span class="badge mood-badge" 
                                  style="background-color: <?php echo htmlspecialchars($mood_color); ?>; color: <?php echo $text_color; ?>; border: 1px solid rgba(255,255,255,0.2);">
                                <?php echo htmlspecialchars($track['mood_name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Duration:</label>
                    <div class="detail-value"><?php echo $track['duration'] ? formatDuration($track['duration']) : 'Not specified'; ?></div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Plays:</label>
                    <div class="detail-value">
                        <span class="badge plays-badge bg-info">
                            <?php echo number_format($track['plays_count']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Uploaded:</label>
                    <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($track['created_at'])); ?></div>
                </div>
                
                <?php if ($track['updated_at']): ?>
                <div class="detail-item mb-3">
                    <label class="fw-bold text-muted">Last Updated:</label>
                    <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($track['updated_at'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-4">
                <h6 class="fw-bold mb-3">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="../browse.php?search=<?php echo urlencode($track['title']); ?>" 
                       class="btn btn-sm btn-outline-info" target="_blank">
                        <i class="fas fa-eye me-2"></i>View in Browse
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="playTrackPreview()">
                        <i class="fas fa-play me-2"></i>Preview Track
                    </button>
                    <a href="tracks.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-custom {
    background: linear-gradient(135deg, rgba(25, 25, 25, 0.95), rgba(40, 40, 40, 0.95));
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.form-control {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.2);
    color: var(--text-white);
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: var(--primary-color);
    color: var(--text-white);
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
}

.form-control::placeholder {
    color: var(--text-light);
    opacity: 0.8;
}

.form-control option {
    background-color: #2c2c2c;
    color: #ffffff;
}

.form-label {
    color: var(--text-white);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-text {
    color: var(--text-light);
    font-size: 0.875rem;
    opacity: 0.9;
}

.text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

.cover-preview-container {
    position: relative;
    display: inline-block;
}

.cover-preview {
    border: 2px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.cover-preview:hover {
    border-color: var(--primary-color);
    box-shadow: 0 8px 25px rgba(29, 185, 84, 0.3);
}

.detail-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.7) !important;
}

.detail-value {
    margin-top: 0.25rem;
    color: var(--text-white) !important;
    font-weight: 500;
    min-height: 1.5rem;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #ff8c00);
    border: none;
    color: #000;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #ff8c00, #ffc107);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
}

/* Enhanced mood badge styling */
.mood-badge {
    font-size: 0.8rem;
    padding: 0.4em 0.8em;
    border-radius: 12px;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mood-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

/* Language badge styling */
.language-badge {
    background-color: rgba(108, 117, 125, 0.9) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-weight: 600;
    padding: 0.4em 0.8em;
    border-radius: 12px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

/* Plays badge styling */
.plays-badge {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-weight: 700;
    padding: 0.4em 0.8em;
    border-radius: 12px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.4);
}

/* Improve general text contrast */
.text-white {
    color: #ffffff !important;
}

/* Alert styling for better visibility */
.alert {
    border: none;
    border-radius: 8px;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(25, 135, 84, 0.2));
    color: #d4edda;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(185, 28, 47, 0.2));
    color: #f8d7da;
    border-left: 4px solid #dc3545;
}

/* Better visibility for form elements */
.form-control:disabled {
    background-color: rgba(255, 255, 255, 0.02);
    color: rgba(255, 255, 255, 0.5);
}

.form-control.is-valid {
    border-color: #28a745;
    background-color: rgba(40, 167, 69, 0.1);
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

/* Required field indicator */
.text-danger {
    color: #ff6b6b !important;
    font-weight: bold;
}

/* Better select dropdown visibility */
select.form-control {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}

/* Image upload preview styles */
.image-upload-preview {
    max-width: 150px;
    max-height: 150px;
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    margin-top: 1rem;
    display: none;
}

.image-upload-preview.show {
    display: block;
}

.image-upload-preview img {
    max-width: 100%;
    border-radius: 4px;
}

/* Form animations */
.form-control {
    transition: all 0.3s ease;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .cover-preview {
        max-width: 150px;
        max-height: 150px;
    }
    
    .d-flex.gap-3 {
        flex-direction: column;
        gap: 1rem !important;
    }
}
</style>

<script>
// Image preview functionality
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('coverPreview');
            preview.src = e.target.result;
            
            // Add animation
            preview.style.opacity = '0';
            setTimeout(() => {
                preview.style.opacity = '1';
                preview.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    preview.style.transform = 'scale(1)';
                }, 200);
            }, 100);
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const titleInput = document.getElementById('title');
    const artistInput = document.getElementById('artist');
    const coverInput = document.getElementById('cover_image');
    
    // Real-time validation
    function validateField(input, minLength = 1) {
        const value = input.value.trim();
        const isValid = value.length >= minLength;
        
        input.classList.toggle('is-invalid', !isValid);
        input.classList.toggle('is-valid', isValid);
        
        return isValid;
    }
    
    titleInput.addEventListener('input', () => validateField(titleInput));
    artistInput.addEventListener('input', () => validateField(artistInput));
    
    // File size validation
    coverInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file.size > maxSize) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, or GIF)');
                this.value = '';
                return;
            }
            
            previewImage(this);
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const titleValid = validateField(titleInput);
        const artistValid = validateField(artistInput);
        
        if (!titleValid || !artistValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
    
    // Auto-save to localStorage for form recovery
    const formInputs = form.querySelectorAll('input[type="text"], input[type="number"], select');
    
    formInputs.forEach(input => {
        // Load saved values
        const savedValue = localStorage.getItem(`edit_track_${input.name}`);
        if (savedValue && !input.value) {
            input.value = savedValue;
        }
        
        // Save on change
        input.addEventListener('change', function() {
            localStorage.setItem(`edit_track_${this.name}`, this.value);
        });
    });
    
    // Clear localStorage on successful submit
    form.addEventListener('submit', function() {
        if (this.checkValidity()) {
            formInputs.forEach(input => {
                localStorage.removeItem(`edit_track_${input.name}`);
            });
        }
    });
});

// Track preview functionality
function playTrackPreview() {
    const trackId = <?php echo $track_id; ?>;
    const trackData = {
        id: trackId,
        title: "<?php echo addslashes($track['title']); ?>",
        artist: "<?php echo addslashes($track['artist']); ?>",
        file_path: "<?php echo UPLOAD_URL; ?>music/<?php echo $track['file_path']; ?>",
        cover_image: "<?php echo UPLOAD_URL; ?>covers/<?php echo $track['cover_image']; ?>"
    };
    
    if (typeof window.setPlaylist === 'function') {
        window.setPlaylist([trackData], 0);
        if (typeof showToast === 'function') {
            showToast('Playing track preview', 'info');
        }
    } else {
        alert('Music player not available');
    }
}

// Mood color preview
document.getElementById('mood_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const color = selectedOption.dataset.color;
    
    if (color) {
        this.style.borderColor = color;
        this.style.boxShadow = `0 0 0 0.2rem ${color}25`;
    } else {
        this.style.borderColor = '';
        this.style.boxShadow = '';
    }
});

console.log('Edit track page initialized');
</script>

<?php include '../includes/footer.php'; ?>
