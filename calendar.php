<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
requireLogin();

$user    = getCurrentUser();
$userId  = $user['id'];
$today   = date('Y-m-d');

// Navigation
$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

// Clamp
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$pageTitle  = 'Calendrier';
$activePage = 'calendar';

// Load month data
$monthData = getMonthData($userId, $year, $month);

// Also load weekly totals for any ISO week that overlaps this month
// We'll compute per-week totals from month data + cross-boundary days

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$lastDay  = mktime(0, 0, 0, $month + 1, 0, $year);
$daysInMonth = (int)date('t', $firstDay);

// Get monday of first week & sunday of last week
$calStart = strtotime('monday this week', $firstDay);
if (date('N', $firstDay) == 1) $calStart = $firstDay;
$calEnd   = strtotime('sunday this week', $lastDay);
if (date('N', $lastDay) == 7) $calEnd = $lastDay;

// Load ALL days in calendar range for weekly totals
$calStartStr = date('Y-m-d', $calStart);
$calEndStr   = date('Y-m-d', $calEnd);
$stmtRange   = getDB()->prepare(
    'SELECT log_date, SUM(units) AS total_units, MAX(is_dry_day) AS is_dry_day
     FROM drink_log WHERE user_id = ? AND log_date BETWEEN ? AND ?
     GROUP BY log_date'
);
$stmtRange->execute([$userId, $calStartStr, $calEndStr]);
$rangeData = [];
foreach ($stmtRange->fetchAll() as $r) {
    $rangeData[$r['log_date']] = $r;
}

// Helper: classify a day
function classifyDay(?array $d): string {
    if (!$d || $d['total_units'] === null) return 'empty';
    if ((int)$d['is_dry_day'] === 1)       return 'dry';
    if ((float)$d['total_units'] == 0 && (int)$d['is_dry_day'] === 0) return 'empty';
    if ((float)$d['total_units'] <= WHO_DAILY_LIMIT) return 'ok';
    return 'over';
}

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin',
               'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-12 col-md-10 col-lg-8 col-xl-7">

<!-- Month navigation -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <a href="calendar.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
       class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h2 class="h5 fw-bold mb-0 text-center">
        <?= $monthNames[$month - 1] ?> <?= $year ?>
    </h2>
    <a href="calendar.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?>"
       class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-chevron-right"></i>
    </a>
</div>

<!-- Legend -->
<div class="d-flex flex-wrap gap-2 mb-3 small">
    <span class="dl-legend dl-legend-dry"><i class="bi bi-leaf me-1"></i>Dry Day</span>
    <span class="dl-legend dl-legend-ok"><i class="bi bi-check me-1"></i>Dans la norme</span>
    <span class="dl-legend dl-legend-over"><i class="bi bi-exclamation-triangle me-1"></i>Au-dessus</span>
    <span class="dl-legend dl-legend-empty">Vide</span>
</div>

