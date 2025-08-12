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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['track_id']) || !is_numeric($input['track_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid track ID']);
    exit();
}

$track_id = (int)$input['track_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if track exists
    $stmt = $pdo->prepare("SELECT id FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Track not found']);
        exit();
    }

    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND track_id = ?");
    $stmt->execute([$user_id, $track_id]);
    $existing_favorite = $stmt->fetch();

    if ($existing_favorite) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND track_id = ?");
        $stmt->execute([$user_id, $track_id]);
        
        echo json_encode([
            'success' => true,
            'favorited' => false,
            'message' => 'Removed from favorites'
        ]);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, track_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $track_id]);
        
        echo json_encode([
            'success' => true,
            'favorited' => true,
            'message' => 'Added to favorites'
        ]);
    }

} catch (PDOException $e) {
    error_log("Favorite toggle error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
