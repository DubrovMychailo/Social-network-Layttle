<?php
if (session_status() == PHP_SESSION_NONE) session_start();
$userData = $userData ?? null;
$isOwnProfile = $isOwnProfile ?? false;
$friends = $friends ?? [];
$posts = $posts ?? [];
$currentUser = $_SESSION['user'] ?? null;

// Підрахунок загальної кількості медіа для сайдбару
$totalMediaCount = 0;
foreach ($posts as $p) {
    $pPhotos = is_array($p['photos']) ? $p['photos'] : (json_decode($p['photos'] ?? '[]', true) ?: []);
    $pVideos = is_array($p['videos']) ? $p['videos'] : (json_decode($p['videos'] ?? '[]', true) ?: []);
    $totalMediaCount += count($pPhotos) + count($pVideos);
}

if (!empty($posts)) {
    foreach ($posts as &$post) {
        $fields = ['photos', 'videos', 'likes', 'comments_data'];
        foreach ($fields as $field) {
            if (isset($post[$field]) && is_string($post[$field])) {
                $post[$field] = json_decode($post[$field], true) ?: [];
            } elseif (!isset($post[$field])) {
                $post[$field] = [];
            }
        }
    }
    unset($post);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($userData['firstname'] ?? 'Профіль') ?> | Layttle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f0f2f5; color: #1c1e21; font-family: -apple-system, system-ui, sans-serif; }
        .profile-side-card { border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border: none; overflow: hidden; }
        .stat-item { flex: 1; text-align: center; padding: 10px; border-right: 1px solid #f0f2f5; }
        .stat-item:last-child { border-right: none; }
        .stat-num { display: block; font-weight: 700; font-size: 1.1rem; }
        .stat-label { font-size: 0.8rem; color: #65676b; text-transform: uppercase; }
        .post-card { border-radius: 12px; background: #fff; border: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .post-header { padding: 12px 16px; display: flex; align-items: center; position: relative; }
        .avatar-md { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .post-author-name { font-weight: 600; font-size: 0.95rem; color: #050505; text-decoration: none; }
        .post-meta { font-size: 0.8rem; color: #65676b; }
        .media-container { width: 100%; max-width: 500px; height: 500px; margin: 10px auto; background: #000; position: relative; border-radius: 8px; overflow: hidden; }
        .carousel-item img, .carousel-item video { width: 100%; height: 500px; object-fit: cover; cursor: pointer; }
        .media-counter { position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.6); color: #fff; padding: 3px 12px; border-radius: 20px; font-size: 0.8rem; z-index: 10; }
        .post-footer { padding: 10px 16px; border-top: 1px solid #ebedf0; }
        .reaction-wrapper { display: flex; align-items: center; background: #f0f2f5; border-radius: 20px; padding: 4px 12px; width: fit-content; position: relative; }
        .like-btn-main { border: none; background: none; font-weight: 600; color: #65676b; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .reaction-menu { display: none; position: absolute; bottom: 42px; left: 0; background: white; padding: 5px 10px; border-radius: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.15); z-index: 1000; gap: 10px; }
        .reaction-wrapper.active .reaction-menu { display: flex; }
        .emoji-item { border: none; background: none; font-size: 1.6rem; transition: transform 0.2s; cursor: pointer; padding: 0; }
        .emoji-item:hover { transform: scale(1.4) translateY(-5px); }
        .divider { width: 1px; height: 16px; background: #ccd0d5; margin: 0 8px; }
        .likes-count { font-weight: 500; color: #65676b; }
        #customLightbox { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); justify-content: center; align-items: center; }
        .lb-arrow { background: none; border: none; color: white; font-size: 5rem; font-weight: 100; position: absolute; top: 50%; transform: translateY(-50%); cursor: pointer; transition: 0.3s; }
        .lb-arrow:hover { color: #aaa; }
        .lb-prev { left: 30px; } .lb-next { right: 30px; }
        .lb-close { position: absolute; top: 20px; right: 35px; color: white; font-size: 2.5rem; cursor: pointer; }
        #createPostModal .media-preview-container { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
        #createPostModal .media-preview-item { position: relative; width: 80px; height: 80px; overflow: hidden; border-radius: 8px; }
        #createPostModal .media-preview-item img, #createPostModal .media-preview-item video { width: 100%; height: 100%; object-fit: cover; }
        #createPostModal .remove-media-btn { position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 0.8rem; line-height: 1; text-align: center; }
    </style>
</head>
<body>

<div id="customLightbox">
    <span class="lb-close">&times;</span>
    <button class="lb-arrow lb-prev">＜</button>
    <div id="lb-content" class="text-center"></div>
    <div id="lb-counter" style="position:absolute; bottom:20px; color:white; font-weight:300;"></div>
    <button class="lb-arrow lb-next">＞</button>
</div>

<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPostModalLabel">Створити допис</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPostForm" action="/api/post_handler.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <textarea class="form-control mb-3" name="content" rows="3" placeholder="Що у вас нового, <?= htmlspecialchars($currentUser['firstname'] ?? 'друже') ?>?" required></textarea>
                    <input type="file" class="form-control" id="postMediaInput" name="media[]" accept="image/*,video/*" multiple max="5">
                    <small class="text-muted">Можна вибрати до 5 фото або відео.</small>
                    <div id="mediaPreviews" class="media-preview-container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-primary">Опублікувати</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <div class="profile-side-card p-3 mb-3 text-center">
                <img src="<?= ($userData['photo'] ?? null) ?: '/uploads/nophoto.webp' ?>" class="rounded-circle mb-3 border" style="width:120px; height:120px; object-fit:cover;">
                <h5><?= htmlspecialchars(($userData['firstname'] ?? '') . ' ' . ($userData['lastname'] ?? '')) ?></h5>
                <p class="text-muted small">@<?= htmlspecialchars($userData['login'] ?? 'user') ?></p>

                <div class="d-flex mt-3">
                    <div class="stat-item">
                        <span class="stat-num"><?= count($friends) ?></span>
                        <span class="stat-label">друзів</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num"><?= count($posts) ?></span>
                        <span class="stat-label">дописів</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num"><?= $totalMediaCount ?></span>
                        <span class="stat-label">медіа</span>
                    </div>
                </div>
            </div>

            <div class="profile-side-card p-3">
                <h6 class="mb-3">Усі медіа профілю</h6>
                <div class="row g-1">
                    <?php
                    $limit = 9; $count = 0;
                    foreach($posts as $p):
                        $pPhotos = is_array($p['photos']) ? $p['photos'] : (json_decode($p['photos'] ?? '[]', true) ?: []);
                        $pVideos = is_array($p['videos']) ? $p['videos'] : (json_decode($p['videos'] ?? '[]', true) ?: []);
                        $postMedia = array_merge($pPhotos, $pVideos);
                        foreach($postMedia as $mediaItem):
                            if($count < $limit): $count++; ?>
                                <div class="col-4">
                                    <img src="/uploads/posts/<?= htmlspecialchars($mediaItem) ?>" class="img-fluid rounded" style="aspect-ratio:1; object-fit:cover;">
                                </div>
                            <?php endif;
                        endforeach;
                    endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($isOwnProfile): ?>
                <div class="post-card p-3 d-flex align-items-center gap-3 mb-3">
                    <img src="<?= ($currentUser['photo'] ?? null) ?: '/uploads/nophoto.webp' ?>" class="rounded-circle" style="width:40px; height:40px;">
                    <div class="bg-light flex-grow-1 px-3 py-2 rounded-pill text-muted" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        Що у вас нового, <?= htmlspecialchars($currentUser['firstname'] ?? 'друже') ?>?
                    </div>
                </div>
            <?php endif; ?>

            <div id="postsContainer">
                <?php foreach ($posts as $post): ?>
                    <div class="post-card post" data-id="<?= $post['id'] ?>">
                        <div class="post-header">
                            <img src="<?= ($post['user_photo'] ?? null) ?: '/uploads/nophoto.webp' ?>" class="avatar-md">
                            <div class="ms-2 post-info">
                                <a href="#" class="post-author-name"><?= htmlspecialchars(($post['firstname'] ?? '') . ' ' . ($post['lastname'] ?? '')) ?></a>
                                <div class="post-meta">
                                    <?= date('j F в H:i', strtotime($post['created_at'])) ?>
                                    <?php if(($post['updated_at'] ?? '') > $post['created_at']): ?> <span class="ms-1">· Редаговано</span> <?php endif; ?>
                                </div>
                            </div>
                            <?php if($isOwnProfile): ?>
                                <div class="ms-auto dropdown">
                                    <i class="bi bi-three-dots text-muted" style="cursor:pointer" data-bs-toggle="dropdown"></i>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i>Редагувати</a></li>
                                        <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Видалити</a></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="px-3 pb-2"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></div>

                        <?php
                        $allPostMedia = [];
                        $pPhotos = is_array($post['photos']) ? $post['photos'] : (json_decode($post['photos'] ?? '[]', true) ?: []);
                        foreach($pPhotos as $f) if(!empty($f)) $allPostMedia[] = ['t'=>'img', 'f'=>$f];

                        $pVideos = is_array($post['videos']) ? $post['videos'] : (json_decode($post['videos'] ?? '[]', true) ?: []);
                        foreach($pVideos as $f) if(!empty($f)) $allPostMedia[] = ['t'=>'vid', 'f'=>$f];
                        ?>

                        <?php if (!empty($allPostMedia)): ?>
                            <div class="media-container">
                                <div class="media-counter">1 / <?= count($allPostMedia) ?></div>
                                <div id="carousel-<?= $post['id'] ?>" class="carousel slide" data-bs-ride="false">
                                    <div class="carousel-inner">
                                        <?php foreach ($allPostMedia as $i => $item): ?>
                                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                                <?php if($item['t'] == 'img'): ?>
                                                    <img src="/uploads/posts/<?= htmlspecialchars($item['f']) ?>" class="lb-trigger" data-media='<?= json_encode($allPostMedia) ?>' data-index="<?= $i ?>">
                                                <?php else: ?>
                                                    <video class="lb-trigger" data-media='<?= json_encode($allPostMedia) ?>' data-index="<?= $i ?>"><source src="/uploads/posts/<?= htmlspecialchars($item['f']) ?>" type="video/mp4"></video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if(count($allPostMedia) > 1): ?>
                                        <button class="carousel-control-prev" data-bs-target="#carousel-<?= $post['id'] ?>" data-bs-slide="prev">＜</button>
                                        <button class="carousel-control-next" data-bs-target="#carousel-<?= $post['id'] ?>" data-bs-slide="next">＞</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="post-footer">
                            <div class="d-flex align-items-center">
                                <?php $isLiked = in_array($currentUser['id'] ?? 0, $post['likes'] ?? []); ?>
                                <div class="reaction-wrapper <?= $isLiked ? 'is-liked' : '' ?>">
                                    <div class="reaction-menu">
                                        <?php foreach(['👍','❤️','😂','😮','😢'] as $emoji): ?>
                                            <button class="emoji-item" data-emoji="<?= $emoji ?>"><?= $emoji ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="like-btn-main">
                                        <span class="active-emoji"><?= $isLiked ? '❤️' : '👍' ?></span>
                                        <span>Подобається</span>
                                        <div class="divider"></div>
                                        <span class="likes-count"><?= count($post['likes'] ?? []) ?></span>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <?php foreach(($post['comments_data'] ?? []) as $c): ?>
                                    <div class="d-flex gap-2 mb-2 small">
                                        <span class="fw-bold"><?= htmlspecialchars($c['firstname'] ?? 'Користувач') ?>:</span>
                                        <span><?= htmlspecialchars($c['comment_text'] ?? '') ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0 mt-2" placeholder="Напишіть коментар...">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lb = document.getElementById('customLightbox');
        const lbContent = document.getElementById('lb-content');
        const lbCounter = document.getElementById('lb-counter');
        let mList = [], mIdx = 0;

        function updateLB() {
            const item = mList[mIdx];
            lbContent.innerHTML = item.t === 'img'
                ? `<img src="/uploads/posts/${item.f}" style="max-height:85vh; max-width:90vw; object-fit:contain;">`
                : `<video controls autoplay style="max-height:85vh; max-width:90vw;"><source src="/uploads/posts/${item.f}" type="video/mp4"></video>`;
            lbCounter.innerText = `${mIdx + 1} / ${mList.length}`;
        }

        document.addEventListener('click', (e) => {
            const t = e.target;

            if (t.classList.contains('lb-trigger')) {
                mList = JSON.parse(t.dataset.media);
                mIdx = parseInt(t.dataset.index);
                updateLB();
                lb.style.display = 'flex';
            }

            if (t.classList.contains('lb-next')) { mIdx = (mIdx + 1) % mList.length; updateLB(); }
            if (t.classList.contains('lb-prev')) { mIdx = (mIdx - 1 + mList.length) % mList.length; updateLB(); }
            if (t.classList.contains('lb-close') || t === lb) { lb.style.display = 'none'; lbContent.innerHTML = ''; }

            if (t.closest('.like-btn-main')) {
                const wrap = t.closest('.reaction-wrapper');
                const postElement = t.closest('.post');
                const postId = postElement.dataset.id;
                const count = wrap.querySelector('.likes-count');
                const emojiSpan = wrap.querySelector('.active-emoji');

                wrap.classList.toggle('active');

                if(!t.classList.contains('emoji-item')) {
                    let actionType = wrap.classList.contains('is-liked') ? 'unlike' : 'like';
                    if(wrap.classList.contains('is-liked')) {
                        wrap.classList.remove('is-liked');
                        count.innerText = Math.max(0, parseInt(count.innerText) - 1);
                        emojiSpan.innerText = '👍';
                    } else {
                        wrap.classList.add('is-liked');
                        count.innerText = parseInt(count.innerText) + 1;
                        emojiSpan.innerText = '❤️';
                    }

                    fetch('/api/post_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${actionType}&post_id=${postId}`
                    });
                }
            }

            if (t.classList.contains('emoji-item')) {
                const wrap = t.closest('.reaction-wrapper');
                const postElement = t.closest('.post');
                const postId = postElement.dataset.id;
                const emoji = t.dataset.emoji;

                wrap.querySelector('.active-emoji').innerText = emoji;
                wrap.classList.remove('active');
                wrap.classList.add('is-liked');

                fetch('/api/post_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=like&post_id=${postId}&emoji=${encodeURIComponent(emoji)}`
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            wrap.querySelector('.likes-count').innerText = data.new_count;
                        }
                    });
            }
        });

        document.querySelectorAll('.carousel').forEach(c => {
            c.addEventListener('slid.bs.carousel', function () {
                const badge = this.closest('.media-container').querySelector('.media-counter');
                const items = this.querySelectorAll('.carousel-item');
                const activeIdx = Array.from(items).indexOf(this.querySelector('.active')) + 1;
                badge.innerText = `${activeIdx} / ${items.length}`;
            });
        });

        const postMediaInput = document.getElementById('postMediaInput');
        const mediaPreviews = document.getElementById('mediaPreviews');
        let selectedFiles = [];

        postMediaInput.addEventListener('change', (event) => {
            const files = Array.from(event.target.files);
            if (selectedFiles.length + files.length > 5) {
                alert('Можна завантажити не більше 5 медіа-файлів.');
                event.target.value = '';
                return;
            }
            selectedFiles = selectedFiles.concat(files);
            renderMediaPreviews();
        });

        function renderMediaPreviews() {
            mediaPreviews.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const previewItem = document.createElement('div');
                    previewItem.classList.add('media-preview-item');
                    if (file.type.startsWith('image/')) {
                        previewItem.innerHTML = `<img src="${e.target.result}">`;
                    } else if (file.type.startsWith('video/')) {
                        previewItem.innerHTML = `<video src="${e.target.result}" muted autoplay loop></video>`;
                    }
                    const removeBtn = document.createElement('button');
                    removeBtn.classList.add('remove-media-btn');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = () => {
                        selectedFiles.splice(index, 1);
                        renderMediaPreviews();
                    };
                    previewItem.appendChild(removeBtn);
                    mediaPreviews.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            });
        }

        const createPostForm = document.getElementById('createPostForm');
        createPostForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData();
            formData.append('content', createPostForm.querySelector('textarea[name="content"]').value);
            selectedFiles.forEach(file => {
                formData.append('media[]', file);
            });

            const response = await fetch('/api/post_handler.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Помилка: ' + result.message);
                }
            } else {
                alert('Помилка сервера при створенні допису.');
            }
        });
    });
</script>
</body>
</html>