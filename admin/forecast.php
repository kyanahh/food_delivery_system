<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole("admin");

$forecastStart = $_GET["date"] ?? date("Y-m-d", strtotime("+1 day"));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $forecastStart)) {
    $forecastStart = date("Y-m-d", strtotime("+1 day"));
}

/*
 * 30-Day Demand Forecast
 * - Uses the average number of paid, non-cancelled orders
 *   for each hour during the 30 days immediately before the
 *   forecast period.
 * - Generates 24 hourly predictions for each of the next 30 days.
 * - Total forecast horizon: 30 days / 720 hourly predictions.
 * - This remains a simple historical-pattern method and does not
 *   use an advanced machine-learning model.
 */
$forecastEnd = date("Y-m-d", strtotime($forecastStart . " +29 days"));
$historyStart = date("Y-m-d", strtotime($forecastStart . " -30 days"));
$historyEnd   = date("Y-m-d", strtotime($forecastStart . " -1 day"));

$hourlySql = "
    SELECT
        HOUR(o.created_at) AS order_hour,
        COUNT(*) AS total_orders,
        COUNT(DISTINCT DATE(o.created_at)) AS active_days
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
    GROUP BY HOUR(o.created_at)
    ORDER BY order_hour
";
$stmt = $pdo->prepare($hourlySql);
$stmt->execute([
    $historyStart . " 00:00:00",
    $historyEnd . " 23:59:59"
]);

$hourlyHistory = [];
foreach ($stmt->fetchAll() as $row) {
    $hourlyHistory[(int)$row["order_hour"]] = [
        "total_orders" => (int)$row["total_orders"],
        "active_days" => max((int)$row["active_days"], 1),
        "average" => (float)$row["total_orders"] / max((int)$row["active_days"], 1)
    ];
}

/* Actual order activity inside the 30-day forecast horizon. */
$actualSql = "
    SELECT
        DATE(o.created_at) AS order_date,
        HOUR(o.created_at) AS order_hour,
        COUNT(*) AS order_count
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
    GROUP BY DATE(o.created_at), HOUR(o.created_at)
    ORDER BY order_date, order_hour
";
$stmt = $pdo->prepare($actualSql);
$stmt->execute([
    $forecastStart . " 00:00:00",
    $forecastEnd . " 23:59:59"
]);

$actualByDateHour = [];
foreach ($stmt->fetchAll() as $row) {
    $actualByDateHour[$row["order_date"]][(int)$row["order_hour"]] = (int)$row["order_count"];
}

/* Build 30 daily forecasts, each containing 24 hourly forecasts. */
$dailyForecasts = [];
$hourlyBySelectedDate = [];
$totalForecast = 0;
$peakHour = null;
$peakForecast = 0;
$totalActual = 0;

for ($day = 0; $day < 30; $day++) {
    $date = date("Y-m-d", strtotime($forecastStart . " +$day days"));
    $dayForecast = 0;
    $dayActual = 0;
    $dayPeakHour = null;
    $dayPeakForecast = 0;
    $hours = [];

    for ($hour = 0; $hour < 24; $hour++) {
        $historicalAverage = $hourlyHistory[$hour]["average"] ?? 0;
        $actual = $actualByDateHour[$date][$hour] ?? 0;

        /*
         * For future dates, the historical average is the forecast.
         * If a date has already recorded activity, the larger value
         * is displayed so actual activity is not understated.
         */
        $forecast = $historicalAverage;
        if ($actual > 0) {
            $forecast = max($forecast, (float)$actual);
        }

        $forecastRounded = (int)round($forecast);

        $dayForecast += $forecastRounded;
        $dayActual += $actual;
        $totalForecast += $forecastRounded;
        $totalActual += $actual;

        if ($forecastRounded > $dayPeakForecast) {
            $dayPeakForecast = $forecastRounded;
            $dayPeakHour = $hour;
        }

        if ($forecastRounded > $peakForecast) {
            $peakForecast = $forecastRounded;
            $peakHour = $hour;
        }

        $hours[] = [
            "hour" => $hour,
            "label" => date("g A", strtotime(sprintf("%02d:00", $hour))),
            "historical" => $historicalAverage,
            "actual" => $actual,
            "forecast" => $forecastRounded
        ];
    }

    $dailyForecasts[$date] = [
        "date" => $date,
        "label" => date("M d, Y", strtotime($date)),
        "day_name" => date("l", strtotime($date)),
        "forecast" => $dayForecast,
        "actual" => $dayActual,
        "peak_hour" => $dayPeakHour,
        "peak_forecast" => $dayPeakForecast,
        "hours" => $hours
    ];
}

