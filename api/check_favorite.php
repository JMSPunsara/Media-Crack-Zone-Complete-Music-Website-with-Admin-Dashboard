<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get track ID
$track_id = isset($_GET['track_id']) ? (int)$_GET['track_id'] : 0;

if (!$track_id) {
    echo json_encode(['error' => 'Invalid track ID']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Check if track is in user's favorites
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND track_id = ?");
    $stmt->execute([$user_id, $track_id]);
    $is_favorite = $stmt->fetchColumn() > 0;
    
    echo json_encode([
        'success' => true,
        'is_favorite' => $is_favorite
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
