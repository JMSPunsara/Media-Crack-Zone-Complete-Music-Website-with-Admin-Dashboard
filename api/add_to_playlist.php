<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['playlist_id']) || !isset($input['track_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$playlist_id = intval($input['playlist_id']);
$track_id = intval($input['track_id']);

try {
    // Check if user owns the playlist
    $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Playlist not found or access denied']);
        exit;
    }
    
    // Check if track exists
    $stmt = $pdo->prepare("SELECT id FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Track not found']);
        exit;
    }
    
    // Check if track is already in playlist
    $stmt = $pdo->prepare("SELECT id FROM playlist_tracks WHERE playlist_id = ? AND track_id = ?");
    $stmt->execute([$playlist_id, $track_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Track already in playlist']);
        exit;
    }
    
    // Get next position
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM playlist_tracks WHERE playlist_id = ?");
    $stmt->execute([$playlist_id]);
    $next_position = $stmt->fetchColumn();
    
    // Add track to playlist
    $stmt = $pdo->prepare("INSERT INTO playlist_tracks (playlist_id, track_id, position, created_at) VALUES (?, ?, ?, NOW())");
    
    if ($stmt->execute([$playlist_id, $track_id, $next_position])) {
        // Update playlist updated_at timestamp
        $stmt = $pdo->prepare("UPDATE playlists SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$playlist_id]);
        
        echo json_encode(['success' => true, 'message' => 'Track added to playlist successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add track to playlist']);
    }
    
} catch (PDOException $e) {
    error_log("Add to playlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
