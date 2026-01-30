<?php

use core\DB;

if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../core/DB.php';

$userId = $_SESSION['user']['id'] ?? null;
$id = $_POST['id'] ?? null;
$content = trim($_POST['content'] ?? '');

if (!$userId || !$id || !$content) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $db = new DB('localhost', 'layttle', 'Dubrov', '2004Dubrov');
    $pdo = $db->pdo;
    $stmt = $pdo->prepare("UPDATE posts SET content=?, updated_at=NOW() WHERE id=? AND user_id=?");
    $stmt->execute([$content, $id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
