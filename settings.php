<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'Settings';
$currentPage = 'settings';

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

// Default user preferences
$default_preferences = [
    'theme' => 'dark',
    'autoplay' => 1,
    'shuffle' => 0,
    'repeat' => 'none',
    'volume' => 70,
    'quality' => 'high',
    'notifications' => 1,
    'auto_add_to_history' => 1,
    'show_lyrics' => 1,
    'crossfade' => 0,
    'crossfade_duration' => 3,
    'equalizer_preset' => 'default',
    'language' => 'en',
    'timezone' => 'UTC'
];

// Get user preferences from database or use defaults
$stmt = $pdo->prepare("SELECT preferences FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$prefs_json = $stmt->fetchColumn();

// Handle cases where preferences is NULL or invalid JSON
$user_preferences = [];
if ($prefs_json) {
    $decoded_prefs = json_decode($prefs_json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_prefs)) {
        $user_preferences = $decoded_prefs;
    }
}

$preferences = array_merge($default_preferences, $user_preferences);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_preferences'])) {
        // Audio Settings
        $new_preferences = [
            'theme' => sanitize($_POST['theme'] ?? 'dark'),
            'autoplay' => isset($_POST['autoplay']) ? 1 : 0,
            'shuffle' => isset($_POST['shuffle']) ? 1 : 0,
            'repeat' => sanitize($_POST['repeat'] ?? 'none'),
            'volume' => max(0, min(100, intval($_POST['volume'] ?? 70))),
            'quality' => sanitize($_POST['quality'] ?? 'high'),
            'notifications' => isset($_POST['notifications']) ? 1 : 0,
            'auto_add_to_history' => isset($_POST['auto_add_to_history']) ? 1 : 0,
            'show_lyrics' => isset($_POST['show_lyrics']) ? 1 : 0,
            'crossfade' => isset($_POST['crossfade']) ? 1 : 0,
            'crossfade_duration' => max(1, min(10, intval($_POST['crossfade_duration'] ?? 3))),
            'equalizer_preset' => sanitize($_POST['equalizer_preset'] ?? 'default'),
            'language' => sanitize($_POST['language'] ?? 'en'),
            'timezone' => sanitize($_POST['timezone'] ?? 'UTC')
        ];
        
        // Update preferences in database
        $prefs_json = json_encode($new_preferences);
        $stmt = $pdo->prepare("UPDATE users SET preferences = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$prefs_json, $_SESSION['user_id']])) {
            $preferences = $new_preferences;
            $success_message = "Settings updated successfully!";
        } else {
            $error_message = "Failed to update settings.";
        }
    }
    
    if (isset($_POST['clear_history'])) {
        // Clear play history
        $stmt = $pdo->prepare("DELETE FROM play_history WHERE user_id = ?");
        if ($stmt->execute([$_SESSION['user_id']])) {
            $success_message = "Play history cleared successfully!";
        } else {
            $error_message = "Failed to clear play history.";
        }
    }
    
    if (isset($_POST['clear_favorites'])) {
        // Clear favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
        if ($stmt->execute([$_SESSION['user_id']])) {
            $success_message = "Favorites cleared successfully!";
        } else {
            $error_message = "Failed to clear favorites.";
        }
    }
    
    if (isset($_POST['export_data'])) {
        // Export user data
        try {
            $export_data = [
                'user_info' => [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'created_at' => $user['created_at']
                ],
                'preferences' => $preferences
            ];
            
            // Get favorites
            $stmt = $pdo->prepare("
                SELECT t.title, t.artist, t.album, f.created_at 
                FROM favorites f 
                JOIN tracks t ON f.track_id = t.id 
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $export_data['favorites'] = $stmt->fetchAll();
            
            // Get play history
            $stmt = $pdo->prepare("
                SELECT t.title, t.artist, t.album, ph.played_at 
                FROM play_history ph 
                JOIN tracks t ON ph.track_id = t.id 
                WHERE ph.user_id = ? 
                ORDER BY ph.played_at DESC 
                LIMIT 1000
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $export_data['play_history'] = $stmt->fetchAll();
            
            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="music_data_export_' . date('Y-m-d') . '.json"');
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            exit();
            
        } catch (Exception $e) {
            $error_message = "Failed to export data: " . $e->getMessage();
        }
    }
}

// Get usage statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_plays FROM play_history WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_plays = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_favorites FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_favorites = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("
        SELECT DATE(ph.played_at) as play_date, COUNT(*) as plays_count 
        FROM play_history ph 
        WHERE ph.user_id = ? 
        AND ph.played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(ph.played_at) 
        ORDER BY play_date DESC 
        LIMIT 7
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_activity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_plays = 0;
    $total_favorites = 0;
    $recent_activity = [];
}

