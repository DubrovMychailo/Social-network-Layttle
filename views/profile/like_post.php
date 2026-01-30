<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../core/DB.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
$postId = $_POST['post_id'] ?? null;
$reaction = $_POST['reaction'] ?? '👍';

if (!$userId || !$postId) {
    echo json_encode(['success' => false, 'error' => 'Auth error']);
    exit;
}

try {
    $db = new \core\DB('localhost', 'layttle', 'Dubrov', '2004Dubrov');
    $post = $db->selectOne('posts', ['id' => $postId]);

    if (!$post) throw new Exception("Пост не знайдено");

    // В твоїй таблиці `posts` поле `likes` — це JSON масив ID юзерів
    $likes = !empty($post['likes']) ? json_decode($post['likes'], true) : [];
    if (!is_array($likes)) $likes = [];

    // Логіка: додаємо юзера, якщо його там немає
    if (!in_array($userId, $likes)) {
        $likes[] = (int)$userId;
    }

    $db->update('posts', [
        'likes' => json_encode($likes),
        'updated_at' => date('Y-m-d H:i:s') // Можна використати для збереження останньої реакції
    ], ['id' => $postId]);

    echo json_encode([
        'success' => true,
        'new_count' => count($likes),
        'emoji' => $reaction
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}