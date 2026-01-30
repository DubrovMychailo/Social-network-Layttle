<?php

use core\DB;

if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../core/DB.php';

$userId = $_SESSION['user']['id'] ?? null;
$id = $_POST['id'] ?? null;

if(!$userId || !$id) {echo json_encode(['success'=>false]); exit;}

try{
    $db = new DB('localhost','layttle','Dubrov','2004Dubrov');
    $pdo = $db->pdo;
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
    $stmt->execute([$id,$userId]);
    echo json_encode(['success'=>true]);
}catch(Exception $e){
    echo json_encode(['success'=>false]);
}
