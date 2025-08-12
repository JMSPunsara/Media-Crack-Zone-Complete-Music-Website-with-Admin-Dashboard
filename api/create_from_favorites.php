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

if (!isset($input['name'])) {
    echo json_encode(['success' => false, 'message' => 'Playlist name is required']);
    exit;
}

$name = sanitize($input['name']);

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Playlist name cannot be empty']);
    exit;
}

try {
    // Check if user has favorites
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites_count = $stmt->fetchColumn();
    
    if ($favorites_count == 0) {
        echo json_encode(['success' => false, 'message' => 'No favorites found']);
        exit;
    }
    
    // Check if playlist name already exists
    $stmt = $pdo->prepare("SELECT id FROM playlists WHERE user_id = ? AND name = ?");
    $stmt->execute([$_SESSION['user_id'], $name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A playlist with this name already exists']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create playlist
        $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name, description, is_public, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->execute([$_SESSION['user_id'], $name, 'Created from your favorite tracks']);
        $playlist_id = $pdo->lastInsertId();
        
        // Get all favorite tracks
        $stmt = $pdo->prepare("
            SELECT track_id 
            FROM favorites 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $favorite_tracks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add tracks to playlist
        $position = 1;
        foreach ($favorite_tracks as $track_id) {
            $stmt = $pdo->prepare("INSERT INTO playlist_tracks (playlist_id, track_id, position, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$playlist_id, $track_id, $position]);
            $position++;
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Playlist '$name' created with $favorites_count tracks from your favorites",
            'playlist_id' => $playlist_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Create from favorites error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
