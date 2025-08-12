<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$track_id = isset($input['track_id']) ? (int)$input['track_id'] : 0;

if (!$track_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid track ID']);
    exit;
}

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND track_id = ?");
    $stmt->execute([$_SESSION['user_id'], $track_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND track_id = ?");
        $stmt->execute([$_SESSION['user_id'], $track_id]);
        $is_favorite = false;
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, track_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $track_id]);
        $is_favorite = true;
    }
    
    echo json_encode([
        'success' => true,
        'is_favorite' => $is_favorite
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to toggle favorite']);
}
?>
