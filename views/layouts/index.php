<?php
use models\Users;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/** @var string $Title */
/** @var string $Content */

if (empty($Title)) $Title = 'Layttle';
if (empty($Content)) $Content = '';

// Відображення флеш-повідомлень (якщо є)
if (isset($_SESSION['flash_message'])): ?>
    <div class="container mt-4">
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_message']['type']); ?>">
            <?php echo htmlspecialchars($_SESSION['flash_message']['message']); ?>
        </div>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($Title, ENT_QUOTES, 'UTF-8') ?> — Соціальна мережа Layttle</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"/>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="/css/IndexMain.css">
    <link rel="stylesheet" href="/css/Footer.css">
</head>

<body>
<?php include_once __DIR__ . '/header.php'; ?>

<main class="container my-4">
    <h1><?= htmlspecialchars($Title, ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="content-wrapper">
        <?= $Content ?>
    </div>
</main>

<?php include_once __DIR__ . '/footer.php'; ?>
</body>
</html>