<?php
require_once 'config.php';

// Smart contrast color calculation function
function calculateContrastColor($backgroundColor) {
    // Remove # if present
    $color = ltrim($backgroundColor, '#');
    
    // Convert to RGB
    $r = hexdec(substr($color, 0, 2));
    $g = hexdec(substr($color, 2, 2));
    $b = hexdec(substr($color, 4, 2));
    
    // Calculate luminance
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Return white for dark colors, black for light colors
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'My Profile';
$currentPage = 'profile';

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

// Initialize messages
$success_message = '';
$error_message = '';

// Check for avatar update success
if (isset($_GET['avatar_updated'])) {
    $success_message = "Avatar updated successfully!";
    // Clear the session message if it exists
    if (isset($_SESSION['success_message'])) {
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($full_name) || empty($email)) {
            $error_message = "Full name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $error_message = "Email address is already in use.";
            } else {
                $update_success = false;
                
                // Update basic profile info
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$full_name, $email, $_SESSION['user_id']])) {
                    $update_success = true;
                }
                
                // Handle password change if provided
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error_message = "Current password is required to change password.";
                        $update_success = false;
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error_message = "Current password is incorrect.";
                        $update_success = false;
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New passwords don't match.";
                        $update_success = false;
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                        $update_success = false;
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                            $success_message = "Profile and password updated successfully!";
                        } else {
                            $error_message = "Failed to update password.";
                            $update_success = false;
                        }
                    }
                } elseif ($update_success) {
                    $success_message = "Profile updated successfully!";
                }
                
                // Refresh user data
                if ($update_success || $success_message) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                }
            }
        }
    }
    
    // Handle avatar upload
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
        $avatar = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($avatar['error'] == UPLOAD_ERR_OK) {
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $detected_type = finfo_file($file_info, $avatar['tmp_name']);
            finfo_close($file_info);
            
            if (in_array($detected_type, $allowed_mime_types) && $avatar['size'] <= $max_size) {
                $extension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                $avatar_name = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($extension);
                $avatar_path = UPLOAD_PATH . 'avatars/' . $avatar_name;
                
                if (move_uploaded_file($avatar['tmp_name'], $avatar_path)) {
                    // Delete old avatar if it's not a default one
                    if ($user['avatar'] && 
                        !in_array($user['avatar'], ['default-avatar.png', 'default-avatar.svg']) && 
                        file_exists(UPLOAD_PATH . 'avatars/' . $user['avatar'])) {
                        unlink(UPLOAD_PATH . 'avatars/' . $user['avatar']);
                    }
                    
                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$avatar_name, $_SESSION['user_id']])) {
                        // Set success message for redirect
                        $_SESSION['success_message'] = "Avatar updated successfully!";
                        
                        // Force refresh of avatar URL to prevent caching issues
                        header("Location: " . $_SERVER['PHP_SELF'] . "?avatar_updated=" . time());
                        exit();
                    } else {
                        $error_message = "Failed to update avatar in database.";
                        // Clean up uploaded file
                        if (file_exists($avatar_path)) {
                            unlink($avatar_path);
                        }
                    }
                } else {
                    $error_message = "Failed to upload avatar file.";
                }
            } else {
                $error_message = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 5MB.";
            }
        } else {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File too large (exceeds server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large',
                UPLOAD_ERR_PARTIAL => 'File upload incomplete',
                UPLOAD_ERR_NO_FILE => 'No file selected',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: no temp directory',
                UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file',
                UPLOAD_ERR_EXTENSION => 'Server error: upload blocked'
            ];
            $error_message = $upload_errors[$avatar['error']] ?? "Unknown upload error: " . $avatar['error'];
        }
    }
}

// Re-fetch user data to ensure we have the latest avatar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get current avatar URL
$current_avatar_url = '';
$avatar_cache_buster = time(); // Always use current time for cache busting

if ($user['avatar'] && file_exists(UPLOAD_PATH . 'avatars/' . $user['avatar'])) {
    // Add cache-busting parameter to prevent browser caching issues
    $file_time = filemtime(UPLOAD_PATH . 'avatars/' . $user['avatar']);
    $current_avatar_url = UPLOAD_URL . 'avatars/' . $user['avatar'] . '?v=' . $file_time;
} else {
    // Use default avatar
    $current_avatar_url = UPLOAD_URL . 'avatars/default-avatar.svg?v=' . $avatar_cache_buster;
}

// Get user statistics with error handling
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorite_count = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT 
        COUNT(DISTINCT pl.track_id) as tracks_played,
        COALESCE(SUM(t.duration), 0) as total_listening_time
        FROM play_history pl 
        JOIN tracks t ON pl.track_id = t.id 
        WHERE pl.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch() ?: ['tracks_played' => 0, 'total_listening_time' => 0];

    // Get recent favorites
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color
        FROM favorites f
        JOIN tracks t ON f.track_id = t.id
        LEFT JOIN languages l ON t.language_id = l.id
        LEFT JOIN moods m ON t.mood_id = m.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_favorites = $stmt->fetchAll() ?: [];

    // Get recently played tracks
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color, ph.played_at
        FROM play_history ph
        JOIN tracks t ON ph.track_id = t.id
        LEFT JOIN languages l ON t.language_id = l.id
        LEFT JOIN moods m ON t.mood_id = m.id
        WHERE ph.user_id = ?
        ORDER BY ph.played_at DESC
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_plays = $stmt->fetchAll() ?: [];

} catch (PDOException $e) {
    // Handle database errors gracefully
    $favorite_count = 0;
    $stats = ['tracks_played' => 0, 'total_listening_time' => 0];
    $recent_favorites = [];
    $recent_plays = [];
}

// Format listening time
$listening_hours = floor(($stats['total_listening_time'] ?? 0) / 3600);
$listening_minutes = floor((($stats['total_listening_time'] ?? 0) % 3600) / 60);
$formatted_listening_time = sprintf("%dh %dm", $listening_hours, $listening_minutes);

include 'includes/header.php';
?>

