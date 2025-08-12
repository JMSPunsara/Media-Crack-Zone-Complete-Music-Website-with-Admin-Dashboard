<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'media_crack_zone_music');
define('DB_USER', 'root');        // XAMPP default
define('DB_PASS', '');            // XAMPP default (empty password)

// Site configuration
define('SITE_NAME', 'Media Crack Zone');

// Detect if running on development server or get current server info
$server_port = $_SERVER['SERVER_PORT'] ?? '80';
$server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';

if ($server_name === 'localhost' || $server_name === '127.0.0.1') {
    // Local development on XAMPP
    define('SITE_URL', 'http://localhost/music');
    define('UPLOAD_URL', 'http://localhost/music/uploads/');
} else {
    // Production server
    define('SITE_URL', 'https://music.mediacrackzone.com');
    define('UPLOAD_URL', SITE_URL . '/uploads/');
}

define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
    mkdir(UPLOAD_PATH . 'music/', 0777, true);
    mkdir(UPLOAD_PATH . 'covers/', 0777, true);
    mkdir(UPLOAD_PATH . 'avatars/', 0777, true);
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Include SEO helper functions
require_once __DIR__ . '/includes/seo.php';

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function redirect($url) {
    // Handle absolute URLs
    if (strpos($url, 'http') === 0) {
        header("Location: $url");
    } else {
        // Handle relative URLs - prepend SITE_URL
        $redirect_url = SITE_URL . '/' . ltrim($url, '/');
        header("Location: $redirect_url");
    }
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    } else {
        return sprintf("%d:%02d", $minutes, $seconds);
    }
}

function formatTotalDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return sprintf("%dh %dm", $hours, $minutes);
    } else {
        return sprintf("%dm", $minutes);
    }
}

function uploadFile($file, $type = 'music') {
    $uploadDir = UPLOAD_PATH . $type . '/';
    $allowedTypes = [
        'music' => ['mp3', 'wav', 'ogg', 'm4a'],
        'covers' => ['jpg', 'jpeg', 'png', 'gif'],
        'avatars' => ['jpg', 'jpeg', 'png', 'gif']
    ];
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes[$type])) {
        return false;
    }
    
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $fileName;
    }
    
    return false;
}

function getAudioDuration($filePath) {
    // Simple duration calculation for demo
    // In production, use getID3 library or similar
    return rand(120, 300); // Random duration between 2-5 minutes
}
?>
