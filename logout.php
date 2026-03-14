<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Invalider le token en base et supprimer le cookie
if (!empty($_COOKIE['dl_remember'])) {
    getDB()->prepare(
        'UPDATE users SET remember_token = NULL, remember_expires = NULL
         WHERE remember_token = ?'
    )->execute([$_COOKIE['dl_remember']]);
    setcookie('dl_remember', '', time() - 3600, '/');
}

session_destroy();
header('Location: index.php');
exit;
