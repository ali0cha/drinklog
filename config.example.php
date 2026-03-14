<?php
// ============================================================
//  DrinkLog – Configuration
//  Copy this file from config.example.php and fill in values
// ============================================================

// --- Database ---
define('DB_HOST',    'localhost');
define('DB_NAME',    'drinklog');
define('DB_USER',    'your_db_user');
define('DB_PASS',    'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// --- App ---
define('APP_NAME', 'DrinkLog');
define('APP_URL',  'https://yourdomain.com/drinklog');  // no trailing slash

// --- WHO limits (standard drinks / units = 10 g pure alcohol) ---
define('WHO_DAILY_LIMIT',  2.0);   // units per day
define('WHO_WEEKLY_LIMIT', 14.0);  // units per week

// --- SMTP ---
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'your_email@gmail.com');
define('SMTP_PASS',      'your_app_password');   // Gmail App Password
define('SMTP_FROM',      'your_email@gmail.com');
define('SMTP_FROM_NAME', APP_NAME);

// --- Session ---
define('SESSION_NAME', 'drinklog_sess');

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-login via "Se souvenir de moi" cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['dl_remember'])) {
    require_once __DIR__ . '/db.php';
    $stmt = getDB()->prepare(
        'SELECT id FROM users WHERE remember_token = ? AND remember_expires > NOW()'
    );
    $stmt->execute([$_COOKIE['dl_remember']]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['user_id'] = $row['id'];
        // Rolling renewal: another 30 days
        $newToken   = bin2hex(random_bytes(32));
        $newExpires = date('Y-m-d H:i:s', strtotime('+30 days'));
        getDB()->prepare(
            'UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?'
        )->execute([$newToken, $newExpires, $row['id']]);
        setcookie('dl_remember', $newToken, time() + 30 * 86400, '/', '', true, true);
    } else {
        setcookie('dl_remember', '', time() - 3600, '/');
    }
}
