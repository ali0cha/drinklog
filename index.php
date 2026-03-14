<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'login'; // 'login' | 'register'

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];

                // Remember me : cookie 30 jours
                if (!empty($_POST['remember'])) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    getDB()->prepare(
                        'UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?'
                    )->execute([$token, $expires, $user['id']]);
                    setcookie('dl_remember', $token, time() + 30 * 86400, '/', '', true, true);
                }

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }
        $tab = 'login';

    } elseif ($action === 'register') {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['reg_email'] ?? '');
        $password  = $_POST['reg_password'] ?? '';
        $password2 = $_POST['reg_password2'] ?? '';

        if (!$username || !$email || !$password) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $password2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            // Check uniqueness
            $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = 'Cet email ou nom d\'utilisateur est déjà utilisé.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = getDB()->prepare(
                    'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
                );
                $stmt->execute([$username, $email, $hash]);
                $userId = getDB()->lastInsertId();
                $_SESSION['user_id'] = $userId;
                header('Location: dashboard.php');
                exit;
            }
        }
        $tab = 'register';
    }
}

// Determine theme for unauthenticated page
$theme = $_COOKIE['dl_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6d28d9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    <title><?= APP_NAME ?> – Connexion</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="assets/icon-192.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dl-auth-body">

<!-- Theme toggle (top right) -->
<button class="btn btn-sm btn-outline-secondary position-fixed top-0 end-0 m-3 rounded-circle p-1 lh-1"
        id="themeToggle" style="width:36px;height:36px;z-index:9999">
    <i class="bi <?= $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>" id="themeIcon"></i>
</button>

<div class="dl-auth-container">
    <div class="text-center mb-4">
        <div class="dl-app-icon mb-3">
            <i class="bi bi-cup-straw"></i>
        </div>
        <h1 class="h3 fw-bold"><?= APP_NAME ?></h1>
        <p class="text-muted small">Suivi de consommation d'alcool</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success py-2">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-pills nav-fill mb-3" id="authTab">
        <li class="nav-item">
            <button class="nav-link <?= $tab === 'login' ? 'active' : '' ?>"
                    id="login-tab" data-bs-toggle="pill" data-bs-target="#login">
                <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $tab === 'register' ? 'active' : '' ?>"
                    id="register-tab" data-bs-toggle="pill" data-bs-target="#register">
                <i class="bi bi-person-plus me-1"></i>Inscription
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- LOGIN -->
        <div class="tab-pane fade <?= $tab === 'login' ? 'show active' : '' ?>" id="login">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control form-control-lg"
                               placeholder="votre@email.com" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control form-control-lg"
                               placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                        <label class="form-check-label small" for="rememberMe">
                            Se souvenir de moi <span class="text-muted">(30 jours)</span>
                        </label>
                    </div>
                    <a href="reset.php" class="text-muted small">Mot de passe oublié ?</a>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                </button>
            </form>
        </div>

        <!-- REGISTER -->
        <div class="tab-pane fade <?= $tab === 'register' ? 'show active' : '' ?>" id="register">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control form-control-lg"
                               placeholder="MonPseudo" required maxlength="50"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="reg_email" class="form-control form-control-lg"
                               placeholder="votre@email.com" required
                               value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="reg_password" id="reg_password"
                               class="form-control form-control-lg" placeholder="Min. 8 caractères" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirmer le mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="reg_password2" id="reg_password2"
                               class="form-control form-control-lg" placeholder="Répétez le mot de passe" required>
                    </div>
                    <div class="invalid-feedback" id="pwdMatchFeedback">
                        Les mots de passe ne correspondent pas.
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                    <i class="bi bi-person-check me-1"></i>Créer mon compte
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        <i class="bi bi-shield-check me-1"></i>
        Vos données restent sur votre serveur.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password show/hide
document.getElementById('togglePwd')?.addEventListener('click', function() {
    const pwd  = document.querySelector('[name=password]');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'bi bi-eye'; }
});

// Password match check
['reg_password','reg_password2'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function() {
        const p1 = document.getElementById('reg_password').value;
        const p2 = document.getElementById('reg_password2').value;
        const fb = document.getElementById('pwdMatchFeedback');
        if (p2 && p1 !== p2) {
            document.getElementById('reg_password2').classList.add('is-invalid');
        } else {
            document.getElementById('reg_password2').classList.remove('is-invalid');
        }
    });
});

// Theme toggle
document.getElementById('themeToggle').addEventListener('click', function() {
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    const next = isDark ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    icon.className = isDark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    document.cookie = 'dl_theme=' + next + ';path=/;max-age=31536000';
});
</script>
</body>
</html>
