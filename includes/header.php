<?php
// includes/header.php
// Usage: include with $pageTitle and $activePage set
$theme = getUserTheme();
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6d28d9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> – <?= APP_NAME ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icon-192.png">
    <link rel="icon" href="assets/icon-192.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm dl-navbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-cup-straw me-1"></i><?= APP_NAME ?>
        </a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <!-- Theme toggle -->
            <button class="btn btn-sm btn-outline-secondary rounded-circle p-1 lh-1" id="themeToggle"
                    title="Changer le thème" style="width:32px;height:32px">
                <i class="bi <?= $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>" id="themeIcon"></i>
            </button>
            <!-- Nav links desktop -->
            <div class="d-none d-md-flex gap-1">
                <a href="dashboard.php" class="btn btn-sm <?= ($activePage??'') === 'dashboard' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-plus-circle me-1"></i>Encoder
                </a>
                <a href="calendar.php" class="btn btn-sm <?= ($activePage??'') === 'calendar' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-calendar3 me-1"></i>Calendrier
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                </a>
            </div>
            <!-- Hamburger mobile -->
            <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mobileMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
        </div>
        <!-- Mobile menu -->
        <div class="collapse navbar-collapse" id="mobileMenu">
            <div class="d-flex flex-column gap-2 py-2 d-md-none">
                <a href="dashboard.php" class="btn <?= ($activePage??'') === 'dashboard' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-plus-circle me-1"></i>Encoder une boisson
                </a>
                <a href="calendar.php" class="btn <?= ($activePage??'') === 'calendar' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-calendar3 me-1"></i>Calendrier
                </a>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>
<!-- Bottom nav (mobile) -->
<nav class="d-md-none fixed-bottom dl-bottom-nav">
    <a href="dashboard.php" class="dl-bottom-nav-item <?= ($activePage??'') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Encoder</span>
    </a>
    <a href="calendar.php" class="dl-bottom-nav-item <?= ($activePage??'') === 'calendar' ? 'active' : '' ?>">
        <i class="bi bi-calendar3"></i>
        <span>Calendrier</span>
    </a>
    <a href="logout.php" class="dl-bottom-nav-item">
        <i class="bi bi-box-arrow-right"></i>
        <span>Quitter</span>
    </a>
</nav>
<main class="container-fluid px-3 py-3 mb-5 pb-5">