<div class="container-fluid profile-page">
    <!-- Enhanced Profile Header with Gradient Background -->
    <div class="profile-hero-section">
        <div class="hero-background">
            <div class="floating-elements">
                <div class="floating-element element-1"></div>
                <div class="floating-element element-2"></div>
                <div class="floating-element element-3"></div>
            </div>
        </div>
        
        <div class="container">
            <div class="profile-header-content">
                <div class="row align-items-center">
                    <div class="col-lg-3 col-md-4 text-center">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar-container" onclick="document.getElementById('avatar').click()">
                                <div class="avatar-ring"></div>
                                <img src="<?php echo $current_avatar_url; ?>" 
                                     alt="Profile Avatar" 
                                     class="profile-avatar" 
                                     id="profileAvatar"
                                     loading="lazy"
                                     onerror="this.src='<?php echo UPLOAD_URL; ?>avatars/default-avatar.svg?v=<?php echo time(); ?>'">
                                <div class="avatar-overlay">
                                    <div class="overlay-content">
                                        <i class="fas fa-camera"></i>
                                        <span class="overlay-text">Change Photo</span>
                                    </div>
                                </div>
                                <div class="avatar-status-indicator"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <div class="profile-info">
                            <h1 class="profile-name">
                                <span class="name-text"><?php echo htmlspecialchars(substr($user['full_name'] ?: $user['username'], 0, 25) . (strlen($user['full_name'] ?: $user['username']) > 25 ? '...' : '')); ?></span>
                                <div class="verified-badge">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </h1>
                            <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="profile-email">
                                <i class="fas fa-envelope me-2"></i>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <div class="profile-stats-grid">
                                <div class="stat-card-modern">
                                    <div class="stat-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($favorite_count); ?></h3>
                                        <p class="stat-label">Favorites</p>
                                    </div>
                                </div>
                                <div class="stat-card-modern">
                                    <div class="stat-icon">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($stats['tracks_played'] ?? 0); ?></h3>
                                        <p class="stat-label">Played</p>
                                    </div>
                                </div>
                                <div class="stat-card-modern">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $formatted_listening_time; ?></h3>
                                        <p class="stat-label">Listened</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content Section -->
    <div class="main-content-section">
        <div class="container">
            <div class="row g-4">
                <!-- Profile Settings Card -->
                <div class="col-xl-8 col-lg-7">
                    <div class="settings-card">
                        <div class="card-header-modern">
                            <div class="header-content">
                                <div class="header-icon">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <div class="header-text">
                                    <h3>Profile Settings</h3>
                                    <p>Manage your account information and preferences</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body-modern">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success-modern" role="alert">
                                    <div class="alert-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="alert-content">
                                        <h6>Success!</h6>
                                        <p><?php echo htmlspecialchars($success_message); ?></p>
                                    </div>
                                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if ($error_message): ?>
                                <div class="alert alert-error-modern" role="alert">
                                    <div class="alert-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="alert-content">
                                        <h6>Error!</h6>
                                        <p><?php echo htmlspecialchars($error_message); ?></p>
                                    </div>
                                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Avatar Upload Section -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-image me-2"></i>Profile Picture
                                </h4>
                                <form method="POST" enctype="multipart/form-data" class="avatar-upload-form" id="avatarForm">
                                    <div class="upload-area">
                                        <div class="upload-preview">
                                            <img src="<?php echo $current_avatar_url; ?>" alt="Avatar Preview" class="preview-image" id="avatarPreview">
                                        </div>
                                        <div class="upload-controls">
                                            <input type="file" 
                                                   class="file-input" 
                                                   id="avatar" 
                                                   name="avatar" 
                                                   accept="image/jpeg,image/png,image/gif"
                                                   required>
                                            <div class="upload-content">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <h5>Upload New Photo</h5>
                                                <p class="upload-text" id="file-name">Click to browse or drag and drop</p>
                                                <small class="upload-info">JPG, PNG, or GIF • Max 5MB</small>
                                            </div>
                                            <button type="submit" name="update_avatar" class="btn-upload" id="uploadAvatarBtn" disabled>
                                                <i class="fas fa-upload me-2"></i>
                                                Update Avatar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Profile Information Form -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h4>
                                <form method="POST" class="profile-form" id="profileForm">
                                    <div class="form-grid">
                                        <div class="input-group-modern">
                                            <label for="full_name" class="form-label-modern">
                                                <i class="fas fa-user"></i>
                                                Full Name
                                            </label>
                                            <input type="text" 
                                                   class="form-control-modern" 
                                                   id="full_name" 
                                                   name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                                   required
                                                   maxlength="100">
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label for="email" class="form-label-modern">
                                                <i class="fas fa-envelope"></i>
                                                Email Address
                                            </label>
                                            <input type="email" 
                                                   class="form-control-modern" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                   required
                                                   maxlength="100">
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label for="username" class="form-label-modern">
                                                <i class="fas fa-at"></i>
                                                Username
                                            </label>
                                            <input type="text" 
                                                   class="form-control-modern readonly" 
                                                   id="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                   readonly>
                                            <small class="form-help">Username cannot be changed</small>
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label for="joined_date" class="form-label-modern">
                                                <i class="fas fa-calendar"></i>
                                                Member Since
                                            </label>
                                            <input type="text" 
                                                   class="form-control-modern readonly" 
                                                   value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Password Change Section -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-shield-alt me-2"></i>Security Settings
                                </h4>
                                <form method="POST" class="password-form">
                                    <div class="password-grid">
                                        <div class="input-group-modern">
                                            <label for="current_password" class="form-label-modern">
                                                <i class="fas fa-key"></i>
                                                Current Password
                                            </label>
                                            <input type="password" 
                                                   class="form-control-modern" 
                                                   id="current_password" 
                                                   name="current_password"
                                                   autocomplete="current-password">
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label for="new_password" class="form-label-modern">
                                                <i class="fas fa-lock"></i>
                                                New Password
                                            </label>
                                            <input type="password" 
                                                   class="form-control-modern" 
                                                   id="new_password" 
                                                   name="new_password"
                                                   autocomplete="new-password"
                                                   minlength="6">
                                            <div class="password-strength" id="passwordStrength">
                                                <div class="strength-bar">
                                                    <div class="strength-fill" id="strengthFill"></div>
                                                </div>
                                                <span class="strength-text" id="strengthText">Enter new password</span>
                                            </div>
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label for="confirm_password" class="form-label-modern">
                                                <i class="fas fa-lock"></i>
                                                Confirm Password
                                            </label>
                                            <input type="password" 
                                                   class="form-control-modern" 
                                                   id="confirm_password" 
                                                   name="confirm_password"
                                                   autocomplete="new-password">
                                            <div class="password-match" id="passwordMatch"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_profile" class="btn-primary-modern">
                                            <i class="fas fa-save me-2"></i>
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Sidebar -->
                <div class="col-xl-4 col-lg-5">
                    <div class="sidebar-content">
                        <!-- Quick Stats Card -->
                        <div class="stats-card-modern">
                            <div class="card-header-modern">
                                <div class="header-content">
                                    <div class="header-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="header-text">
                                        <h4>Your Music Journey</h4>
                                        <p>Track your listening habits</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-item-modern">
                                    <div class="stat-visual">
                                        <div class="stat-circle" style="--percentage: <?php echo min(($favorite_count / 100) * 100, 100); ?>%">
                                            <span class="stat-number"><?php echo number_format($favorite_count); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-info">
                                        <h5>Favorites</h5>
                                        <p>Loved tracks</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item-modern">
                                    <div class="stat-visual">
                                        <div class="stat-circle" style="--percentage: <?php echo min((($stats['tracks_played'] ?? 0) / 50) * 100, 100); ?>%">
                                            <span class="stat-number"><?php echo number_format($stats['tracks_played'] ?? 0); ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-info">
                                        <h5>Played</h5>
                                        <p>Total tracks</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item-modern">
                                    <div class="stat-visual">
                                        <div class="stat-circle" style="--percentage: <?php echo min(($listening_hours / 24) * 100, 100); ?>%">
                                            <span class="stat-number"><?php echo $formatted_listening_time; ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-info">
                                        <h5>Time</h5>
                                        <p>Listening hours</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="actions-card-modern">
                            <div class="card-header-modern">
                                <div class="header-content">
                                    <div class="header-icon">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <div class="header-text">
                                        <h4>Quick Actions</h4>
                                        <p>Navigate to your favorite sections</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="actions-grid">
                                <a href="favorites.php" class="action-card">
                                    <div class="action-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="action-content">
                                        <h5>My Favorites</h5>
                                        <p>View your loved tracks</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </a>
                                
                                <a href="browse.php" class="action-card">
                                    <div class="action-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="action-content">
                                        <h5>Discover Music</h5>
                                        <p>Find new tracks</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </a>
                                
                                <a href="index.php" class="action-card">
                                    <div class="action-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="action-content">
                                        <h5>Home</h5>
                                        <p>Back to homepage</p>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Recent Activity Section -->
                <div class="row mt-4">
                    <!-- Recent Favorites -->
                    <div class="col-lg-6">
                        <div class="settings-card">
                            <div class="card-header-modern">
                                <div class="header-content">
                                    <div class="header-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="header-text">
                                        <h4>Recent Favorites</h4>
                                        <p>Your most recently loved tracks</p>
                                    </div>
                                </div>
                                <div class="header-actions">
                                    <a href="favorites.php" class="btn btn-outline-primary btn-sm">View All</a>
                                </div>
                            </div>
                            <div class="card-body-modern">
                                <?php if (empty($recent_favorites)): ?>
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-heart-broken"></i>
                                        </div>
                                        <h5>No Favorite Tracks</h5>
                                        <p>Start building your music collection by hearting tracks you love</p>
                                        <a href="browse.php" class="btn-primary-modern">
                                            <i class="fas fa-search me-2"></i>
                                            Discover Music
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="modern-track-list">
                                        <?php foreach ($recent_favorites as $index => $track): ?>
                                            <div class="modern-track-item" data-track-id="<?php echo $track['id']; ?>">
                                                <div class="track-cover-modern">
                                                    <?php 
                                                    $cover_url = ($track['cover_image'] && file_exists(UPLOAD_PATH . 'covers/' . $track['cover_image'])) ? 
                                                        UPLOAD_URL . 'covers/' . $track['cover_image'] : 
                                                        UPLOAD_URL . 'covers/default-cover.jpg';
                                                    ?>
                                                    <img src="<?php echo $cover_url; ?>" 
                                                         alt="<?php echo htmlspecialchars($track['title']); ?>" 
                                                         loading="lazy">
                                                    <div class="play-overlay" onclick="playTrack(<?php echo htmlspecialchars(json_encode($track)); ?>)">
                                                        <i class="fas fa-play"></i>
                                                    </div>
                                                </div>
                                                <div class="track-details-modern">
                                                    <h6 class="track-title"><?php echo htmlspecialchars(substr($track['title'], 0, 30) . (strlen($track['title']) > 30 ? '...' : '')); ?></h6>
                                                    <p class="track-artist"><?php echo htmlspecialchars(substr($track['artist'], 0, 25) . (strlen($track['artist']) > 25 ? '...' : '')); ?></p>
                                                    <?php if ($track['mood_name']): ?>
                                                        <span class="modern-mood-badge" 
                                                              style="background-color: <?php echo htmlspecialchars($track['mood_color']); ?>; 
                                                                     color: <?php echo calculateContrastColor($track['mood_color']); ?>">
                                                            <?php echo htmlspecialchars($track['mood_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="track-actions-modern">
                                                    <button class="action-btn play-btn" 
                                                            onclick="playTrack(<?php echo htmlspecialchars(json_encode($track)); ?>)"
                                                            title="Play Track">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="action-btn favorite-btn active" 
                                                            onclick="toggleFavorite(<?php echo $track['id']; ?>)"
                                                            title="Remove from Favorites">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recently Played -->
                    <div class="col-lg-6">
                        <div class="settings-card">
                            <div class="card-header-modern">
                                <div class="header-content">
                                    <div class="header-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div class="header-text">
                                        <h4>Recently Played</h4>
                                        <p>Your latest listening activity</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-modern">
                                <?php if (empty($recent_plays)): ?>
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-music"></i>
                                        </div>
                                        <h5>No Listening History</h5>
                                        <p>Your recently played tracks will appear here once you start listening</p>
                                        <a href="browse.php" class="btn-primary-modern">
                                            <i class="fas fa-play me-2"></i>
                                            Start Listening
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="modern-track-list">
                                        <?php foreach ($recent_plays as $index => $track): ?>
                                            <div class="modern-track-item" data-track-id="<?php echo $track['id']; ?>">
                                                <div class="track-cover-modern">
                                                    <?php 
                                                    $cover_url = ($track['cover_image'] && file_exists(UPLOAD_PATH . 'covers/' . $track['cover_image'])) ? 
                                                        UPLOAD_URL . 'covers/' . $track['cover_image'] : 
                                                        UPLOAD_URL . 'covers/default-cover.jpg';
                                                    ?>
                                                    <img src="<?php echo $cover_url; ?>" 
                                                         alt="<?php echo htmlspecialchars($track['title']); ?>" 
                                                         loading="lazy">
                                                    <div class="play-overlay" onclick="playTrack(<?php echo htmlspecialchars(json_encode($track)); ?>)">
                                                        <i class="fas fa-play"></i>
                                                    </div>
                                                </div>
                                                <div class="track-details-modern">
                                                    <h6 class="track-title"><?php echo htmlspecialchars(substr($track['title'], 0, 30) . (strlen($track['title']) > 30 ? '...' : '')); ?></h6>
                                                    <p class="track-artist"><?php echo htmlspecialchars(substr($track['artist'], 0, 25) . (strlen($track['artist']) > 25 ? '...' : '')); ?></p>
                                                    <small class="played-time">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('M j, g:i A', strtotime($track['played_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="track-actions-modern">
                                                    <button class="action-btn play-btn" 
                                                            onclick="playTrack(<?php echo htmlspecialchars(json_encode($track)); ?>)"
                                                            title="Play Track">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="action-btn favorite-btn" 
                                                            onclick="toggleFavorite(<?php echo $track['id']; ?>)"
                                                            title="Add to Favorites">
                                                        <i class="far fa-heart"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Professional Profile Page Styling -->
<style>
/* Base Layout */
.profile-page {
    background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Hero Section */
.profile-hero-section {
    position: relative;
    padding: 4rem 0;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(29, 185, 84, 0.1) 0%, rgba(30, 215, 96, 0.05) 100%);
}

.floating-elements {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.floating-element {
    position: absolute;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(29, 185, 84, 0.2), rgba(30, 215, 96, 0.1));
    animation: float 6s ease-in-out infinite;
}

.element-1 {
    width: 100px;
    height: 100px;
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.element-2 {
    width: 60px;
    height: 60px;
    top: 60%;
    right: 15%;
    animation-delay: 2s;
}

.element-3 {
    width: 80px;
    height: 80px;
    bottom: 20%;
    left: 70%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

/* Profile Header */
.profile-header-content {
    position: relative;
    z-index: 2;
}

.profile-avatar-section {
    position: relative;
    display: inline-block;
}

.profile-avatar-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
    margin-bottom: 2rem;
}

.avatar-ring {
    position: absolute;
    top: -8px;
    left: -8px;
    right: -8px;
    bottom: -8px;
    border: 3px solid transparent;
    border-radius: 50%;
    background: linear-gradient(135deg, #1db954, #1ed760) border-box;
    mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
    animation: rotate 8s linear infinite;
}

.profile-avatar {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 2;
}

.profile-avatar.loading {
    filter: blur(2px);
    opacity: 0.7;
}

.avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 3;
}

.profile-avatar-container:hover .avatar-overlay {
    opacity: 1;
}

.profile-avatar-container:hover .profile-avatar {
    transform: scale(1.05);
}

.overlay-content {
    text-align: center;
    color: white;
}

.overlay-content i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.overlay-text {
    font-size: 0.9rem;
    font-weight: 500;
}

.avatar-status-indicator {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 24px;
    height: 24px;
    background: #1db954;
    border: 3px solid white;
    border-radius: 50%;
    z-index: 4;
}

/* Profile Info */
.profile-info {
    padding-left: 2rem;
}

.profile-name {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.name-text {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ffffff, #1db954);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-right: 1rem;
}

.verified-badge {
    color: #1db954;
    font-size: 1.5rem;
}

.profile-username {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.profile-email {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
    margin-bottom: 2rem;
}

/* Stats Grid */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card-modern {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card-modern:hover {
    transform: translateY(-5px);
    background: rgba(29, 185, 84, 0.1);
    border-color: rgba(29, 185, 84, 0.3);
    box-shadow: 0 20px 40px rgba(29, 185, 84, 0.2);
}

.stat-card-modern .stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.2rem;
}

.stat-card-modern .stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.stat-card-modern .stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

/* Main Content Section */
.main-content-section {
    padding: 3rem 0;
    position: relative;
}

/* Settings Card */
.settings-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.card-header-modern {
    background: linear-gradient(135deg, rgba(29, 185, 84, 0.1), rgba(30, 215, 96, 0.05));
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem;
}

.header-content {
    display: flex;
    align-items: center;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-right: 1.5rem;
    box-shadow: 0 10px 20px rgba(29, 185, 84, 0.3);
}

.header-text h3 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.header-text p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    font-size: 0.95rem;
}

.card-body-modern {
    padding: 2.5rem;
}

/* Modern Alerts */
.alert-success-modern,
.alert-error-modern {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    position: relative;
    backdrop-filter: blur(10px);
}

.alert-success-modern {
    background: rgba(40, 167, 69, 0.15);
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.alert-error-modern {
    background: rgba(220, 53, 69, 0.15);
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.alert-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
}

.alert-success-modern .alert-icon {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.alert-error-modern .alert-icon {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.alert-content {
    flex: 1;
}

.alert-content h6 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert-content p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 0.9rem;
}

.alert-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.alert-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

/* Form Sections */
.form-section {
    margin-bottom: 3rem;
}

.section-title {
    color: #ffffff;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.75rem;
    color: #1db954;
}

/* Avatar Upload */
.avatar-upload-form {
    margin-bottom: 2rem;
}

.upload-area {
    background: rgba(255, 255, 255, 0.03);
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.upload-area:hover {
    border-color: #1db954;
    background: rgba(29, 185, 84, 0.05);
}

.upload-preview {
    margin-bottom: 1.5rem;
}

.preview-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.2);
}

.upload-content {
    margin-bottom: 1.5rem;
}

.upload-icon {
    font-size: 3rem;
    color: #1db954;
    margin-bottom: 1rem;
}

.upload-content h5 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.upload-text {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
}

.upload-info {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
}

.file-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.btn-upload {
    background: linear-gradient(135deg, #1db954, #1ed760);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-upload:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(29, 185, 84, 0.4);
}

.btn-upload:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Modern Form Inputs */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.password-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.input-group-modern {
    position: relative;
    margin-bottom: 1rem;
}

.form-label-modern {
    display: flex;
    align-items: center;
    color: #ffffff;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-label-modern i {
    margin-right: 0.5rem;
    color: #1db954;
    width: 16px;
}

.form-control-modern {
    width: 100%;
    padding: 1rem 1.25rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.form-control-modern:focus {
    outline: none;
    border-color: #1db954;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 4px rgba(29, 185, 84, 0.2);
}

.form-control-modern.readonly {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.05);
    cursor: not-allowed;
}

.form-help {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

/* Password Strength */
.password-strength {
    margin-top: 0.75rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.password-match {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Form Actions */
.form-actions {
    text-align: right;
    margin-top: 2rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #1db954, #1ed760);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.btn-primary-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-primary-modern:hover::before {
    left: 100%;
}

.btn-primary-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(29, 185, 84, 0.4);
}

/* Sidebar Content */
.sidebar-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.stats-card-modern,
.actions-card-modern {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

/* Stats Grid in Sidebar */
.stats-grid {
    padding: 1.5rem;
    display: grid;
    gap: 1.5rem;
}

.stat-item-modern {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

.stat-item-modern:hover {
    background: rgba(29, 185, 84, 0.1);
    border-color: rgba(29, 185, 84, 0.2);
    transform: translateX(5px);
}

.stat-visual {
    margin-right: 1rem;
}

.stat-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: conic-gradient(#1db954 var(--percentage, 0%), rgba(255, 255, 255, 0.1) 0%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.stat-circle::before {
    content: '';
    position: absolute;
    width: 45px;
    height: 45px;
    background: #1a1a2e;
    border-radius: 50%;
}

.stat-circle .stat-number {
    position: relative;
    z-index: 2;
    font-size: 0.8rem;
    font-weight: 700;
    color: #ffffff;
}

.stat-info h5 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 1rem;
}

.stat-info p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
    font-size: 0.85rem;
}

/* Actions Grid */
.actions-grid {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.action-card {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(29, 185, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.action-card:hover::before {
    left: 100%;
}

.action-card:hover {
    background: rgba(29, 185, 84, 0.1);
    border-color: rgba(29, 185, 84, 0.3);
    transform: translateX(8px);
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(29, 185, 84, 0.2);
}

.action-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    margin-right: 1rem;
    box-shadow: 0 4px 12px rgba(29, 185, 84, 0.3);
}

.action-content {
    flex: 1;
}

.action-content h5 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.action-content p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
    font-size: 0.8rem;
}

.action-arrow {
    color: rgba(255, 255, 255, 0.4);
    transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
    color: #1db954;
    transform: translateX(3px);
}

/* Header Actions */
.header-actions {
    margin-left: auto;
}

/* Modern Track List Styling */
.modern-track-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.modern-track-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.modern-track-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(29, 185, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.modern-track-item:hover::before {
    left: 100%;
}

.modern-track-item:hover {
    background: rgba(29, 185, 84, 0.08);
    border-color: rgba(29, 185, 84, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(29, 185, 84, 0.15);
}

.track-cover-modern {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 12px;
    overflow: hidden;
    margin-right: 1rem;
    flex-shrink: 0;
}

.track-cover-modern img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.track-cover-modern:hover img {
    transform: scale(1.1);
}

.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
    border-radius: 12px;
}

.track-cover-modern:hover .play-overlay,
.modern-track-item:hover .play-overlay {
    opacity: 1;
}

.play-overlay i {
    color: #1db954;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.play-overlay:hover i {
    color: #ffffff;
    font-size: 1.7rem;
    text-shadow: 0 0 10px rgba(29, 185, 84, 0.8);
}

.track-details-modern {
    flex: 1;
    min-width: 0;
}

.track-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.track-artist {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.played-time {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}

.modern-mood-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

.track-actions-modern {
    display: flex;
    gap: 0.5rem;
    margin-left: 1rem;
}

.action-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.action-btn:hover {
    background: rgba(29, 185, 84, 0.2);
    color: #1db954;
    transform: scale(1.1);
}

.action-btn.active {
    background: rgba(29, 185, 84, 0.2);
    color: #1db954;
}

.play-btn:hover {
    background: rgba(29, 185, 84, 0.9);
    color: white;
}

.favorite-btn.active {
    color: #e91e63;
}

.favorite-btn.active:hover {
    background: rgba(233, 30, 99, 0.2);
    color: #e91e63;
}

/* Empty State Styling */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: rgba(255, 255, 255, 0.6);
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.3);
}

.empty-state h5 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Responsive Design for Track Lists */
@media (max-width: 768px) {
    .modern-track-item {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .track-cover-modern {
        margin-right: 0;
        margin-bottom: 1rem;
        width: 80px;
        height: 80px;
    }
    
    .track-actions-modern {
        margin-left: 0;
        margin-top: 1rem;
        justify-content: center;
    }
    
    .track-details-modern {
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    .modern-track-list {
        gap: 0.75rem;
    }
    
    .modern-track-item {
        padding: 0.75rem;
    }
    
    .track-cover-modern {
        width: 60px;
        height: 60px;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .profile-info {
        padding-left: 1rem;
    }
    
    .name-text {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .profile-hero-section {
        padding: 2rem 0;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
    }
    
    .name-text {
        font-size: 2rem;
    }
    
    .profile-info {
        padding-left: 0;
        margin-top: 1.5rem;
        text-align: center;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 1rem;
    }
    
    .form-grid,
    .password-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header-modern {
        padding: 1.5rem;
    }
    
    .card-body-modern {
        padding: 1.5rem;
    }
    
    .upload-area {
        padding: 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .header-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    .profile-avatar {
        width: 100px;
        height: 100px;
    }
    
    .name-text {
        font-size: 1.8rem;
    }
    
    .main-content-section {
        padding: 2rem 0;
    }
    
    .stats-grid {
        padding: 1rem;
    }
    
    .actions-grid {
        padding: 0.75rem;
    }
}

/* Animation Enhancements */
@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes slideInUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.settings-card,
.stats-card-modern,
.actions-card-modern {
    animation: slideInUp 0.6s ease-out;
}

/* Loading States */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>

<!-- Professional Profile Page JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Avatar upload preview functionality
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.querySelector('.preview-image');
    const profileAvatar = document.querySelector('.profile-avatar');
    const uploadArea = document.querySelector('.upload-area');
    const uploadBtn = document.querySelector('.btn-upload');
    const fileNameDisplay = document.getElementById('file-name');

    // Avatar upload handling
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const fileType = file.type.toLowerCase();
                
                if (!allowedTypes.includes(fileType)) {
                    showNotification('Please select a valid image file (JPG, PNG, or GIF)', 'error');
                    this.value = '';
                    if (fileNameDisplay) fileNameDisplay.textContent = 'Choose image file...';
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('File size must be less than 5MB', 'error');
                    this.value = '';
                    if (fileNameDisplay) fileNameDisplay.textContent = 'Choose image file...';
                    return;
                }

                // Update file name display
                if (fileNameDisplay) fileNameDisplay.textContent = file.name;
                if (uploadBtn) uploadBtn.disabled = false;

                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarPreview) {
                        avatarPreview.src = e.target.result;
                        avatarPreview.style.display = 'block';
                    }
                    if (profileAvatar) {
                        profileAvatar.classList.add('loading');
                        setTimeout(() => {
                            profileAvatar.src = e.target.result;
                            profileAvatar.classList.remove('loading');
                        }, 500);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        if (uploadArea) {
            uploadArea.addEventListener('click', () => avatarInput.click());
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#1db954';
                this.style.background = 'rgba(29, 185, 84, 0.1)';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                this.style.background = 'rgba(255, 255, 255, 0.03)';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                this.style.background = 'rgba(255, 255, 255, 0.03)';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    avatarInput.files = files;
                    avatarInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }

    // Password strength indicator
    const passwordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput) {
        const strengthBar = document.querySelector('.strength-fill');
        const strengthText = document.querySelector('.strength-text');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            if (strengthBar && strengthText) {
                updatePasswordStrength(strength, strengthBar, strengthText);
            }
        });
    }

    // Password confirmation matching
    if (confirmPasswordInput && passwordInput) {
        const matchIndicator = document.querySelector('.password-match');
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (matchIndicator) {
                if (confirmPassword === '') {
                    matchIndicator.textContent = '';
                    matchIndicator.className = 'password-match';
                } else if (password === confirmPassword) {
                    matchIndicator.textContent = '✓ Passwords match';
                    matchIndicator.className = 'password-match text-success';
                    matchIndicator.style.color = '#28a745';
                    confirmPasswordInput.setCustomValidity('');
                } else {
                    matchIndicator.textContent = '✗ Passwords do not match';
                    matchIndicator.className = 'password-match text-danger';
                    matchIndicator.style.color = '#dc3545';
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                }
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    // Form submission enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Special handling for avatar form
            if (form.id === 'avatarForm') {
                const fileInput = form.querySelector('#avatar');
                if (!fileInput.files || !fileInput.files[0]) {
                    e.preventDefault();
                    showNotification('Please select an image file first', 'error');
                    return false;
                }
                
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
                }
            }
            
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Re-enable button after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });

    // Alert close functionality
    const alertCloseButtons = document.querySelectorAll('.alert-close');
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert-success-modern, .alert-error-modern');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        });
    });

    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Stats animation
    function animateStats() {
        const statCircles = document.querySelectorAll('.stat-circle');
        statCircles.forEach(circle => {
            const statNumber = circle.querySelector('.stat-number');
            if (statNumber) {
                const percentage = parseInt(statNumber.textContent) || 0;
                circle.style.setProperty('--percentage', `${Math.min(percentage, 100)}%`);
            }
        });
    }

    // Initialize stats animation when page loads
    setTimeout(animateStats, 500);

    // Form validation enhancements
    const inputs = document.querySelectorAll('.form-control-modern');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('focus', function() {
            clearFieldValidation(this);
        });
    });

    // Real-time form validation
    function validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const isRequired = field.hasAttribute('required');
        
        let isValid = true;
        let message = '';
        
        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Email validation
        if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        // Password validation
        if (fieldType === 'password' && value) {
            if (value.length < 6) {
                isValid = false;
                message = 'Password must be at least 6 characters long';
            }
        }
        
        // Display validation result
        if (!isValid) {
            field.style.borderColor = '#dc3545';
            field.style.background = 'rgba(220, 53, 69, 0.1)';
            showFieldError(field, message);
        } else if (value) {
            field.style.borderColor = '#28a745';
            field.style.background = 'rgba(40, 167, 69, 0.1)';
            clearFieldError(field);
        }
    }
    
    function clearFieldValidation(field) {
        field.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        field.style.background = 'rgba(255, 255, 255, 0.05)';
        clearFieldError(field);
    }
    
    function showFieldError(field, message) {
        clearFieldError(field);
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.style.color = '#dc3545';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '0.25rem';
        errorElement.textContent = message;
        field.parentNode.appendChild(errorElement);
    }
    
    function clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-success-modern, .alert-error-modern');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);
    });

    // Keyboard navigation enhancements
    document.addEventListener('keydown', function(e) {
        // Escape key to close alerts
        if (e.key === 'Escape') {
            const alerts = document.querySelectorAll('.alert-success-modern, .alert-error-modern');
            alerts.forEach(alert => {
                alert.querySelector('.alert-close')?.click();
            });
        }
        
        // Ctrl/Cmd + S to save form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.click();
            }
        }
    });

    // Profile avatar loading with enhanced error handling
    const avatarImg = document.getElementById('profileAvatar') || document.querySelector('.profile-avatar');
    if (avatarImg) {
        avatarImg.classList.add('loading');
        
        const originalSrc = avatarImg.src;
        const cacheBuster = new Date().getTime();
        if (originalSrc.includes('?v=')) {
            const newSrc = originalSrc.split('?v=')[0] + '?v=' + cacheBuster;
            avatarImg.src = newSrc;
        }
        
        avatarImg.addEventListener('load', function() {
            this.classList.remove('loading');
        });
        
        avatarImg.addEventListener('error', function() {
            this.classList.remove('loading');
            this.src = '<?php echo UPLOAD_URL; ?>avatars/default-avatar.svg?v=' + cacheBuster;
        });
        
        if (avatarImg.complete) {
            avatarImg.classList.remove('loading');
        }
    }
});

// Password strength calculation
function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 25;
    if (/[a-z]/.test(password)) score += 10;
    if (/[A-Z]/.test(password)) score += 10;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^A-Za-z0-9]/.test(password)) score += 15;
    
    return Math.min(score, 100);
}

