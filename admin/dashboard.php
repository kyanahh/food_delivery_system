<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| TODAY'S ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");

$todayOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| TODAY'S SALES
|--------------------------------------------------------------------------
| Only PAID orders and exclude cancelled orders.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE DATE(created_at) = CURDATE()
      AND payment_status = 'Paid'
      AND order_status <> 'Cancelled'
");

$todaySales = (float) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| TOTAL CUSTOMERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| ACTIVE RIDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'rider'
      AND account_status = 'active'
");

$activeRiders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Pending'
");

$pendingOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PREPARING ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Preparing'
");

$preparingOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| COMPLETED ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Completed'
");

$completedOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DELIVERY ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_type = 'Delivery'
");

$deliveryOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PICKUP ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_type = 'Pickup'
");

$pickupOrders = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        o.order_id,
        o.order_number,
        o.total_amount,
        o.payment_status,
        o.order_status,
        o.order_type,
        o.created_at,
        u.first_name,
        u.last_name
    FROM orders o
    INNER JOIN users u
        ON u.user_id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
");

$recentOrders = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| FORECASTING DATA CHECK
|--------------------------------------------------------------------------
| The forecasting page uses paid, non-cancelled orders
| from the previous 30 days.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE payment_status = 'Paid'
      AND order_status <> 'Cancelled'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");

$forecastDataCount = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| FORECAST STATUS
|--------------------------------------------------------------------------
*/

if ($forecastDataCount >= 100) {

    $forecastStatus = "Ready";
    $forecastBadge = "success";
    $forecastMessage = number_format($forecastDataCount)
        . " historical orders available";

} elseif ($forecastDataCount > 0) {

    $forecastStatus = "Limited Data";
    $forecastBadge = "warning";
    $forecastMessage = number_format($forecastDataCount)
        . " historical orders available";

} else {

    $forecastStatus = "No Data";
    $forecastBadge = "danger";
    $forecastMessage = "No historical order data available";
}

/*
|--------------------------------------------------------------------------
| ORDER STATUS SUMMARY
|--------------------------------------------------------------------------
*/

$statusCounts = [];

