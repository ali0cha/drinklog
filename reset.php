<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/mailer.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } else {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            getDB()->prepare(
                'UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?'
            )->execute([$token, $expires, $user['id']]);

            $link = APP_URL . '/reset_confirm.php?token=' . $token;
            $sent = sendPasswordResetEmail($email, $user['username'], $link);

            if ($sent) {
                $success = 'Un email de réinitialisation vous a été envoyé. Vérifiez votre boîte mail.';
            } else {
                $error = 'Erreur lors de l\'envoi de l\'email. Vérifiez la configuration SMTP.';
            }
        } else {
            // Don't reveal if email exists
            $success = 'Si cet email est associé à un compte, un lien de réinitialisation a été envoyé.';
        }
    }
}

$theme = $_COOKIE['dl_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dl-auth-body">
<div class="dl-auth-container">
    <div class="text-center mb-4">
        <div class="dl-app-icon mb-3"><i class="bi bi-shield-lock"></i></div>
        <h1 class="h4 fw-bold">Mot de passe oublié</h1>
        <p class="text-muted small">
            Entrez votre email pour recevoir un lien de réinitialisation.
        </p>
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
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Adresse email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control form-control-lg"
                       placeholder="votre@email.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-send me-1"></i>Envoyer le lien
        </button>
    </form>
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
