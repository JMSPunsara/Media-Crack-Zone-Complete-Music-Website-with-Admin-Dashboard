<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
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
    $stmt = $pdo->prepare("UPDATE tracks SET plays_count = plays_count + 1 WHERE id = ?");
    $stmt->execute([$track_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update play count']);
}
?>
