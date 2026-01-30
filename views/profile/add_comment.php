<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../core/DB.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
$postId = $_POST['post_id'] ?? null;
$text = trim($_POST['comment_text'] ?? '');

if (!$userId || !$postId || $text === '') {
    echo json_encode(['success' => false]);
    exit;
}

$db = new \core\DB('localhost', 'layttle', 'Dubrov', '2004Dubrov');
$db->insert('comments', [
    'post_id' => $postId,
    'user_id' => $userId,
    'comment_text' => $text,
    'created_at' => date('Y-m-d H:i:s')
]);

echo json_encode([
    'success' => true,
    'user_name' => $_SESSION['user']['firstname'],
    'text' => htmlspecialchars($text)
]);