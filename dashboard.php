<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
requireLogin();

$user    = getCurrentUser();
$userId  = $user['id'];
$today   = date('Y-m-d');
$pageTitle  = 'Encoder';
$activePage = 'dashboard';

$error   = '';
$success = '';

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_drink') {
        $name   = trim($_POST['name']   ?? '');
        $volume = (float)($_POST['volume'] ?? 0);
        $degree = (float)($_POST['degree'] ?? 0);
        $date   = $_POST['log_date']  ?? $today;

        if (!$name || $volume <= 0 || $degree < 0) {
            $error = 'Veuillez remplir correctement tous les champs.';
        } elseif ($degree > 100) {
            $error = 'Le degré d\'alcool ne peut pas dépasser 100%.';
        } else {
            $units = calculateUnits($volume, $degree);
            $stmt  = getDB()->prepare(
                'INSERT INTO drink_log (user_id, log_date, name, volume_ml, degree, units, is_dry_day)
                 VALUES (?, ?, ?, ?, ?, ?, 0)'
            );
            $stmt->execute([$userId, $date, $name, $volume, $degree, $units]);

            // Save as user preset if requested
            if (!empty($_POST['save_preset'])) {
                $existing = getDB()->prepare(
                    'SELECT id FROM drink_presets WHERE user_id = ? AND name = ?'
                );
                $existing->execute([$userId, $name]);
                if (!$existing->fetch()) {
                    getDB()->prepare(
                        'INSERT INTO drink_presets (user_id, name, volume_ml, degree) VALUES (?, ?, ?, ?)'
                    )->execute([$userId, $name, $volume, $degree]);
                }
            }
            $success = sprintf(
                'Boisson ajoutée : <strong>%s</strong> – <strong>%.2f unités</strong>',
                htmlspecialchars($name), $units
            );
        }

    } elseif ($action === 'dry_day') {
        $date = $_POST['log_date'] ?? $today;

        // Remove existing entries for that day first
        getDB()->prepare('DELETE FROM drink_log WHERE user_id = ? AND log_date = ?')
               ->execute([$userId, $date]);

        // Insert a dry-day marker
        getDB()->prepare(
            'INSERT INTO drink_log (user_id, log_date, name, volume_ml, degree, units, is_dry_day)
             VALUES (?, ?, "Dry Day", 0, 0, 0, 1)'
        )->execute([$userId, $date]);

        $success = '🌿 <strong>Dry Day</strong> enregistré pour le ' .
                   date('d/m/Y', strtotime($date)) . '.';

    } elseif ($action === 'delete_entry') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        getDB()->prepare('DELETE FROM drink_log WHERE id = ? AND user_id = ?')
               ->execute([$entryId, $userId]);
        $success = 'Entrée supprimée.';
    }
}

// ---- Load data ----
$presets = getUserPresets($userId);
$history = getUserHistory($userId, 8);

$selectedDate = $_GET['date'] ?? $today;
$dayLogs      = getDayLogs($userId, $selectedDate);
$dayTotal     = getDayTotal($userId, $selectedDate);
$todayUnits   = (float)($dayTotal['total_units'] ?? 0);
$isDryDay     = (bool)($dayTotal['is_dry_day']   ?? false);

// Weekly total (Mon–Sun of selected date)
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
$weekEnd   = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
$stmtWeek  = getDB()->prepare(
    'SELECT SUM(units) as total FROM drink_log
     WHERE user_id = ? AND log_date BETWEEN ? AND ?'
);
$stmtWeek->execute([$userId, $weekStart, $weekEnd]);
$weekUnits = (float)($stmtWeek->fetch()['total'] ?? 0);

// Build preset JSON for JS
$presetsJson = json_encode(array_map(fn($p) => [
    'id'     => $p['id'],
    'name'   => $p['name'],
    'volume' => $p['volume_ml'],
    'degree' => $p['degree'],
    'user'   => !is_null($p['user_id']),
], $presets));

$historyJson = json_encode(array_map(fn($h) => [
    'name'   => $h['name'],
    'volume' => $h['volume_ml'],
    'degree' => $h['degree'],
], $history));

