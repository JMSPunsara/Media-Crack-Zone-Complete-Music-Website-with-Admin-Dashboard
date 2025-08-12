<?php
require_once '../config.php';
require_once '../includes/auto_sitemap.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $artist = sanitize($_POST['artist']);
    $album = sanitize($_POST['album']);
    $language_id = (int)$_POST['language_id'];
    $mood_id = (int)$_POST['mood_id'];
    
    if (empty($title) || empty($artist) || !isset($_FILES['music_file'])) {
        $error = 'Title, artist, and music file are required.';
    } else {
        // Upload music file
        $music_file = uploadFile($_FILES['music_file'], 'music');
        if (!$music_file) {
            $error = 'Invalid music file format. Please upload MP3, WAV, OGG, or M4A files.';
        } else {
            // Upload cover image if provided
            $cover_image = 'default-cover.jpg';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['size'] > 0) {
                $uploaded_cover = uploadFile($_FILES['cover_image'], 'covers');
                if ($uploaded_cover) {
                    $cover_image = $uploaded_cover;
                }
            }
            
            // Get duration
            $duration = getAudioDuration(UPLOAD_PATH . 'music/' . $music_file);
            
            // Insert track
            $stmt = $pdo->prepare("
                INSERT INTO tracks (title, artist, album, file_path, cover_image, duration, language_id, mood_id, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$title, $artist, $album, $music_file, $cover_image, $duration, $language_id ?: null, $mood_id ?: null, $_SESSION['user_id']])) {
                $success = 'Track uploaded successfully!';
                
                // Trigger automatic sitemap update
                SitemapHooks::onTrackChange();
                
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Failed to save track information.';
                // Delete uploaded files
                if (file_exists(UPLOAD_PATH . 'music/' . $music_file)) {
                    unlink(UPLOAD_PATH . 'music/' . $music_file);
                }
                if ($cover_image !== 'default-cover.jpg' && file_exists(UPLOAD_PATH . 'covers/' . $cover_image)) {
                    unlink(UPLOAD_PATH . 'covers/' . $cover_image);
                }
            }
        }
    }
}

// Get languages and moods
$stmt = $pdo->query("SELECT * FROM languages ORDER BY name");
$languages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM moods ORDER BY name");
$moods = $stmt->fetchAll();

$pageTitle = 'Upload Music';
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-upload me-2 text-primary"></i>Upload New Track
                </h2>
                <p class="text-white-75">Add a new music track to the platform</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <div class="mt-2">
                        <a href="upload.php" class="btn btn-success btn-sm me-2">Upload Another</a>
                        <a href="tracks.php" class="btn btn-outline-success btn-sm">View All Tracks</a>
                    </div>
                </div>
            <?php else: ?>
            
            <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Track Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="artist" class="form-label">Artist *</label>
                        <input type="text" class="form-control" id="artist" name="artist" 
                               value="<?php echo isset($_POST['artist']) ? htmlspecialchars($_POST['artist']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="album" class="form-label">Album (Optional)</label>
                    <input type="text" class="form-control" id="album" name="album" 
                           value="<?php echo isset($_POST['album']) ? htmlspecialchars($_POST['album']) : ''; ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="language_id" class="form-label">Language</label>
                        <select class="form-control" id="language_id" name="language_id">
                            <option value="">Select Language</option>
                            <?php foreach ($languages as $language): ?>
                            <option value="<?php echo $language['id']; ?>" 
                                    <?php echo (isset($_POST['language_id']) && $_POST['language_id'] == $language['id']) ? 'selected' : ''; ?>>
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
                                    <?php echo (isset($_POST['mood_id']) && $_POST['mood_id'] == $mood['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mood['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="music_file" class="form-label">Music File *</label>
                    <input type="file" class="form-control" id="music_file" name="music_file" 
                           accept=".mp3,.wav,.ogg,.m4a" required>
                    <div class="form-text">Supported formats: MP3, WAV, OGG, M4A (Max size: 50MB)</div>
                </div>
                
                <div class="mb-4">
                    <label for="cover_image" class="form-label">Cover Image (Optional)</label>
                    <input type="file" class="form-control" id="cover_image" name="cover_image" 
                           accept=".jpg,.jpeg,.png,.gif">
                    <div class="form-text">Supported formats: JPG, PNG, GIF (Max size: 5MB)</div>
                </div>
                
                <!-- Upload Progress -->
                <div class="mb-4" id="uploadProgress" style="display: none;">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Uploading...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg" id="uploadBtn">
                        <i class="fas fa-upload me-2"></i>Upload Track
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary btn-lg ms-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const musicFile = document.getElementById('music_file').files[0];
    const coverFile = document.getElementById('cover_image').files[0];
    
    // Check file sizes
    if (musicFile && musicFile.size > 50 * 1024 * 1024) { // 50MB
        e.preventDefault();
        alert('Music file size must be less than 50MB');
        return;
    }
    
    if (coverFile && coverFile.size > 5 * 1024 * 1024) { // 5MB
        e.preventDefault();
        alert('Cover image size must be less than 5MB');
        return;
    }
    
    // Show progress (simulation)
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadBtn = document.getElementById('uploadBtn');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    
    uploadProgress.style.display = 'block';
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="loading-spinner me-2"></i>Uploading...';
    
    // Simulate upload progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress > 90) progress = 90;
        
        progressBar.style.width = progress + '%';
        progressPercent.textContent = Math.round(progress) + '%';
    }, 200);
    
    // Clear interval when form actually submits
    setTimeout(() => {
        clearInterval(interval);
        progressBar.style.width = '100%';
        progressPercent.textContent = '100%';
    }, 2000);
});

// Auto-fill metadata from filename
document.getElementById('music_file').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const filename = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
        const titleInput = document.getElementById('title');
        const artistInput = document.getElementById('artist');
        
        // Try to parse "Artist - Title" format
        if (filename.includes(' - ') && !titleInput.value) {
            const parts = filename.split(' - ');
            if (parts.length >= 2) {
                artistInput.value = parts[0].trim();
                titleInput.value = parts.slice(1).join(' - ').trim();
            }
        } else if (!titleInput.value) {
            titleInput.value = filename;
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