// Update password strength display
function updatePasswordStrength(strength, strengthBar, strengthText) {
    strengthBar.style.width = strength + '%';
    
    if (strength < 30) {
        strengthBar.style.background = '#dc3545';
        strengthText.textContent = 'Weak';
        strengthText.style.color = '#dc3545';
    } else if (strength < 60) {
        strengthBar.style.background = '#ffc107';
        strengthText.textContent = 'Fair';
        strengthText.style.color = '#ffc107';
    } else if (strength < 80) {
        strengthBar.style.background = '#28a745';
        strengthText.textContent = 'Good';
        strengthText.style.color = '#28a745';
    } else {
        strengthBar.style.background = '#1db954';
        strengthText.textContent = 'Strong';
        strengthText.style.color = '#1db954';
    }
}

// Utility functions
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert-${type}-modern`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '400px';
    
    notification.innerHTML = `
        <div class="alert-icon">
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
        </div>
        <div class="alert-content">
            <h6>${type === 'success' ? 'Success' : 'Error'}</h6>
            <p>${message}</p>
        </div>
        <button class="alert-close" type="button">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Add close functionality
    notification.querySelector('.alert-close').addEventListener('click', function() {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.querySelector('.alert-close').click();
        }
    }, 5000);
}

// Verify audio file exists before playing
function verifyAudioFile(url) {
    return fetch(url, { method: 'HEAD' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`File not found: ${response.status}`);
            }
            return true;
        })
        .catch(error => {
            console.error('Audio file verification failed:', error);
            return false;
        });
}

