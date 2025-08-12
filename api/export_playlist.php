<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

if (!isset($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Playlist ID required');
}

$playlist_id = intval($_GET['id']);

try {
    // Check if user owns the playlist or if it's public
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->execute([$playlist_id, $_SESSION['user_id']]);
    $playlist = $stmt->fetch();
    
    if (!$playlist) {
        header('HTTP/1.0 404 Not Found');
        exit('Playlist not found or access denied');
    }
    
    // Get playlist tracks with track details
    $stmt = $pdo->prepare("
        SELECT t.*, pt.position
        FROM playlist_tracks pt
        JOIN tracks t ON pt.track_id = t.id
        WHERE pt.playlist_id = ?
        ORDER BY pt.position
    ");
    $stmt->execute([$playlist_id]);
    $tracks = $stmt->fetchAll();
    
    // Create export data
    $export_data = [
        'playlist' => [
            'name' => $playlist['name'],
            'description' => $playlist['description'],
            'created_at' => $playlist['created_at'],
            'track_count' => count($tracks)
        ],
        'tracks' => []
    ];
    
    foreach ($tracks as $track) {
        $export_data['tracks'][] = [
            'position' => $track['position'],
            'title' => $track['title'],
            'artist' => $track['artist'],
            'album' => $track['album'],
            'duration' => $track['duration'],
            'genre' => $track['genre']
        ];
    }
    
    // Set headers for download
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $playlist['name']) . '_playlist.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output JSON
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Export playlist error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit('Database error occurred');
}
?>
