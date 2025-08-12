<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? sanitize($input['query']) : '';

if (empty($query)) {
    echo json_encode(['tracks' => []]);
    exit;
}

try {
    $search_term = "%$query%";
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
               COALESCE(t.cover_image, 'default-cover.jpg') as cover_image
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        WHERE t.title LIKE ? OR t.artist LIKE ? OR t.album LIKE ?
        ORDER BY t.plays_count DESC, t.created_at DESC 
        LIMIT 20
    ");
    
    $stmt->execute([$search_term, $search_term, $search_term]);
    $tracks = $stmt->fetchAll();
    
    // Prepare track data for player
    foreach ($tracks as &$track) {
        $track['file_path'] = UPLOAD_URL . 'music/' . $track['file_path'];
        $track['cover_image'] = UPLOAD_URL . 'covers/' . $track['cover_image'];
    }
    
    echo json_encode(['tracks' => $tracks]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
?>
