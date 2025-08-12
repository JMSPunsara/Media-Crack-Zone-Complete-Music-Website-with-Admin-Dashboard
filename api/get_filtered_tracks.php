<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$filters = isset($input['filters']) ? $input['filters'] : [];
$limit = isset($input['limit']) ? min((int)$input['limit'], 100) : 50;
$random = isset($input['random']) ? (bool)$input['random'] : false;
$exclude_played = isset($input['exclude_played']) ? $input['exclude_played'] : [];

try {
    $where_conditions = [];
    $params = [];
    
    // Exclude already played tracks
    if (!empty($exclude_played)) {
        $placeholders = str_repeat('?,', count($exclude_played) - 1) . '?';
        $where_conditions[] = "t.id NOT IN ($placeholders)";
        $params = array_merge($params, $exclude_played);
    }
    
    // Apply filters
    if (!empty($filters['language_id'])) {
        $where_conditions[] = "t.language_id = ?";
        $params[] = $filters['language_id'];
    }
    
    if (!empty($filters['mood_id'])) {
        $where_conditions[] = "t.mood_id = ?";
        $params[] = $filters['mood_id'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(t.title LIKE ? OR t.artist LIKE ? OR t.album LIKE ?)";
        $search_term = "%" . $filters['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;
    
    // Order clause - random if requested, otherwise by popularity
    $order_clause = $random ? "ORDER BY RAND()" : "ORDER BY t.plays_count DESC, t.created_at DESC";
    
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
               COALESCE(t.cover_image, 'default-cover.jpg') as cover_image,
               " . ($user_id ? "CASE WHEN f.track_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite" : "0 as is_favorite") . "
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        " . ($user_id ? "LEFT JOIN favorites f ON t.id = f.track_id AND f.user_id = ?" : "") . "
        $where_clause
        $order_clause 
        LIMIT ?
    ");
    
    $execute_params = [];
    if ($user_id) {
        $execute_params[] = $user_id;
    }
    $execute_params = array_merge($execute_params, $params, [$limit]);
    
    $stmt->execute($execute_params);
    $tracks = $stmt->fetchAll();
    
    // Add full URLs
    foreach ($tracks as &$track) {
        $track['file_path'] = UPLOAD_URL . 'music/' . $track['file_path'];
        $track['cover_image'] = UPLOAD_URL . 'covers/' . $track['cover_image'];
    }
    
    echo json_encode([
        'success' => true,
        'tracks' => $tracks,
        'count' => count($tracks),
        'filters_applied' => $filters
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load filtered tracks']);
}
?>
