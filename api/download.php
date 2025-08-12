<?php
require_once '../config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Track ID required';
    exit;
}

$track_id = (int)$_GET['id'];

try {
    // Get track info
    $stmt = $pdo->prepare("SELECT * FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    $track = $stmt->fetch();
    
    if (!$track) {
        http_response_code(404);
        echo 'Track not found';
        exit;
    }
    
    $file_path = UPLOAD_PATH . 'music/' . $track['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    
    // Update download count
    $stmt = $pdo->prepare("UPDATE tracks SET downloads_count = downloads_count + 1 WHERE id = ?");
    $stmt->execute([$track_id]);
    
    // Record download for logged-in users
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("INSERT INTO downloads (user_id, track_id, downloaded_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $track_id]);
    }
    
    // Set headers for download
    $filename = sanitize($track['artist'] . ' - ' . $track['title'] . '.mp3');
    
    header('Content-Type: audio/mpeg');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file
    readfile($file_path);
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Download failed';
}
?>
