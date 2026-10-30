<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole("admin");

$forecastDate = $_GET["date"] ?? date("Y-m-d");

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $forecastDate)) {
    $forecastDate = date("Y-m-d");
}

$historyStart = date("Y-m-d", strtotime($forecastDate . " -30 days"));
$historyEnd   = date("Y-m-d", strtotime($forecastDate . " -1 day"));

/*
 * Simple demand forecasting:
 * - Uses the average number of paid, non-cancelled orders
 *   for each hour during the previous 30 days.
 * - Also shows today's/current active order count.
 * This matches the project's scope of using historical ordering
 * patterns and peak-hour trends rather than an advanced ML model.
 */

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
    $days = max((int)$row["active_days"], 1);
    $hourlyHistory[(int)$row["order_hour"]] = [
        "total_orders" => (int)$row["total_orders"],
        "active_days" => $days,
        "average" => (float)$row["total_orders"] / $days
    ];
}

$todaySql = "
    SELECT
        HOUR(o.created_at) AS order_hour,
        COUNT(*) AS order_count
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND DATE(o.created_at) = ?
    GROUP BY HOUR(o.created_at)
    ORDER BY order_hour
";

$stmt = $pdo->prepare($todaySql);
$stmt->execute([$forecastDate]);

$actualByHour = [];
foreach ($stmt->fetchAll() as $row) {
    $actualByHour[(int)$row["order_hour"]] = (int)$row["order_count"];
}

/* Current active orders for the selected date. */
$activeSql = "
    SELECT COUNT(*) AS active_orders
    FROM orders
    WHERE DATE(created_at) = ?
      AND order_status IN ('Pending','Confirmed','Preparing','Ready for Delivery','Ready for Pickup','Out for Delivery')
";
$stmt = $pdo->prepare($activeSql);
$stmt->execute([$forecastDate]);
$activeOrders = (int)$stmt->fetch()["active_orders"];

/* Total historical demand and overall forecast. */
$totalHistorical = 0;
$totalForecast = 0;
$peakHour = null;
$peakForecast = 0;

$forecastRows = [];

for ($hour = 0; $hour < 24; $hour++) {
    $historicalAverage = $hourlyHistory[$hour]["average"] ?? 0;
    $actual = $actualByHour[$hour] ?? 0;

    /*
     * If the selected date is today, slightly account for actual
     * order activity already recorded for the hour.
     */
    $forecast = $historicalAverage;

    if ($forecastDate === date("Y-m-d") && $actual > 0) {
        $forecast = max($forecast, (float)$actual);
    }

    $forecastRounded = (int)round($forecast);

    $totalHistorical += $historicalAverage;
    $totalForecast += $forecastRounded;

    if ($forecastRounded > $peakForecast) {
        $peakForecast = $forecastRounded;
        $peakHour = $hour;
    }

    $forecastRows[] = [
        "hour" => $hour,
        "label" => date("g A", strtotime(sprintf("%02d:00", $hour))),
        "historical" => $historicalAverage,
        "actual" => $actual,
        "forecast" => $forecastRounded
    ];
}

$peakStart = $peakHour !== null
    ? date("g A", strtotime(sprintf("%02d:00", $peakHour)))
    : "No data";

$peakEnd = $peakHour !== null
    ? date("g A", strtotime(sprintf("%02d:00", ($peakHour + 1) % 24)))
    : "";

$highDemandHours = array_filter(
    $forecastRows,
    fn($row) => $row["forecast"] >= max(1, $peakForecast * 0.75)
);

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
    <title>Demand Forecasting | Jollibee Central Plaza Admin</title>

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
                        <label class="form-label small fw-semibold mb-1">Forecast Date</label>
                        <input type="date"
                               name="date"
                               class="form-control"
                               value="<?= e($forecastDate) ?>">
                    </div>

                    <button type="submit" class="btn btn-jollibee">
                        <i class="bi bi-arrow-repeat me-1"></i> Forecast
                    </button>
                </form>
            </div>

            <div class="alert alert-light border mb-4">
                <i class="bi bi-info-circle text-jollibee me-2"></i>
                Forecast is based on paid, non-cancelled orders from the previous
                <strong>30 days</strong>, grouped by hour. The selected day's recorded
                order activity is also considered when available.
            </div>

            <div class="row g-3 mb-4">

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Forecasted Orders</small>
                                <h4 class="fw-bold mb-0"><?= number_format($totalForecast) ?></h4>
                            </div>
                            <div class="summary-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Peak Hour</small>
                                <h4 class="fw-bold mb-0"><?= e($peakStart) ?></h4>
                            </div>
                            <div class="summary-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Peak Forecast</small>
                                <h4 class="fw-bold mb-0"><?= number_format($peakForecast) ?></h4>
                                <small class="text-muted">orders/hour</small>
                            </div>
                            <div class="summary-icon">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card summary-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Active Orders</small>
                                <h4 class="fw-bold mb-0"><?= number_format($activeOrders) ?></h4>
                                <small class="text-muted">selected date</small>
                            </div>
                            <div class="summary-icon">
                                <i class="bi bi-basket"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1">Hourly Demand Forecast</h5>
                    <small class="text-muted">
                        Historical average compared with recorded and forecasted demand
                    </small>
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
                                <?php foreach ($forecastRows as $row): ?>
                                    <?php
                                        $isHigh = $peakForecast > 0 &&
                                            $row["forecast"] >= ($peakForecast * 0.75);

                                        $level = "Low";
                                        $badge = "bg-secondary";

                                        if ($peakForecast > 0) {
                                            $ratio = $row["forecast"] / $peakForecast;

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
                                        <td class="fw-semibold">
                                            <?= e($row["label"]) ?>
                                        </td>

                                        <td>
                                            <?= number_format($row["historical"], 1) ?>
                                        </td>

                                        <td>
                                            <?= number_format($row["actual"]) ?>
                                        </td>

                                        <td class="fw-bold text-jollibee">
                                            <?= number_format($row["forecast"]) ?>
                                        </td>

                                        <td>
                                            <span class="badge <?= $badge ?>">
                                                <?= $level ?>
                                            </span>
                                        </td>
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
                                Hours with the highest expected demand
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
                                        $percentage = $peakForecast > 0
                                            ? min(100, ($row["forecast"] / $peakForecast) * 100)
                                            : 0;
                                    ?>

                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-semibold">
                                                <?= e($row["label"]) ?>
                                            </span>
                                            <span class="fw-semibold">
                                                <?= number_format($row["forecast"]) ?> orders
                                            </span>
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
                            <small class="text-muted">How the estimate is calculated</small>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-calendar3 text-jollibee me-2"></i>
                                    Historical Period
                                </div>
                                <small class="text-muted">
                                    <?= e(date("M d, Y", strtotime($historyStart))) ?>
                                    to
                                    <?= e(date("M d, Y", strtotime($historyEnd))) ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-clock-history text-jollibee me-2"></i>
                                    Hourly Pattern
                                </div>
                                <small class="text-muted">
                                    Orders are grouped by hour to identify periods
                                    with higher demand.
                                </small>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold">
                                    <i class="bi bi-activity text-jollibee me-2"></i>
                                    Current Activity
                                </div>
                                <small class="text-muted">
                                    Recorded orders for the selected date are shown
                                    alongside the historical forecast.
                                </small>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    <i class="bi bi-exclamation-triangle text-jollibee me-2"></i>
                                    Limitation
                                </div>
                                <small class="text-muted">
                                    This is a simple forecasting method based on
                                    available order history and is not an advanced
                                    machine-learning prediction model.
                                </small>
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