// Global play track function
function playTrack(trackData) {
    console.log('Playing track:', trackData);
    
    // Validate track data
    if (!trackData || !trackData.id) {
        showNotification('Invalid track data', 'error');
        return;
    }
    
    // Check if track has file_path
    if (!trackData.file_path) {
        showNotification('Track file not available', 'error');
        return;
    }
    
    // Construct correct track URL
    const trackUrl = `<?php echo UPLOAD_URL; ?>music/${trackData.file_path}`;
    console.log('Track URL (FIXED PATH):', trackUrl);
    console.log('Track data:', trackData);
    
    // Verify audio file exists before attempting to play
    console.log('Attempting to play track directly (verification skipped for now)');
    
    // Check if global music player is available
    if (typeof window.playTrackGlobal === 'function') {
        try {
            window.playTrackGlobal(trackData);
            return;
        } catch (error) {
            console.log('Global player error:', error);
        }
    }
    
    // Try alternative music player methods
    if (typeof window.musicPlayer === 'object' && window.musicPlayer.playTrack) {
        try {
            window.musicPlayer.playTrack(trackData);
            return;
        } catch (error) {
            console.log('Music player object error:', error);
        }
    }
    
    // Fallback: try to find and use any available audio element (footer player)
    const audioPlayer = document.querySelector('#audioPlayer') || 
                       document.querySelector('#globalAudioPlayer audio') || 
                       document.querySelector('audio') ||
                       document.querySelector('.music-player audio');
    
    if (audioPlayer) {
        try {
            audioPlayer.src = trackUrl;
            audioPlayer.load();
            
            // Add error event listener
            audioPlayer.onerror = function(e) {
                console.error('Audio error:', e);
                console.error('Audio error code:', audioPlayer.error?.code);
                console.error('Audio src that failed:', audioPlayer.src);
                
                let errorMessage = 'Failed to play track';
                if (audioPlayer.error?.code === 4) {
                    errorMessage = 'Audio format not supported or file not found';
                } else if (audioPlayer.error?.code === 3) {
                    errorMessage = 'Audio file is corrupted or incomplete';
                } else if (audioPlayer.error?.code === 2) {
                    errorMessage = 'Network error while loading audio';
                }
                
                showNotification(errorMessage + ' (URL: ' + trackUrl + ')', 'error');
            };
            
            // Add loaded event listener
            audioPlayer.onloadeddata = function() {
                console.log('Audio loaded successfully from:', trackUrl);
            };
            
            audioPlayer.play().then(() => {
                console.log('Track playing successfully!');
                showNotification(`Now playing: ${trackData.title}`, 'success');
                
                // Update player display if elements exist
                updatePlayerDisplay(trackData);
                
                // Record play history
                recordPlayHistory(trackData.id);
            }).catch(error => {
                console.error('Error playing track:', error);
                showNotification('Failed to play track: ' + error.message, 'error');
            });
        } catch (error) {
            console.error('Fallback player error:', error);
            showNotification('Unable to play track', 'error');
        }
    } else {
        console.log('No audio player found, creating temporary player');
        
        // Create a temporary audio element as last resort
        const tempAudio = document.createElement('audio');
        tempAudio.controls = false;
        tempAudio.style.display = 'none';
        tempAudio.preload = 'auto';
        document.body.appendChild(tempAudio);
        
        // Add comprehensive error handling to temporary audio
        tempAudio.onerror = function(e) {
            console.error('Temporary audio error:', e);
            console.error('Temporary audio error code:', tempAudio.error?.code);
            console.error('Temporary audio src that failed:', tempAudio.src);
            
            let errorMessage = 'Failed to play track';
            if (tempAudio.error?.code === 4) {
                errorMessage = 'Audio format not supported or file not found';
            }
            
            showNotification(errorMessage + ' (URL: ' + trackUrl + ')', 'error');
            if (document.body.contains(tempAudio)) {
                document.body.removeChild(tempAudio);
            }
        };
        
        tempAudio.src = trackUrl;
        tempAudio.load();
        
        tempAudio.play().then(() => {
            console.log('Track playing successfully via temporary player!');
            showNotification(`Now playing: ${trackData.title}`, 'success');
            recordPlayHistory(trackData.id);
            
            // Remove temporary audio when done
            tempAudio.addEventListener('ended', () => {
                if (document.body.contains(tempAudio)) {
                    document.body.removeChild(tempAudio);
                }
            });
        }).catch(error => {
            console.error('Temporary player error:', error);
            if (document.body.contains(tempAudio)) {
                document.body.removeChild(tempAudio);
            }
            showNotification('Unable to play this track: ' + error.message, 'error');
        });
    }
}