include 'includes/header.php';
?>

<div class="container-fluid settings-page">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="container">
                    <div class="row align-items-center py-4">
                        <div class="col-md-8">
                            <h1 class="page-title">
                                <i class="fas fa-cog me-3"></i>Settings
                            </h1>
                            <p class="page-subtitle">Customize your music experience</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="quick-stats">
                                <span class="stat-item">
                                    <i class="fas fa-play me-1"></i>
                                    <?php echo number_format($total_plays); ?> plays
                                </span>
                                <span class="stat-item ms-3">
                                    <i class="fas fa-heart me-1"></i>
                                    <?php echo number_format($total_favorites); ?> favorites
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-lg-3">
                <div class="settings-nav">
                    <div class="nav nav-pills flex-column" id="settings-tab" role="tablist">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                            <i class="fas fa-sliders-h me-2"></i>General
                        </button>
                        <button class="nav-link" id="playback-tab" data-bs-toggle="pill" data-bs-target="#playback" type="button" role="tab">
                            <i class="fas fa-music me-2"></i>Playback
                        </button>
                        <button class="nav-link" id="audio-tab" data-bs-toggle="pill" data-bs-target="#audio" type="button" role="tab">
                            <i class="fas fa-volume-up me-2"></i>Audio
                        </button>
                        <button class="nav-link" id="privacy-tab" data-bs-toggle="pill" data-bs-target="#privacy" type="button" role="tab">
                            <i class="fas fa-shield-alt me-2"></i>Privacy
                        </button>
                        <button class="nav-link" id="data-tab" data-bs-toggle="pill" data-bs-target="#data" type="button" role="tab">
                            <i class="fas fa-database me-2"></i>Data
                        </button>
                        <button class="nav-link" id="about-tab" data-bs-toggle="pill" data-bs-target="#about" type="button" role="tab">
                            <i class="fas fa-info-circle me-2"></i>About
                        </button>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-9">
                <form method="POST" id="settingsForm">
                    <div class="tab-content" id="settings-tabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-palette me-2"></i>Appearance</h3>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="theme" class="form-label">Theme</label>
                                            <select class="form-select" id="theme" name="theme">
                                                <option value="dark" <?php echo $preferences['theme'] == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                                <option value="light" <?php echo $preferences['theme'] == 'light' ? 'selected' : ''; ?>>Light</option>
                                                <option value="auto" <?php echo $preferences['theme'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="language" class="form-label">Language</label>
                                            <select class="form-select" id="language" name="language">
                                                <option value="en" <?php echo $preferences['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="es" <?php echo $preferences['language'] == 'es' ? 'selected' : ''; ?>>Español</option>
                                                <option value="fr" <?php echo $preferences['language'] == 'fr' ? 'selected' : ''; ?>>Français</option>
                                                <option value="de" <?php echo $preferences['language'] == 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="UTC" <?php echo $preferences['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo $preferences['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                                <option value="America/Chicago" <?php echo $preferences['timezone'] == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                                <option value="America/Denver" <?php echo $preferences['timezone'] == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                                <option value="America/Los_Angeles" <?php echo $preferences['timezone'] == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                                <option value="Europe/London" <?php echo $preferences['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                                <option value="Europe/Paris" <?php echo $preferences['timezone'] == 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                                <option value="Asia/Tokyo" <?php echo $preferences['timezone'] == 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <h3><i class="fas fa-bell me-2"></i>Notifications</h3>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifications" name="notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications">
                                        Enable notifications
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Playback Settings -->
                        <div class="tab-pane fade" id="playback" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-play me-2"></i>Playback Behavior</h3>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="autoplay" name="autoplay" <?php echo $preferences['autoplay'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="autoplay">
                                        Autoplay next track
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="shuffle" name="shuffle" <?php echo $preferences['shuffle'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="shuffle">
                                        Shuffle by default
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="auto_add_to_history" name="auto_add_to_history" <?php echo $preferences['auto_add_to_history'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_add_to_history">
                                        Automatically add to play history
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="show_lyrics" name="show_lyrics" <?php echo $preferences['show_lyrics'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_lyrics">
                                        Show lyrics when available
                                    </label>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="repeat" class="form-label">Repeat Mode</label>
                                            <select class="form-select" id="repeat" name="repeat">
                                                <option value="none" <?php echo $preferences['repeat'] == 'none' ? 'selected' : ''; ?>>No repeat</option>
                                                <option value="track" <?php echo $preferences['repeat'] == 'track' ? 'selected' : ''; ?>>Repeat track</option>
                                                <option value="playlist" <?php echo $preferences['repeat'] == 'playlist' ? 'selected' : ''; ?>>Repeat playlist</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="volume" class="form-label">Default Volume</label>
                                            <input type="range" class="form-range" min="0" max="100" step="5" id="volume" name="volume" value="<?php echo $preferences['volume']; ?>">
                                            <div class="d-flex justify-content-between">
                                                <small>0%</small>
                                                <small id="volume-display"><?php echo $preferences['volume']; ?>%</small>
                                                <small>100%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audio Settings -->
                        <div class="tab-pane fade" id="audio" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-headphones me-2"></i>Audio Quality</h3>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quality" class="form-label">Streaming Quality</label>
                                            <select class="form-select" id="quality" name="quality">
                                                <option value="low" <?php echo $preferences['quality'] == 'low' ? 'selected' : ''; ?>>Low (96 kbps)</option>
                                                <option value="normal" <?php echo $preferences['quality'] == 'normal' ? 'selected' : ''; ?>>Normal (160 kbps)</option>
                                                <option value="high" <?php echo $preferences['quality'] == 'high' ? 'selected' : ''; ?>>High (320 kbps)</option>
                                                <option value="lossless" <?php echo $preferences['quality'] == 'lossless' ? 'selected' : ''; ?>>Lossless</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="equalizer_preset" class="form-label">Equalizer Preset</label>
                                            <select class="form-select" id="equalizer_preset" name="equalizer_preset">
                                                <option value="default" <?php echo $preferences['equalizer_preset'] == 'default' ? 'selected' : ''; ?>>Default</option>
                                                <option value="rock" <?php echo $preferences['equalizer_preset'] == 'rock' ? 'selected' : ''; ?>>Rock</option>
                                                <option value="pop" <?php echo $preferences['equalizer_preset'] == 'pop' ? 'selected' : ''; ?>>Pop</option>
                                                <option value="jazz" <?php echo $preferences['equalizer_preset'] == 'jazz' ? 'selected' : ''; ?>>Jazz</option>
                                                <option value="classical" <?php echo $preferences['equalizer_preset'] == 'classical' ? 'selected' : ''; ?>>Classical</option>
                                                <option value="electronic" <?php echo $preferences['equalizer_preset'] == 'electronic' ? 'selected' : ''; ?>>Electronic</option>
                                                <option value="bass_boost" <?php echo $preferences['equalizer_preset'] == 'bass_boost' ? 'selected' : ''; ?>>Bass Boost</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <h3><i class="fas fa-random me-2"></i>Crossfade</h3>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="crossfade" name="crossfade" <?php echo $preferences['crossfade'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="crossfade">
                                        Enable crossfade between tracks
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label for="crossfade_duration" class="form-label">Crossfade Duration</label>
                                    <input type="range" class="form-range" min="1" max="10" step="1" id="crossfade_duration" name="crossfade_duration" value="<?php echo $preferences['crossfade_duration']; ?>" <?php echo !$preferences['crossfade'] ? 'disabled' : ''; ?>>
                                    <div class="d-flex justify-content-between">
                                        <small>1s</small>
                                        <small id="crossfade-display"><?php echo $preferences['crossfade_duration']; ?>s</small>
                                        <small>10s</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Settings -->
                        <div class="tab-pane fade" id="privacy" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-user-secret me-2"></i>Privacy & Data</h3>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Control how your data is collected and used.
                                </div>

                                <div class="privacy-option">
                                    <h5>Play History</h5>
                                    <p class="text-muted">We track what you listen to improve recommendations.</p>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_add_to_history" name="auto_add_to_history" <?php echo $preferences['auto_add_to_history'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_add_to_history">
                                            Allow play history tracking
                                        </label>
                                    </div>
                                </div>

                                <div class="privacy-option mt-4">
                                    <h5>Account Information</h5>
                                    <p class="text-muted">Manage your account data and privacy settings.</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Account Created:</strong><br>
                                            <span class="text-muted"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Last Updated:</strong><br>
                                            <span class="text-muted"><?php echo $user['updated_at'] ? date('F j, Y', strtotime($user['updated_at'])) : 'Never'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Management -->
                        <div class="tab-pane fade" id="data" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-download me-2"></i>Export Data</h3>
                                <p class="text-muted">Download your data including favorites, play history, and preferences.</p>
                                
                                <button type="submit" name="export_data" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-2"></i>Export My Data
                                </button>

                                <h3 class="mt-5"><i class="fas fa-trash me-2"></i>Data Management</h3>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Warning:</strong> These actions cannot be undone.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="data-action-card">
                                            <h5><i class="fas fa-history me-2"></i>Clear Play History</h5>
                                            <p class="text-muted">Remove all your listening history.</p>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                                                Clear History
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="data-action-card">
                                            <h5><i class="fas fa-heart me-2"></i>Clear Favorites</h5>
                                            <p class="text-muted">Remove all your favorite tracks.</p>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearFavoritesModal">
                                                Clear Favorites
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <h3 class="mt-4"><i class="fas fa-chart-bar me-2"></i>Usage Statistics</h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <h4><?php echo number_format($total_plays); ?></h4>
                                            <p>Total Plays</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <h4><?php echo number_format($total_favorites); ?></h4>
                                            <p>Total Favorites</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <h4><?php echo count($recent_activity); ?></h4>
                                            <p>Active Days (Last 7)</p>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($recent_activity)): ?>
                                <h4 class="mt-4">Recent Activity</h4>
                                <div class="table-responsive">
                                    <table class="table table-dark table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Plays</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_activity as $activity): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($activity['play_date'])); ?></td>
                                                <td><?php echo number_format($activity['plays_count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- About -->
                        <div class="tab-pane fade" id="about" role="tabpanel">
                            <div class="settings-section">
                                <h3><i class="fas fa-music me-2"></i>Media Crack Zone</h3>
                                
                                <div class="about-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Version Information</h5>
                                            <ul class="list-unstyled">
                                                <li><strong>Version:</strong> 1.0.0</li>
                                                <li><strong>Build:</strong> 2025.01.14</li>
                                                <li><strong>Platform:</strong> Web Application</li>
                                                <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Features</h5>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success me-2"></i>High-quality audio streaming</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Personalized recommendations</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Playlist management</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Cross-device sync</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Offline listening</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h5>Support</h5>
                                    <p>Need help? Contact our support team or check out our documentation.</p>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="fas fa-life-ring me-2"></i>Help Center
                                        </button>
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="fas fa-envelope me-2"></i>Contact Support
                                        </button>
                                        <button type="button" class="btn btn-outline-primary">
                                            <i class="fas fa-book me-2"></i>Documentation
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h5>Legal</h5>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-link">Privacy Policy</button>
                                        <button type="button" class="btn btn-link">Terms of Service</button>
                                        <button type="button" class="btn btn-link">Licenses</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-footer mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" onclick="resetToDefaults()">
                                <i class="fas fa-undo me-2"></i>Reset to Defaults
                            </button>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" onclick="window.location.reload()">
                                    Cancel
                                </button>
                                <button type="submit" name="update_preferences" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal fade" id="clearHistoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Play History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear your entire play history? This action cannot be undone.</p>
                <p class="text-muted">This will remove all records of tracks you've listened to.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_history" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Clear History
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clearFavoritesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Favorites</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove all your favorite tracks? This action cannot be undone.</p>
                <p class="text-muted">You'll need to re-favorite tracks you want to save.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_favorites" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Clear Favorites
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Settings Page Styles -->
<style>
.settings-page {
    min-height: 100vh;
    background: var(--background-main);
}

.page-header {
    background: linear-gradient(135deg, var(--background-card) 0%, var(--background-hover) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 0;
}

.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.page-subtitle {
    color: var(--text-light);
    margin: 0;
    font-size: 1.1rem;
}

.quick-stats .stat-item {
    color: var(--text-light);
    font-size: 0.9rem;
}

.settings-nav {
    position: sticky;
    top: 20px;
}

.settings-nav .nav-pills .nav-link {
    color: var(--text-white);
    background: var(--background-hover);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 0.5rem;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
    text-align: left;
    width: 100%;
}

.settings-nav .nav-pills .nav-link:hover {
    background: rgba(29, 185, 84, 0.1);
    border-color: var(--primary-color);
    transform: translateX(5px);
}

.settings-nav .nav-pills .nav-link.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.settings-section {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.settings-section h3 {
    color: var(--text-white);
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.form-label {
    color: var(--text-white);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    background: var(--background-hover);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-white);
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    background: var(--background-hover);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
    color: var(--text-white);
}

.form-check-input {
    background-color: var(--background-hover);
    border-color: rgba(255, 255, 255, 0.3);
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(29, 185, 84, 0.25);
}

.form-check-label {
    color: var(--text-white);
    font-weight: 500;
}

.form-range {
    background: transparent;
}

.form-range::-webkit-slider-track {
    background: var(--background-hover);
    border-radius: 5px;
    height: 8px;
}

.form-range::-webkit-slider-thumb {
    background: var(--primary-color);
    border: none;
    border-radius: 50%;
    height: 20px;
    width: 20px;
}

.form-range::-moz-range-track {
    background: var(--background-hover);
    border-radius: 5px;
    height: 8px;
    border: none;
}

.form-range::-moz-range-thumb {
    background: var(--primary-color);
    border: none;
    border-radius: 50%;
    height: 20px;
    width: 20px;
}

.privacy-option {
    background: var(--background-hover);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.privacy-option h5 {
    color: var(--text-white);
    margin-bottom: 0.5rem;
}

.data-action-card {
    background: var(--background-hover);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
    text-align: center;
}

.data-action-card h5 {
    color: var(--text-white);
    margin-bottom: 1rem;
}

.stat-card {
    background: var(--background-hover);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(29, 185, 84, 0.1);
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.stat-card h4 {
    color: var(--primary-color);
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-card p {
    color: var(--text-light);
    margin: 0;
}

.about-info {
    background: var(--background-hover);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.about-info h5 {
    color: var(--text-white);
    margin-bottom: 1rem;
}

.about-info ul li {
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.settings-footer {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.modal-content {
    background: var(--background-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header, .modal-footer {
    border-color: rgba(255, 255, 255, 0.1);
}

.modal-title {
    color: var(--text-white);
}

.modal-body {
    color: var(--text-white);
}

.table-dark {
    --bs-table-bg: var(--background-hover);
    --bs-table-border-color: rgba(255, 255, 255, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .settings-nav {
        position: static;
        margin-bottom: 2rem;
    }
    
    .settings-nav .nav-pills {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    
    .settings-nav .nav-pills .nav-link {
        white-space: nowrap;
        margin-right: 0.5rem;
        margin-bottom: 0;
    }
    
    .settings-section {
        padding: 1.5rem;
    }
    
    .settings-footer .d-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .settings-footer .d-flex > div {
        width: 100%;
    }
    
    .settings-footer .btn {
        width: 100%;
    }
}

/* Smooth animations */
.tab-pane {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Settings JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Volume slider update
    const volumeSlider = document.getElementById('volume');
    const volumeDisplay = document.getElementById('volume-display');
    
    if (volumeSlider && volumeDisplay) {
        volumeSlider.addEventListener('input', function() {
            volumeDisplay.textContent = this.value + '%';
        });
    }
    
    // Crossfade duration slider update
    const crossfadeSlider = document.getElementById('crossfade_duration');
    const crossfadeDisplay = document.getElementById('crossfade-display');
    const crossfadeCheckbox = document.getElementById('crossfade');
    
    if (crossfadeSlider && crossfadeDisplay) {
        crossfadeSlider.addEventListener('input', function() {
            crossfadeDisplay.textContent = this.value + 's';
        });
    }
    
    // Enable/disable crossfade duration based on checkbox
    if (crossfadeCheckbox && crossfadeSlider) {
        crossfadeCheckbox.addEventListener('change', function() {
            crossfadeSlider.disabled = !this.checked;
            if (!this.checked) {
                crossfadeSlider.style.opacity = '0.5';
            } else {
                crossfadeSlider.style.opacity = '1';
            }
        });
    }
    
    // Form change detection
    const form = document.getElementById('settingsForm');
    let originalFormData = new FormData(form);
    let hasChanges = false;
    
    function checkForChanges() {
        const currentFormData = new FormData(form);
        hasChanges = false;
        
        // Compare form data
        for (let [key, value] of currentFormData.entries()) {
            if (originalFormData.get(key) !== value) {
                hasChanges = true;
                break;
            }
        }
        
        // Update save button state
        const saveBtn = form.querySelector('button[name="update_preferences"]');
        if (saveBtn) {
            if (hasChanges) {
                saveBtn.classList.remove('btn-primary');
                saveBtn.classList.add('btn-success');
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes';
            } else {
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-primary');
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Settings';
            }
        }
    }
    
    // Monitor form changes
    form.addEventListener('change', checkForChanges);
    form.addEventListener('input', checkForChanges);
    
    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        });
    }, 5000);
    
    // Smooth tab switching
    const tabLinks = document.querySelectorAll('#settings-tab button');
    tabLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function(e) {
            const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
            if (targetPane) {
                targetPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});

// Reset to defaults function
function resetToDefaults() {
    if (confirm('Are you sure you want to reset all settings to their default values?')) {
        // Reset form fields to default values
        document.getElementById('theme').value = 'dark';
        document.getElementById('autoplay').checked = true;
        document.getElementById('shuffle').checked = false;
        document.getElementById('repeat').value = 'none';
        document.getElementById('volume').value = 70;
        document.getElementById('volume-display').textContent = '70%';
        document.getElementById('quality').value = 'high';
        document.getElementById('notifications').checked = true;
        document.getElementById('auto_add_to_history').checked = true;
        document.getElementById('show_lyrics').checked = true;
        document.getElementById('crossfade').checked = false;
        document.getElementById('crossfade_duration').value = 3;
        document.getElementById('crossfade_duration').disabled = true;
        document.getElementById('crossfade-display').textContent = '3s';
        document.getElementById('equalizer_preset').value = 'default';
        document.getElementById('language').value = 'en';
        document.getElementById('timezone').value = 'UTC';
        
        // Trigger change event to update UI
        document.getElementById('settingsForm').dispatchEvent(new Event('change'));
    }
}

// Apply theme changes immediately
document.getElementById('theme').addEventListener('change', function() {
    const theme = this.value;
    if (theme === 'light') {
        document.documentElement.style.setProperty('--background-main', '#ffffff');
        document.documentElement.style.setProperty('--background-card', '#f8f9fa');
        document.documentElement.style.setProperty('--background-hover', '#e9ecef');
        document.documentElement.style.setProperty('--text-white', '#212529');
        document.documentElement.style.setProperty('--text-light', '#6c757d');
    } else {
        // Reset to dark theme
        document.documentElement.style.setProperty('--background-main', '#0d1117');
        document.documentElement.style.setProperty('--background-card', '#161b22');
        document.documentElement.style.setProperty('--background-hover', '#21262d');
        document.documentElement.style.setProperty('--text-white', '#f0f6fc');
        document.documentElement.style.setProperty('--text-light', '#8b949e');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
