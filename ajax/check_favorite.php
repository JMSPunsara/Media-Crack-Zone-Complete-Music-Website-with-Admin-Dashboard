<?php
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if track_id is provided
if (!isset($_GET['track_id']) || !is_numeric($_GET['track_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid track ID']);
    exit();
}

$track_id = (int)$_GET['track_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if track is favorited by user
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND track_id = ?");
    $stmt->execute([$user_id, $track_id]);
    $is_favorite = $stmt->fetch() !== false;

    echo json_encode([
        'success' => true,
        'is_favorite' => $is_favorite
    ]);

} catch (PDOException $e) {
    error_log("Check favorite error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