<!-- Calendar grid -->
<div class="card dl-card">
    <div class="card-body p-0 p-md-2">
        <div class="dl-calendar">

            <!-- Day headers -->
            <div class="dl-cal-header">
                <?php foreach (['Lu','Ma','Me','Je','Ve','Sa','Di'] as $d): ?>
                <div class="dl-cal-dh"><?= $d ?></div>
                <?php endforeach; ?>
                <div class="dl-cal-wh">Sem.</div>
            </div>

            <?php
            $cursor = $calStart;
            while ($cursor <= $calEnd):
                // Start of week row
                $weekTotal = 0;
                $weekDays  = [];
                for ($d = 0; $d < 7; $d++) {
                    $dayStr  = date('Y-m-d', $cursor + $d * 86400);
                    $dayData = $rangeData[$dayStr] ?? null;
                    $weekTotal += (float)($dayData['total_units'] ?? 0);
                    $weekDays[] = [
                        'str'     => $dayStr,
                        'day'     => (int)date('j', $cursor + $d * 86400),
                        'inMonth' => (int)date('m', $cursor + $d * 86400) == $month,
                        'data'    => $dayData,
                        'class'   => classifyDay($dayData),
                        'isToday' => $dayStr === $today,
                    ];
                }
                $weekClass = $weekTotal == 0 ? 'secondary' : ($weekTotal <= WHO_WEEKLY_LIMIT ? 'warning' : 'danger');
            ?>
            <div class="dl-cal-row">
                <?php foreach ($weekDays as $wd): ?>
                <?php
                $cellClass = '';
                if (!$wd['inMonth']) $cellClass = 'dl-cal-other';
                elseif ($wd['class'] === 'dry')  $cellClass = 'dl-cal-dry';
                elseif ($wd['class'] === 'ok')   $cellClass = 'dl-cal-ok';
                elseif ($wd['class'] === 'over') $cellClass = 'dl-cal-over';
                ?>
                <a href="dashboard.php?date=<?= $wd['str'] ?>"
                   class="dl-cal-cell <?= $cellClass ?> <?= $wd['isToday'] ? 'dl-cal-today' : '' ?>"
                   title="<?= $wd['str'] ?>">
                    <span class="dl-cal-day-num"><?= $wd['day'] ?></span>
                    <?php if ($wd['inMonth'] && $wd['data'] !== null): ?>
                        <?php if ($wd['class'] === 'dry'): ?>
                        <span class="dl-cal-badge">🌿</span>
                        <?php else: ?>
                        <span class="dl-cal-badge">
                            <?= number_format((float)$wd['data']['total_units'], 1) ?>u
                        </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>

                <!-- Weekly total -->
                <div class="dl-cal-week text-<?= $weekClass ?>">
                    <?php if ($weekTotal > 0): ?>
                    <span class="fw-bold"><?= number_format($weekTotal, 1) ?></span>
                    <span class="dl-cal-week-label">u</span>
                    <?php else: ?>
                    <span class="text-muted">–</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
                $cursor += 7 * 86400;
            endwhile;
            ?>
        </div><!-- dl-calendar -->
    </div>
</div>

<!-- Monthly summary -->
<?php
$monthTotal = array_sum(array_column($monthData, 'total_units'));
$dryDays    = count(array_filter($monthData, fn($d) => (int)$d['is_dry_day'] === 1));
$activeDays = count(array_filter($monthData, fn($d) => (int)$d['is_dry_day'] === 0 && $d['total_units'] > 0));
$overDays   = count(array_filter($monthData, fn($d) => (int)$d['is_dry_day'] === 0 && (float)$d['total_units'] > WHO_DAILY_LIMIT));
?>
<div class="card dl-card mt-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Bilan du mois
    </div>
    <div class="card-body">
        <div class="row g-2 text-center">
            <div class="col-3">
                <div class="fw-bold fs-5"><?= number_format($monthTotal, 1) ?></div>
                <div class="text-muted small">Total<br>unités</div>
            </div>
            <div class="col-3">
                <div class="fw-bold fs-5 text-success"><?= $dryDays ?></div>
                <div class="text-muted small">Dry<br>Days</div>
            </div>
            <div class="col-3">
                <div class="fw-bold fs-5 text-warning"><?= $activeDays ?></div>
                <div class="text-muted small">Jours avec<br>conso.</div>
            </div>
            <div class="col-3">
                <div class="fw-bold fs-5 text-danger"><?= $overDays ?></div>
                <div class="text-muted small">Au-dessus<br>norme</div>
            </div>
        </div>
    </div>
</div>

<!-- Today shortcut -->
<div class="text-center mt-3">
    <a href="calendar.php?month=<?= date('m') ?>&year=<?= date('Y') ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar-check me-1"></i>Mois en cours
    </a>
</div>

</div><!-- col -->
</div><!-- row -->

<?php include __DIR__ . '/includes/footer.php'; ?>
