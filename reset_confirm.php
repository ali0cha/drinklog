<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = '';
$validToken = false;
$user = null;

if ($token) {
    $stmt = getDB()->prepare(
        'SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) $validToken = true;
    else $error = 'Ce lien est invalide ou expiré.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        getDB()->prepare(
            'UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?'
        )->execute([$hash, $user['id']]);
        $success = 'Mot de passe mis à jour avec succès ! Vous pouvez maintenant vous connecter.';
        $validToken = false;
    }
}

$theme = $_COOKIE['dl_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dl-auth-body">
<div class="dl-auth-container">
    <div class="text-center mb-4">
        <div class="dl-app-icon mb-3"><i class="bi bi-key"></i></div>
        <h1 class="h4 fw-bold">Nouveau mot de passe</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2">
        <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success py-2">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
    </div>
    <div class="text-center mt-3">
        <a href="index.php" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
        </a>
    </div>
    <?php elseif ($validToken): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Nouveau mot de passe</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control form-control-lg"
                       placeholder="Min. 8 caractères" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Confirmer</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password2" class="form-control form-control-lg"
                       placeholder="Répétez le mot de passe" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-check-circle me-1"></i>Valider
        </button>
    </form>
    <?php elseif (!$success): ?>
    <div class="text-center">
        <p class="text-muted">Lien invalide ou expiré.</p>
        <a href="reset.php" class="btn btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i>Demander un nouveau lien
        </a>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="index.php" class="text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Retour à la connexion
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
