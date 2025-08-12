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
$filters = isset($input['filters']) ? $input['filters'] : [];
$exclude_played = isset($input['exclude_played']) ? $input['exclude_played'] : [];

if (!$track_id) {
    echo json_encode(['error' => 'Track ID required']);
    exit;
}

try {
    // Get current track info
    $stmt = $pdo->prepare("SELECT language_id, mood_id, artist FROM tracks WHERE id = ?");
    $stmt->execute([$track_id]);
    $current_track = $stmt->fetch();
    
    if (!$current_track) {
        echo json_encode(['error' => 'Track not found']);
        exit;
    }
    
    // Build similar tracks query
    $where_conditions = [];
    $params = [];
    
    // Exclude current track and already played tracks
    $exclude_ids = array_merge([$track_id], $exclude_played);
    $placeholders = str_repeat('?,', count($exclude_ids) - 1) . '?';
    $where_conditions[] = "t.id NOT IN ($placeholders)";
    $params = array_merge($params, $exclude_ids);
    
    // Priority matching (same language and mood)
    if ($current_track['language_id']) {
        $where_conditions[] = "(t.language_id = ? OR t.language_id IS NULL)";
        $params[] = $current_track['language_id'];
    }
    
    if ($current_track['mood_id']) {
        $where_conditions[] = "(t.mood_id = ? OR t.mood_id IS NULL)";
        $params[] = $current_track['mood_id'];
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;
    
    $stmt = $pdo->prepare("
        SELECT t.*, l.name as language_name, m.name as mood_name, m.color as mood_color,
               COALESCE(t.cover_image, 'default-cover.jpg') as cover_image,
               " . ($user_id ? "CASE WHEN f.track_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite" : "0 as is_favorite") . ",
               -- Similarity score
               (CASE WHEN t.language_id = ? THEN 2 ELSE 0 END +
                CASE WHEN t.mood_id = ? THEN 2 ELSE 0 END +
                CASE WHEN t.artist = ? THEN 1 ELSE 0 END) as similarity_score
        FROM tracks t 
        LEFT JOIN languages l ON t.language_id = l.id 
        LEFT JOIN moods m ON t.mood_id = m.id 
        " . ($user_id ? "LEFT JOIN favorites f ON t.id = f.track_id AND f.user_id = ?" : "") . "
        $where_clause
        ORDER BY similarity_score DESC, t.plays_count DESC, RAND()
        LIMIT 20
    ");
    
    // Add similarity parameters
    $similarity_params = [$current_track['language_id'], $current_track['mood_id'], $current_track['artist']];
    if ($user_id) {
        $similarity_params[] = $user_id;
    }
    
    $stmt->execute(array_merge($similarity_params, $params));
    $tracks = $stmt->fetchAll();
    
    // Add full URLs
    foreach ($tracks as &$track) {
        $track['file_path'] = UPLOAD_URL . 'music/' . $track['file_path'];
        $track['cover_image'] = UPLOAD_URL . 'covers/' . $track['cover_image'];
    }
    
    echo json_encode([
        'success' => true,
        'tracks' => $tracks,
        'count' => count($tracks)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load similar tracks']);
}
?>
