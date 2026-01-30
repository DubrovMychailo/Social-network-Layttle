<?php
if (session_status() == PHP_SESSION_NONE) session_start();

use core\DB;
require_once __DIR__ . '/../../core/DB.php';

header('Content-Type: application/json; charset=utf-8');

// --- Перевірка авторизації ---
$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Користувач не авторизований']);
    exit;
}

// --- Контент поста ---
$content = trim($_POST['content'] ?? '');
if ($content === '') {
    echo json_encode(['success' => false, 'error' => 'Пустий контент']);
    exit;
}

// --- Папка для завантаження ---
$uploadDir = __DIR__ . '/../../uploads/posts/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Папка uploads/posts недоступна для запису']);
    exit;
}

// --- Завантаження медіа ---
$photos = [];
$videos = [];
$gifs = [];

if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['tmp_name'] as $index => $tmpName) {
        $originalName = $_FILES['media']['name'][$index];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . basename($originalName);
        $target = $uploadDir . $filename;

        if (in_array($ext, ['jpg','jpeg','png','webp']) && count($photos) < 10) {
            if (move_uploaded_file($tmpName, $target)) $photos[] = $filename;
        } elseif (in_array($ext, ['gif']) && count($gifs) < 10) {
            if (move_uploaded_file($tmpName, $target)) $gifs[] = $filename;
        } elseif (in_array($ext, ['mp4','mov','avi','webm']) && count($videos) < 10) {
            if (move_uploaded_file($tmpName, $target)) $videos[] = $filename;
        }
    }
}

try {
    // --- Підключення до бази ---
    $db = new DB('localhost', 'layttle', 'Dubrov', '2004Dubrov');
    $pdo = $db->pdo;

    // --- Вставка поста ---
    $stmt = $pdo->prepare("
        INSERT INTO posts 
        (user_id, content, photos, videos, gifs, created_at, updated_at, likes, shares)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW(), JSON_ARRAY(), 0)
    ");

    $stmt->execute([
        $userId,
        $content,
        json_encode($photos, JSON_UNESCAPED_UNICODE),
        json_encode($videos, JSON_UNESCAPED_UNICODE),
        json_encode($gifs, JSON_UNESCAPED_UNICODE)
    ]);

    $postId = $pdo->lastInsertId();

    // --- Отримуємо пост з бази для відправки клієнту ---
    $stmt = $pdo->prepare("
        SELECT p.*, u.firstname, u.lastname, u.photo AS user_photo
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Не знайдено пост після вставки']);
        exit;
    }

    // --- Підготовка HTML ---
    $post['photos'] = $photos;
    $post['videos'] = $videos;
    $post['gifs'] = $gifs;

    ob_start();
    ?>
    <div class="post mb-3" data-id="<?= htmlspecialchars($post['id']) ?>">
        <div class="d-flex align-items-center mb-2">
            <img src="<?= (!empty($post['user_photo'])) ? '//Layttle/uploads/' . htmlspecialchars($post['user_photo']) : '//Layttle/uploads/nophoto.webp' ?>"
                 alt="Аватар" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">            <strong class="ms-2">
                <?= htmlspecialchars($post['firstname'] . ' ' . $post['lastname']) ?>
            </strong>
        </div>

        <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

        <div class="media-wrap">
            <?php foreach ($post['photos'] as $photo): ?>
                <img src="/uploads/posts/<?= htmlspecialchars($photo) ?>" class="media-item" onclick="openMedia(this)">
            <?php endforeach; ?>
            <?php foreach ($post['videos'] as $video): ?>
                <video controls class="media-item">
                    <source src="/uploads/posts/<?= htmlspecialchars($video) ?>" type="video/mp4">
                </video>
            <?php endforeach; ?>
            <?php foreach ($post['gifs'] as $gif): ?>
                <img src="/uploads/posts/<?= htmlspecialchars($gif) ?>" class="media-item" onclick="openMedia(this)">
            <?php endforeach; ?>
        </div>

        <small class="text-muted">Опубліковано: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($post['created_at']))) ?></small>

        <?php if ($userId == $post['user_id']): ?>
            <div>
                <button class="btn btn-sm btn-warning edit-post">✏️ Редагувати</button>
                <button class="btn btn-sm btn-danger delete-post">🗑️ Видалити</button>
            </div>
        <?php endif; ?>

        <div class="reactions mt-2">
            <?php foreach (['👍','❤️','😂','😮','😢'] as $emoji): ?>
                <button class="like-btn" data-reaction="<?= htmlspecialchars($emoji) ?>"><?= htmlspecialchars($emoji) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="comments mt-2">
            <label>
                <input type="text" class="comment-input" placeholder="Напишіть коментар...">
            </label>
            <div class="comment-list"></div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    exit;

} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../../error.log', date('Y-m-d H:i:s') . " | DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Помилка при записі в базу даних']);
    exit;
}
