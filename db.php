<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

// ---- Helpers ------------------------------------------------

function calculateUnits(float $volume_ml, float $degree): float {
    // 1 unité (OMS Europe) = 10g d'alcool pur ; densité alcool = 0,789 g/ml
    return ($volume_ml * ($degree / 100) * 0.789) / 10;
}

function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function getUserTheme(): string {
    $user = getCurrentUser();
    return $user['theme'] ?? 'light';
}

function getDayLogs(int $user_id, string $date): array {
    $stmt = getDB()->prepare(
        'SELECT * FROM drink_log WHERE user_id = ? AND log_date = ? ORDER BY created_at ASC'
    );
    $stmt->execute([$user_id, $date]);
    return $stmt->fetchAll();
}

function getDayTotal(int $user_id, string $date): ?array {
    $stmt = getDB()->prepare(
        'SELECT SUM(units) as total_units, MAX(is_dry_day) as is_dry_day
         FROM drink_log WHERE user_id = ? AND log_date = ?'
    );
    $stmt->execute([$user_id, $date]);
    return $stmt->fetch();
}

function getMonthData(int $user_id, int $year, int $month): array {
    $stmt = getDB()->prepare(
        'SELECT log_date,
                SUM(units)        AS total_units,
                MAX(is_dry_day)   AS is_dry_day,
                COUNT(id)         AS entries
         FROM drink_log
         WHERE user_id = ? AND YEAR(log_date) = ? AND MONTH(log_date) = ?
         GROUP BY log_date'
    );
    $stmt->execute([$user_id, $year, $month]);
    $rows = $stmt->fetchAll();
    $data = [];
    foreach ($rows as $row) {
        $data[$row['log_date']] = $row;
    }
    return $data;
}

function getUserPresets(int $user_id): array {
    $stmt = getDB()->prepare(
        'SELECT * FROM drink_presets WHERE user_id IS NULL OR user_id = ?
         ORDER BY user_id DESC, name ASC'
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getUserHistory(int $user_id, int $limit = 10): array {
    $stmt = getDB()->prepare(
        'SELECT name, volume_ml, degree FROM drink_log
         WHERE user_id = ? AND is_dry_day = 0
         GROUP BY name, volume_ml, degree
         ORDER BY MAX(created_at) DESC LIMIT ?'
    );
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit,   PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