$statusStmt = $pdo->query("
    SELECT
        order_status,
        COUNT(*) AS total
    FROM orders
    GROUP BY order_status
");

foreach ($statusStmt->fetchAll() as $row) {
    $statusCounts[$row["order_status"]] = (int) $row["total"];
}

/*
|--------------------------------------------------------------------------
| STATUS VALUES
|--------------------------------------------------------------------------
*/

$pendingCount = $statusCounts["Pending"] ?? 0;
$confirmedCount = $statusCounts["Confirmed"] ?? 0;
$preparingCount = $statusCounts["Preparing"] ?? 0;
$readyDeliveryCount = $statusCounts["Ready for Delivery"] ?? 0;
$readyPickupCount = $statusCounts["Ready for Pickup"] ?? 0;
$outDeliveryCount = $statusCounts["Out for Delivery"] ?? 0;
$completedCount = $statusCounts["Completed"] ?? 0;
$cancelledCount = $statusCounts["Cancelled"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>
        Admin Dashboard | Jollibee Central Plaza
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

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
            background: rgba(215,25,32,.1);
            color: var(--jollibee-red);
            font-size: 1.35rem;
        }

        .table th {
            white-space: nowrap;
        }

        .status-row {
            border-bottom: 1px solid #eee;
        }

        .status-row:last-child {
            border-bottom: 0;
        }

    </style>

</head>

<body>

<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-dark bg-jollibee shadow-sm">

    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >
            Jollibee Central Plaza
            <span class="fw-normal">| Admin</span>
        </a>

        <div class="d-flex align-items-center gap-3 text-white">

            <span>

                <i class="bi bi-person-circle me-1"></i>

                <?= e(
                    ($_SESSION["first_name"] ?? "Admin")
                    . " "
                    . ($_SESSION["last_name"] ?? "")
                ) ?>

            </span>

            <a
                href="../logout.php"
                class="btn btn-light btn-sm"
            >
                <i class="bi bi-box-arrow-right me-1"></i>
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =========================================================
     PAGE LAYOUT
========================================================= -->

<div class="container-fluid">

    <div class="row">

        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <aside
            class="col-md-3 col-lg-2 bg-white border-end sidebar p-3"
        >

            <div class="text-center py-3 mb-3 border-bottom">

                <div class="text-jollibee fs-1">

                    <i class="bi bi-shield-lock-fill"></i>

                </div>

                <div class="fw-bold">

                    <?= e(
                        ($_SESSION["first_name"] ?? "Admin")
                        . " "
                        . ($_SESSION["last_name"] ?? "")
                    ) ?>

                </div>

                <small class="text-muted">
                    Administrator
                </small>

            </div>


            <nav class="nav flex-column">

                <a
                    class="nav-link active"
                    href="dashboard.php"
                >
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>

                <a
                    class="nav-link"
                    href="users.php"
                >
                    <i class="bi bi-people me-2"></i>
                    User Management
                </a>

                <a
                    class="nav-link"
                    href="menu.php"
                >
                    <i class="bi bi-grid me-2"></i>
                    Menu Management
                </a>

                <a
                    class="nav-link"
                    href="orders.php"
                >
                    <i class="bi bi-receipt me-2"></i>
                    Orders
                </a>

                <a
                    class="nav-link"
                    href="sales.php"
                >
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Sales Reports
                </a>

                <a
                    class="nav-link"
                    href="forecast.php"
                >
                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Demand Forecasting
                </a>

                <a
                    class="nav-link"
                    href="settings.php"
                >
                    <i class="bi bi-gear me-2"></i>
                    System Settings
                </a>

            </nav>

        </aside>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <main class="col-md-9 col-lg-10 p-4">

            <!-- HEADER -->

            <div class="mb-4">

                <h2 class="fw-bold mb-1">
                    Admin Dashboard
                </h2>

                <p class="text-muted mb-0">
                    Monitor the Jollibee Central Plaza food
                    delivery system.
                </p>

            </div>


            <!-- =================================================
                 SUMMARY CARDS
            ================================================== -->

            <div class="row g-3 mb-4">

                <!-- TODAY'S ORDERS -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="text-muted small">
                                        Today's Orders
                                    </div>

                                    <div class="fs-3 fw-bold">
                                        <?= number_format($todayOrders) ?>
                                    </div>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-receipt"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TODAY'S SALES -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="text-muted small">
                                        Today's Sales
                                    </div>

                                    <div class="fs-3 fw-bold">
                                        ₱<?= number_format(
                                            $todaySales,
                                            2
                                        ) ?>
                                    </div>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-cash-stack"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CUSTOMERS -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="text-muted small">
                                        Customers
                                    </div>

                                    <div class="fs-3 fw-bold">
                                        <?= number_format(
                                            $totalCustomers
                                        ) ?>
                                    </div>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-people"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ACTIVE RIDERS -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <div class="text-muted small">
                                        Active Riders
                                    </div>

                                    <div class="fs-3 fw-bold">
                                        <?= number_format(
                                            $activeRiders
                                        ) ?>
                                    </div>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-bicycle"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SECONDARY SUMMARY
            ================================================== -->

            <div class="row g-3 mb-4">

                <div class="col-md-3">

                    <div class="card summary-card">

                        <div class="card-body">

                            <div class="text-muted small">
                                Total Orders
                            </div>

                            <div class="fs-4 fw-bold">
                                <?= number_format($totalOrders) ?>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="card summary-card">

                        <div class="card-body">

                            <div class="text-muted small">
                                Pending Orders
                            </div>

                            <div class="fs-4 fw-bold text-warning">
                                <?= number_format($pendingOrders) ?>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="card summary-card">

                        <div class="card-body">

                            <div class="text-muted small">
                                Preparing Orders
                            </div>

                            <div class="fs-4 fw-bold text-info">
                                <?= number_format($preparingOrders) ?>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="card summary-card">

                        <div class="card-body">

                            <div class="text-muted small">
                                Completed Orders
                            </div>

                            <div class="fs-4 fw-bold text-success">
                                <?= number_format($completedOrders) ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 DEMAND FORECASTING
            ================================================== -->

            <div class="card summary-card mb-4">

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between
                               align-items-center flex-wrap gap-3"
                    >

                        <div>

                            <h5 class="fw-bold mb-1">
                                Demand Forecasting
                            </h5>

                            <p class="text-muted mb-0">
                                Monitor predicted food demand
                                during peak hours.
                            </p>

                        </div>

                        <a
                            href="forecast.php"
                            class="btn btn-jollibee"
                        >

                            <i class="bi bi-graph-up-arrow me-1"></i>

                            View Forecast

                        </a>

                    </div>


                    <div class="row g-3 mt-3">

                        <div class="col-md-6">

                            <div class="border rounded p-3 h-100">

                                <small class="text-muted">
                                    Forecast Data
                                </small>

                                <h4 class="fw-bold mt-1 mb-2">

                                    <?= e($forecastStatus) ?>

                                </h4>

                                <span
                                    class="badge text-bg-<?= e(
                                        $forecastBadge
                                    ) ?>"
                                >

                                    <?= e($forecastMessage) ?>

                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="border rounded p-3 h-100">

                                <small class="text-muted">
                                    Peak-Hour Monitoring
                                </small>

                                <h4 class="fw-bold mt-1 mb-2">
                                    Active
                                </h4>

                                <span class="badge text-bg-success">
                                    System Ready
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 LOWER CONTENT
            ================================================== -->

            <div class="row g-4">

                <!-- RECENT ORDERS -->

                <div class="col-lg-8">

                    <div class="card summary-card">

                        <div class="card-header bg-white py-3">

                            <div
                                class="d-flex justify-content-between
                                       align-items-center"
                            >

                                <h5 class="fw-bold mb-0">
                                    Recent Orders
                                </h5>

                                <a
                                    href="orders.php"
                                    class="btn btn-sm btn-jollibee"
                                >
                                    View All
                                </a>

                            </div>

                        </div>


                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table
                                    class="table table-hover
                                           align-middle mb-0"
                                >

                                    <thead class="table-light">

                                        <tr>

                                            <th>Order</th>

                                            <th>Customer</th>

                                            <th>Total</th>

                                            <th>Payment</th>

                                            <th>Status</th>

                                            <th>Date</th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    <?php if (!$recentOrders): ?>

                                        <tr>

                                            <td
                                                colspan="6"
                                                class="text-center
                                                       text-muted py-4"
                                            >
                                                No orders available.
                                            </td>

                                        </tr>

                                    <?php else: ?>

                                        <?php foreach (
                                            $recentOrders
                                            as $order
                                        ): ?>

                                            <tr>

                                                <td class="fw-semibold">

                                                    <?= e(
                                                        $order["order_number"]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= e(
                                                        $order["first_name"]
                                                        . " "
                                                        . $order["last_name"]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    ₱<?= number_format(
                                                        (float)
                                                        $order[
                                                            "total_amount"
                                                        ],
                                                        2
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?php if (
                                                        $order[
                                                            "payment_status"
                                                        ] === "Paid"
                                                    ): ?>

                                                        <span
                                                            class="badge
                                                                   text-bg-success"
                                                        >
                                                            Paid
                                                        </span>

                                                    <?php else: ?>

                                                        <span
                                                            class="badge
                                                                   text-bg-warning"
                                                        >
                                                            <?= e(
                                                                $order[
                                                                    "payment_status"
                                                                ]
                                                            ) ?>
                                                        </span>

                                                    <?php endif; ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    $status = $order[
                                                        "order_status"
                                                    ];

                                                    $statusClass = match (
                                                        $status
                                                    ) {

                                                        "Completed"
                                                            => "success",

                                                        "Cancelled"
                                                            => "danger",

                                                        "Pending"
                                                            => "warning",

                                                        "Preparing"
                                                            => "info",

                                                        default
                                                            => "secondary"
                                                    };

                                                    ?>

                                                    <span
                                                        class="badge
                                                               text-bg-<?= $statusClass ?>"
                                                    >

                                                        <?= e($status) ?>

                                                    </span>

                                                </td>


                                                <td>

                                                    <small>
                                                        <?= date(
                                                            "M d, Y h:i A",
                                                            strtotime(
                                                                $order[
                                                                    "created_at"
                                                                ]
                                                            )
                                                        ) ?>
                                                    </small>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SYSTEM OVERVIEW -->

                <div class="col-lg-4">

                    <div class="card summary-card">

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">
                                System Overview
                            </h5>

                        </div>

                        <div class="card-body">


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>
                                    System Status
                                </span>

                                <span class="badge text-bg-success">
                                    Online
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>
                                    Database
                                </span>

                                <span class="badge text-bg-success">
                                    Connected
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>
                                    Forecasting
                                </span>

                                <span
                                    class="badge
                                           text-bg-<?= e(
                                               $forecastBadge
                                           ) ?>"
                                >
                                    <?= e($forecastStatus) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>
                                    Delivery Orders
                                </span>

                                <strong>
                                    <?= number_format(
                                        $deliveryOrders
                                    ) ?>
                                </strong>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       py-2"
                            >

                                <span>
                                    Pickup Orders
                                </span>

                                <strong>
                                    <?= number_format(
                                        $pickupOrders
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- ORDER STATUS -->

                    <div class="card summary-card mt-4">

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">
                                Order Status
                            </h5>

                        </div>

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Pending</span>

                                <span class="badge text-bg-warning">
                                    <?= number_format($pendingCount) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Confirmed</span>

                                <span class="badge text-bg-secondary">
                                    <?= number_format($confirmedCount) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Preparing</span>

                                <span class="badge text-bg-info">
                                    <?= number_format($preparingCount) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Ready</span>

                                <span class="badge text-bg-primary">

                                    <?= number_format(
                                        $readyDeliveryCount
                                        + $readyPickupCount
                                    ) ?>

                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Out for Delivery</span>

                                <span class="badge text-bg-dark">
                                    <?= number_format(
                                        $outDeliveryCount
                                    ) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       status-row py-2"
                            >

                                <span>Completed</span>

                                <span class="badge text-bg-success">
                                    <?= number_format(
                                        $completedCount
                                    ) ?>
                                </span>

                            </div>


                            <div
                                class="d-flex justify-content-between
                                       py-2"
                            >

                                <span>Cancelled</span>

                                <span class="badge text-bg-danger">
                                    <?= number_format(
                                        $cancelledCount
                                    ) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>