// ---- Render ----
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center g-3">
    <div class="col-12 col-md-10 col-lg-8 col-xl-6">

    <!-- Date picker bar -->
    <div class="card dl-card mb-3">
        <div class="card-body py-2 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-event text-primary fs-5"></i>
            <label class="fw-semibold mb-0 me-2 text-nowrap">Date :</label>
            <input type="date" id="datePicker" class="form-control form-control-sm"
                   value="<?= $selectedDate ?>" max="<?= $today ?>">
            <button class="btn btn-sm btn-outline-secondary text-nowrap"
                    onclick="document.getElementById('datePicker').value='<?= $today ?>';
                             window.location='dashboard.php'">
                Aujourd'hui
            </button>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-check-circle me-1"></i><?= $success ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <?php
            $pct     = min(100, ($todayUnits / WHO_DAILY_LIMIT) * 100);
            $dayClass = $isDryDay ? 'success' : ($todayUnits == 0 ? 'secondary' : ($todayUnits <= WHO_DAILY_LIMIT ? 'warning' : 'danger'));
            ?>
            <div class="card dl-card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-sun me-1"></i>Journée</div>
                    <div class="dl-stat-number text-<?= $dayClass ?>">
                        <?= $isDryDay ? '🌿' : number_format($todayUnits, 2) ?>
                    </div>
                    <?php if (!$isDryDay): ?>
                    <div class="text-muted small">/ <?= WHO_DAILY_LIMIT ?> unités</div>
                    <div class="progress mt-2" style="height:6px">
                        <div class="progress-bar bg-<?= $dayClass ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <?php else: ?>
                    <div class="text-success small fw-semibold">Dry Day ✓</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6">
            <?php
            $wpct     = min(100, ($weekUnits / WHO_WEEKLY_LIMIT) * 100);
            $weekClass = $weekUnits == 0 ? 'secondary' : ($weekUnits <= WHO_WEEKLY_LIMIT ? 'warning' : 'danger');
            ?>
            <div class="card dl-card text-center h-100">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-calendar-week me-1"></i>Semaine</div>
                    <div class="dl-stat-number text-<?= $weekClass ?>">
                        <?= number_format($weekUnits, 2) ?>
                    </div>
                    <div class="text-muted small">/ <?= WHO_WEEKLY_LIMIT ?> unités</div>
                    <div class="progress mt-2" style="height:6px">
                        <div class="progress-bar bg-<?= $weekClass ?>" style="width:<?= $wpct ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dry Day button -->
    <form method="POST" class="mb-3"
          onsubmit="return confirm('Confirmer Dry Day pour le <?= date('d/m/Y', strtotime($selectedDate)) ?> ? Cela supprimera les entrées existantes.')">
        <input type="hidden" name="action" value="dry_day">
        <input type="hidden" name="log_date" value="<?= $selectedDate ?>" id="dryDayDate">
        <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold dl-dry-btn
                <?= $isDryDay ? 'active' : '' ?>">
            <i class="bi bi-leaf me-2"></i>🌿 Dry Day
            <small class="d-block fw-normal opacity-75 fs-7">Pas de consommation ce jour</small>
        </button>
    </form>

    <!-- Drink encoder card -->
    <div class="card dl-card mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Ajouter une boisson
        </div>
        <div class="card-body">
            <form method="POST" id="drinkForm">
                <input type="hidden" name="action" value="add_drink">
                <input type="hidden" name="log_date" value="<?= $selectedDate ?>" id="drinkDate">

                <!-- Quick select -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sélection rapide</label>
                    <select class="form-select form-select-lg" id="quickSelect">
                        <option value="">– Choisir une boisson –</option>
                        <?php if ($history): ?>
                        <optgroup label="🕐 Historique personnel">
                            <?php foreach ($history as $h): ?>
                            <option data-name="<?= htmlspecialchars($h['name']) ?>"
                                    data-volume="<?= $h['volume_ml'] ?>"
                                    data-degree="<?= $h['degree'] ?>">
                                <?= htmlspecialchars($h['name']) ?>
                                (<?= $h['volume_ml'] ?>ml, <?= $h['degree'] ?>%)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <optgroup label="📋 Presets globaux">
                            <?php foreach ($presets as $p): if (!is_null($p['user_id'])) continue; ?>
                            <option data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-volume="<?= $p['volume_ml'] ?>"
                                    data-degree="<?= $p['degree'] ?>">
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php
                        $userPresets = array_filter($presets, fn($p) => !is_null($p['user_id']));
                        if ($userPresets): ?>
                        <optgroup label="⭐ Mes presets">
                            <?php foreach ($userPresets as $p): ?>
                            <option data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-volume="<?= $p['volume_ml'] ?>"
                                    data-degree="<?= $p['degree'] ?>">
                                <?= htmlspecialchars($p['name']) ?>
                                (<?= $p['volume_ml'] ?>ml, <?= $p['degree'] ?>%)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Fields -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom de la boisson</label>
                    <input type="text" name="name" id="drinkName" class="form-control form-control-lg"
                           placeholder="Ex. Leffe Blonde" required maxlength="100">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Volume (ml)</label>
                        <div class="input-group">
                            <input type="number" name="volume" id="drinkVolume"
                                   class="form-control form-control-lg"
                                   placeholder="330" step="1" min="1" required>
                            <span class="input-group-text">ml</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Degré (%)</label>
                        <div class="input-group">
                            <input type="number" name="degree" id="drinkDegree"
                                   class="form-control form-control-lg"
                                   placeholder="5.0" step="0.1" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <!-- Live unit preview -->
                <div class="alert alert-info py-2 mb-3 d-none" id="unitPreview">
                    <i class="bi bi-calculator me-1"></i>
                    Estimation : <strong id="unitValue">0</strong> unité(s)
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="save_preset" id="savePreset">
                    <label class="form-check-label" for="savePreset">
                        Sauvegarder comme preset personnel
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter
                </button>
            </form>
        </div>
    </div>

    <!-- Today's entries -->
    <?php if ($dayLogs): ?>
    <div class="card dl-card">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-2 text-primary"></i>
                Entrées du <?= date('d/m/Y', strtotime($selectedDate)) ?>
            </span>
            <span class="badge bg-primary rounded-pill"><?= count($dayLogs) ?></span>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($dayLogs as $log): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <?php if ($log['is_dry_day']): ?>
                <div>
                    <span class="badge bg-success"><i class="bi bi-leaf me-1"></i>Dry Day</span>
                </div>
                <?php else: ?>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($log['name']) ?></div>
                    <small class="text-muted"><?= $log['volume_ml'] ?>ml · <?= $log['degree'] ?>%</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill">
                        <?= number_format($log['units'], 2) ?> u.
                    </span>
                    <form method="POST" class="m-0"
                          onsubmit="return confirm('Supprimer cette entrée ?')">
                        <input type="hidden" name="action" value="delete_entry">
                        <input type="hidden" name="entry_id" value="<?= $log['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" type="submit">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- WHO info -->
    <div class="card dl-card mt-3 border-0 bg-opacity-10 bg-info">
        <div class="card-body py-2">
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Normes OMS :</strong> max <?= WHO_DAILY_LIMIT ?> unités/jour,
                <?= WHO_WEEKLY_LIMIT ?> unités/semaine.
                1 unité = 10 g d'alcool pur.
                Formule : Volume(ml) × Degré(%) × 0,789 / 1000.
            </p>
        </div>
    </div>

    </div><!-- col -->
