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

if (!isset($input['playlist_id']) || !isset($input['name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$source_playlist_id = intval($input['playlist_id']);
$new_name = sanitize($input['name']);

if (empty($new_name)) {
    echo json_encode(['success' => false, 'message' => 'Playlist name cannot be empty']);
    exit;
}

try {
    // Check if user owns the source playlist or if it's public
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->execute([$source_playlist_id, $_SESSION['user_id']]);
    $source_playlist = $stmt->fetch();
    
    if (!$source_playlist) {
        echo json_encode(['success' => false, 'message' => 'Source playlist not found or access denied']);
        exit;
    }
    
    // Check if new playlist name already exists for current user
    $stmt = $pdo->prepare("SELECT id FROM playlists WHERE user_id = ? AND name = ?");
    $stmt->execute([$_SESSION['user_id'], $new_name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A playlist with this name already exists']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create new playlist
        $description = "Copy of " . $source_playlist['name'];
        if ($source_playlist['user_id'] != $_SESSION['user_id']) {
            // Get original creator's name
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$source_playlist['user_id']]);
            $creator = $stmt->fetch();
            if ($creator) {
                $description .= " by " . $creator['username'];
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name, description, is_public, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->execute([$_SESSION['user_id'], $new_name, $description]);
        $new_playlist_id = $pdo->lastInsertId();
        
        // Get all tracks from source playlist
        $stmt = $pdo->prepare("
            SELECT track_id, position 
            FROM playlist_tracks 
            WHERE playlist_id = ? 
            ORDER BY position
        ");
        $stmt->execute([$source_playlist_id]);
        $tracks = $stmt->fetchAll();
        
        // Copy tracks to new playlist
        foreach ($tracks as $track) {
            $stmt = $pdo->prepare("INSERT INTO playlist_tracks (playlist_id, track_id, position, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$new_playlist_id, $track['track_id'], $track['position']]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Playlist '$new_name' created with " . count($tracks) . " tracks",
            'playlist_id' => $new_playlist_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Duplicate playlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