/* Selected date for the hourly detail table. */
$selectedDate = $_GET["view"] ?? $forecastStart;
if (!isset($dailyForecasts[$selectedDate])) {
    $selectedDate = $forecastStart;
}
$selectedDay = $dailyForecasts[$selectedDate];
$selectedPeakHour = $selectedDay["peak_hour"];

$peakStart = $selectedPeakHour !== null
    ? date("g A", strtotime(sprintf("%02d:00", $selectedPeakHour)))
    : "No data";

$peakEnd = $selectedPeakHour !== null
    ? date("g A", strtotime(sprintf("%02d:00", ($selectedPeakHour + 1) % 24)))
    : "";

$highDemandHours = array_filter(
    $selectedDay["hours"],
    fn($row) => $selectedDay["peak_forecast"] > 0 &&
        $row["forecast"] >= max(1, $selectedDay["peak_forecast"] * 0.75)
);

$totalHistoricalPerDay = array_sum(array_map(
    fn($row) => $row["forecast"],
    $selectedDay["hours"]
));

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>30-Day Demand Forecasting | Jollibee Central Plaza Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --jollibee-red: #d71920;
            --jollibee-dark-red: #b51218;
        }

        body {
            background: #f8f9fa;
        }

        .bg-jollibee {
            background-color: var(--jollibee-red) !important;
        }

        .btn-jollibee {
            background-color: var(--jollibee-red);
            border-color: var(--jollibee-red);
            color: #fff;
        }

        .btn-jollibee:hover {
            background-color: var(--jollibee-dark-red);
            border-color: var(--jollibee-dark-red);
            color: #fff;
        }

        .text-jollibee {
            color: var(--jollibee-red) !important;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
        }

        .sidebar .nav-link {
            color: #343a40;
            border-radius: .5rem;
            margin-bottom: .25rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--jollibee-red);
            color: #fff;
        }

        .summary-card {
            border: 0;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(215, 25, 32, .1);
            color: var(--jollibee-red);
            font-size: 1.35rem;
        }

        .forecast-row {
            border-left: 4px solid transparent;
        }

        .forecast-row.high-demand {
            border-left-color: var(--jollibee-red);
        }

        .progress {
            height: 8px;
        }

        .table th {
            white-space: nowrap;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-jollibee shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            Jollibee Central Plaza | Admin
        </a>

        <div class="d-flex align-items-center gap-3 text-white">
            <span>
                <i class="bi bi-person-circle me-1"></i>
                <?= e(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </span>
            <a href="../logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-3 col-lg-2 bg-white border-end sidebar p-3">
            <div class="text-center py-3 mb-3 border-bottom">
                <div class="text-jollibee fs-1">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="fw-bold">
                    <?= e(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? "")) ?>
                </div>
                <small class="text-muted">Administrator</small>
            </div>

            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i> User Management
                </a>
                <a class="nav-link" href="menu.php">
                    <i class="bi bi-grid me-2"></i> Menu Management
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-receipt me-2"></i> Orders
                </a>
                <a class="nav-link" href="sales.php">
                    <i class="bi bi-bar-chart-line me-2"></i> Sales Reports
                </a>
                <a class="nav-link active" href="forecast.php">
                    <i class="bi bi-graph-up-arrow me-2"></i> Demand Forecasting
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear me-2"></i> System Settings
                </a>
            </nav>
        </aside>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Demand Forecasting</h3>
                    <p class="text-muted mb-0">
                        Estimate order demand using recent historical order patterns.
                    </p>
                </div>

                <form method="GET" class="d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label small fw-semibold mb-1">Forecast Start Date</label>
                        <input type="date"
                               name="date"
                               class="form-control"
                               value="<?= e($forecastStart) ?>">
                    </div>

                    <button type="submit" class="btn btn-jollibee">
                        <i class="bi bi-arrow-repeat me-1"></i> Forecast
                    </button>
                </form>
            </div>

            <div class="alert alert-light border mb-4">
                <i class="bi bi-info-circle text-jollibee me-2"></i>
                Forecast covers <strong>30 days</strong> from
                <strong><?= e(date("M d, Y", strtotime($forecastStart))) ?></strong>
                to <strong><?= e(date("M d, Y", strtotime($forecastEnd))) ?></strong>.
                It uses the previous <strong>30 days</strong> of paid, non-cancelled
                orders, grouped by hour.
            </div>

            <div class="row g-3 mb-4">

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">30-Day Forecast</small>
                                <h4 class="fw-bold mb-0"><?= number_format($totalForecast) ?></h4>
                                <small class="text-muted">predicted orders</small>
                            </div>
                            <div class="summary-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Forecast Period</small>
                                <h4 class="fw-bold mb-0">30 Days</h4>
                                <small class="text-muted"><?= e(date("M d", strtotime($forecastStart))) ?> – <?= e(date("M d, Y", strtotime($forecastEnd))) ?></small>
                            </div>
                            <div class="summary-icon"><i class="bi bi-calendar3"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Peak Hour</small>
                                <h4 class="fw-bold mb-0"><?= e($peakStart) ?></h4>
                                <small class="text-muted"><?= number_format($peakForecast) ?> orders/hour</small>
                            </div>
                            <div class="summary-icon"><i class="bi bi-clock"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Forecast Points</small>
                                <h4 class="fw-bold mb-0">720</h4>
                                <small class="text-muted">30 days × 24 hours</small>
                            </div>
                            <div class="summary-icon"><i class="bi bi-bar-chart-line"></i></div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="alert alert-light border mb-4">
                <i class="bi bi-info-circle text-jollibee me-2"></i>
                <strong>Forecast horizon:</strong> 30 days with 24 hourly predictions per day.
                The historical baseline uses the 30 days immediately before the forecast period.
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1">30-Day Forecast Summary</h5>
                    <small class="text-muted">
                        Daily predicted demand for the selected 30-day forecast period
                    </small>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Forecasted Orders</th>
                                    <th>Peak Hour</th>
                                    <th>Peak Forecast</th>
                                    <th>Demand Level</th>
                                    <th class="text-end">Hourly Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dailyForecasts as $day): ?>
                                    <?php
                                        $ratio = $peakForecast > 0 ? $day["peak_forecast"] / $peakForecast : 0;
                                        $level = "Low";
                                        $badge = "bg-secondary";
                                        if ($ratio >= 0.75) {
                                            $level = "High";
                                            $badge = "bg-danger";
                                        } elseif ($ratio >= 0.40) {
                                            $level = "Moderate";
                                            $badge = "bg-warning text-dark";
                                        }
                                        $dayPeakLabel = $day["peak_hour"] !== null
                                            ? date("g A", strtotime(sprintf("%02d:00", $day["peak_hour"])))
                                            : "No data";
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($day["label"]) ?></td>
                                        <td><?= e($day["day_name"]) ?></td>
                                        <td class="fw-bold text-jollibee"><?= number_format($day["forecast"]) ?></td>
                                        <td><?= e($dayPeakLabel) ?></td>
                                        <td><?= number_format($day["peak_forecast"]) ?> / hr</td>
                                        <td><span class="badge <?= $badge ?>"><?= $level ?></span></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-danger"
                                               href="?date=<?= e($forecastStart) ?>&view=<?= e($day["date"]) ?>">
                                                <i class="bi bi-clock-history me-1"></i> View Hours
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="fw-bold mb-1">Hourly Demand Forecast</h5>
                            <small class="text-muted">
                                <?= e($selectedDay["label"]) ?> — 24 hourly forecast points
                            </small>
                        </div>
                        <form method="GET" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="date" value="<?= e($forecastStart) ?>">
                            <select name="view" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($dailyForecasts as $day): ?>
                                    <option value="<?= e($day["date"]) ?>" <?= $day["date"] === $selectedDate ? "selected" : "" ?>>
                                        <?= e($day["label"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Historical Average</th>
                                    <th>Actual Orders</th>
                                    <th>Forecast</th>
                                    <th>Demand Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selectedDay["hours"] as $row): ?>
                                    <?php
                                        $isHigh = $selectedDay["peak_forecast"] > 0 &&
                                            $row["forecast"] >= ($selectedDay["peak_forecast"] * 0.75);

                                        $level = "Low";
                                        $badge = "bg-secondary";
                                        if ($selectedDay["peak_forecast"] > 0) {
                                            $ratio = $row["forecast"] / $selectedDay["peak_forecast"];
                                            if ($ratio >= 0.75) {
                                                $level = "High";
                                                $badge = "bg-danger";
                                            } elseif ($ratio >= 0.40) {
                                                $level = "Moderate";
                                                $badge = "bg-warning text-dark";
                                            }
                                        }
                                    ?>
                                    <tr class="forecast-row <?= $isHigh ? "high-demand" : "" ?>">
                                        <td class="fw-semibold"><?= e($row["label"]) ?></td>
                                        <td><?= number_format($row["historical"], 1) ?></td>
                                        <td><?= number_format($row["actual"]) ?></td>
                                        <td class="fw-bold text-jollibee"><?= number_format($row["forecast"]) ?></td>
                                        <td><span class="badge <?= $badge ?>"><?= $level ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-1">Peak-Hour Preparation</h5>
                            <small class="text-muted">
                                High-demand hours for <?= e($selectedDay["label"]) ?>
                            </small>
                        </div>

                        <div class="card-body">
                            <?php if (!$highDemandHours): ?>
                                <div class="text-center text-muted py-4">
                                    Not enough historical data to identify peak hours.
                                </div>
                            <?php else: ?>
                                <?php foreach ($highDemandHours as $row): ?>
                                    <?php
                                        $percentage = $selectedDay["peak_forecast"] > 0
                                            ? min(100, ($row["forecast"] / $selectedDay["peak_forecast"]) * 100)
                                            : 0;
                                    ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold"><?= e($row["label"]) ?></span>
                                            <span class="fw-semibold"><?= number_format($row["forecast"]) ?> orders</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-jollibee"
                                                 role="progressbar"
                                                 style="width: <?= $percentage ?>%">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-1">Forecast Information</h5>
                            <small class="text-muted">How the 30-day estimate is calculated</small>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-calendar3 text-jollibee me-2"></i>
                                    Forecast Period
                                </div>
                                <small class="text-muted">
                                    <?= e(date("M d, Y", strtotime($forecastStart))) ?>
                                    to <?= e(date("M d, Y", strtotime($forecastEnd))) ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-clock-history text-jollibee me-2"></i>
                                    Historical Baseline
                                </div>
                                <small class="text-muted">
                                    <?= e(date("M d, Y", strtotime($historyStart))) ?>
                                    to <?= e(date("M d, Y", strtotime($historyEnd))) ?>.
                                    Orders are grouped by hour.
                                </small>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-bar-chart-line text-jollibee me-2"></i>
                                    Forecast Resolution
                                </div>
                                <small class="text-muted">
                                    24 hourly predictions per day, producing 720 forecast points for 30 days.
                                </small>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    <i class="bi bi-exclamation-triangle text-jollibee me-2"></i>
                                    Limitation
                                </div>
                                <small class="text-muted">
                                    This is a simple historical-pattern forecasting method based on available
                                    order history and is not an advanced machine-learning prediction model.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