</div><!-- row -->

<?php
$extraJs = <<<JS
<script>
const quickSelect = document.getElementById('quickSelect');
const drinkName   = document.getElementById('drinkName');
const drinkVolume = document.getElementById('drinkVolume');
const drinkDegree = document.getElementById('drinkDegree');
const unitPreview = document.getElementById('unitPreview');
const unitValue   = document.getElementById('unitValue');

quickSelect.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.dataset.name) return;
    drinkName.value   = opt.dataset.name;
    drinkVolume.value = opt.dataset.volume;
    drinkDegree.value = opt.dataset.degree;
    updateUnits();
});

function updateUnits() {
    const v = parseFloat(drinkVolume.value) || 0;
    const d = parseFloat(drinkDegree.value) || 0;
    const u = (v * (d / 100) * 0.789) / 10;
    if (v > 0 && d >= 0) {
        unitValue.textContent = u.toFixed(2);
        unitPreview.classList.remove('d-none');
    } else {
        unitPreview.classList.add('d-none');
    }
}
drinkVolume.addEventListener('input', updateUnits);
drinkDegree.addEventListener('input', updateUnits);

// Date picker navigation
document.getElementById('datePicker').addEventListener('change', function() {
    window.location = 'dashboard.php?date=' + this.value;
});
</script>
JS;

include __DIR__ . '/includes/footer.php';
?>
