<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'set_theme') {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    if (!empty($_SESSION['user_id'])) {
        getDB()->prepare('UPDATE users SET theme = ? WHERE id = ?')
               ->execute([$theme, $_SESSION['user_id']]);
    }
    setcookie('dl_theme', $theme, time() + 31536000, '/');
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
