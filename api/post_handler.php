<?php
// Спрощена логіка для прикладу (підключи свій DB class)
global $db;
require_once '../core/DB.php';
session_start();
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user']['id'];

if ($data['action'] === 'toggle_like') {
    // Перевіряємо чи є лайк
    $check = $db->selectOne('likes', ['post_id' => $data['post_id'], 'user_id' => $user_id]);
    if ($check) {
        $db->pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->execute([$data['post_id'], $user_id]);
        echo json_encode(['status' => 'unliked']);
    } else {
        $db->pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$data['post_id'], $user_id]);
        echo json_encode(['status' => 'liked']);
    }
}

if ($data['action'] === 'delete_post') {
    // Перевірка автора і видалення
    $db->pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?")->execute([$data['post_id'], $user_id]);
    echo json_encode(['success' => true]);
}