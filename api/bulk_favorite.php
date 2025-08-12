<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['track_ids']) || !is_array($input['track_ids'])) {
    echo json_encode(['error' => 'Track IDs array required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$track_ids = array_map('intval', $input['track_ids']);
$added_count = 0;

try {
    $pdo->beginTransaction();
    
    foreach ($track_ids as $track_id) {
        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND track_id = ?");
        $stmt->execute([$user_id, $track_id]);
        
        if (!$stmt->fetch()) {
            // Add to favorites
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, track_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $track_id]);
            $added_count++;
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'added_count' => $added_count]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Database error']);
}
?>
