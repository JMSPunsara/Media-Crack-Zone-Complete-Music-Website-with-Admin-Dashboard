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
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['track_id']) || !is_numeric($input['track_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid track ID']);
    exit;
}

$track_id = (int)$input['track_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if track exists
    $stmt = $pdo->prepare("SELECT id FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Track not found']);
        exit;
    }
    
    // Insert play history record
    $stmt = $pdo->prepare("INSERT INTO play_history (user_id, track_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $track_id]);
    
    // Update track play count
    $stmt = $pdo->prepare("UPDATE tracks SET plays_count = plays_count + 1 WHERE id = ?");
    $stmt->execute([$track_id]);
    
    echo json_encode(['success' => true, 'message' => 'Play recorded']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