// Update player display elements
function updatePlayerDisplay(trackData) {
    const titleElement = document.querySelector('.current-track-title') || 
                        document.querySelector('[data-track-title]');
    const artistElement = document.querySelector('.current-track-artist') || 
                         document.querySelector('[data-track-artist]');
    const coverElement = document.querySelector('.current-track-cover') || 
                        document.querySelector('[data-track-cover]');
    
    if (titleElement) titleElement.textContent = trackData.title;
    if (artistElement) artistElement.textContent = trackData.artist;
    if (coverElement && trackData.cover_image) {
        const coverUrl = `<?php echo UPLOAD_URL; ?>covers/${trackData.cover_image}`;
        coverElement.src = coverUrl;
    }
}

// Record play history
function recordPlayHistory(trackId) {
    fetch('ajax/record_play.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ track_id: trackId })
    }).catch(error => {
        console.log('Failed to record play history:', error);
    });
}

// Enhanced toggle favorite function
function toggleFavorite(trackId) {
    const btn = event.target.closest('.favorite-btn');
    if (!btn) return;
    
    const isActive = btn.classList.contains('active');
    const icon = btn.querySelector('i');
    
    // Show loading state
    btn.style.opacity = '0.5';
    btn.style.pointerEvents = 'none';
    const originalIcon = icon.className;
    icon.className = 'fas fa-spinner fa-spin';
    
    fetch('ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ track_id: trackId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (data.favorited) {
                btn.classList.add('active');
                icon.className = 'fas fa-heart';
                btn.title = 'Remove from Favorites';
                showNotification('Added to favorites!', 'success');
            } else {
                btn.classList.remove('active');
                icon.className = 'far fa-heart';
                btn.title = 'Add to Favorites';
                showNotification('Removed from favorites', 'success');
                
                // If we're in the favorites section, remove the item with animation
                const trackItem = btn.closest('.modern-track-item');
                if (window.location.pathname.includes('favorites') || 
                    trackItem.closest('[data-section="recent-favorites"]')) {
                    trackItem.style.opacity = '0';
                    trackItem.style.transform = 'translateX(-100%)';
                    setTimeout(() => {
                        trackItem.remove();
                        
                        // Check if favorites list is empty
                        const favoritesList = document.querySelector('.modern-track-list');
                        if (favoritesList && favoritesList.children.length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                }
            }
        } else {
            icon.className = originalIcon;
            showNotification(data.message || 'Failed to update favorites', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        icon.className = originalIcon;
        showNotification('Failed to update favorites. Please try again.', 'error');
    })
    .finally(() => {
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
    });
}

// Check if track is favorited and update button state
function checkFavoriteStatus(trackId, buttonElement) {
    fetch(`ajax/check_favorite.php?track_id=${trackId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = buttonElement.querySelector('i');
            if (data.is_favorite) {
                buttonElement.classList.add('active');
                icon.className = 'fas fa-heart';
                buttonElement.title = 'Remove from Favorites';
            } else {
                buttonElement.classList.remove('active');
                icon.className = 'far fa-heart';
                buttonElement.title = 'Add to Favorites';
            }
        }
    })
    .catch(error => {
        console.log('Failed to check favorite status:', error);
    });
}

// Initialize favorite buttons on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page JavaScript initialized');
    
    // Set up favorite buttons for recently played tracks
    const recentlyPlayedButtons = document.querySelectorAll('[data-section="recently-played"] .favorite-btn');
    console.log('Found recently played favorite buttons:', recentlyPlayedButtons.length);
    
    recentlyPlayedButtons.forEach(btn => {
        const trackItem = btn.closest('.modern-track-item');
        const trackId = trackItem.getAttribute('data-track-id');
        if (trackId) {
            checkFavoriteStatus(trackId, btn);
        }
    });
    
    // Add section identifiers for better handling
    const recentFavoritesSection = document.querySelector('.col-lg-6:first-child .modern-track-list');
    const recentlyPlayedSection = document.querySelector('.col-lg-6:last-child .modern-track-list');
    
    if (recentFavoritesSection) {
        recentFavoritesSection.setAttribute('data-section', 'recent-favorites');
        console.log('Set recent favorites section identifier');
    }
    if (recentlyPlayedSection) {
        recentlyPlayedSection.setAttribute('data-section', 'recently-played');
        console.log('Set recently played section identifier');
    }
    
    // Add click handlers to play buttons as backup
    const playButtons = document.querySelectorAll('.play-btn');
    playButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const trackItem = this.closest('.modern-track-item');
            const trackId = trackItem.getAttribute('data-track-id');
            
            // Try to get track data from button's onclick or data attributes
            const onclickAttr = this.getAttribute('onclick');
            if (onclickAttr) {
                try {
                    // Extract track data from onclick attribute
                    const trackDataMatch = onclickAttr.match(/playTrack\((.+)\)/);
                    if (trackDataMatch) {
                        const trackData = JSON.parse(trackDataMatch[1]);
                        playTrack(trackData);
                    }
                } catch (error) {
                    console.error('Error parsing track data from onclick:', error);
                    showNotification('Unable to play track', 'error');
                }
            }
        });
    });
    
    // Add click handlers to play overlays as backup
    const playOverlays = document.querySelectorAll('.play-overlay');
    console.log('Found play overlays:', playOverlays.length);
    
    playOverlays.forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Play overlay clicked');
            
            // The onclick should handle this, but add logging for debugging
            const onclickAttr = this.getAttribute('onclick');
            if (!onclickAttr) {
                console.error('No onclick handler found for play overlay');
                showNotification('Unable to play track - missing handler', 'error');
            }
        });
    });
});

// Performance optimization
window.addEventListener('beforeunload', function() {
    if (typeof AbortController !== 'undefined') {
        document.querySelectorAll('form').forEach(form => {
            const controller = form._abortController;
            if (controller) {
                controller.abort();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
