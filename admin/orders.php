<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");
$orderType = trim($_GET["order_type"] ?? "");
$paymentStatus = trim($_GET["payment_status"] ?? "");

$allowedStatuses = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Ready for Delivery",
    "Ready for Pickup",
    "Out for Delivery",
    "Completed",
    "Cancelled"
];

$allowedOrderTypes = [
    "Delivery",
    "Pickup"
];

$allowedPaymentStatuses = [
    "Pending",
    "Paid",
    "Failed"
];

/*
|--------------------------------------------------------------------------
| Build Orders Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.order_id,
        o.order_number,
        o.user_id,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.order_type,
        o.delivery_address,
        o.customer_notes,
        o.cash_received,
        o.change_amount,
        o.created_at,
        o.updated_at,

        u.first_name,
        u.last_name,
        u.email,
        u.phone,

        r.first_name AS rider_first_name,
        r.last_name AS rider_last_name

    FROM orders o

    INNER JOIN users u
        ON o.user_id = u.user_id

    LEFT JOIN users r
        ON o.rider_id = r.user_id

    WHERE 1=1
";

$params = [];

if ($search !== "") {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.first_name LIKE ?
            OR u.last_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $status;
}

if (in_array($orderType, $allowedOrderTypes, true)) {
    $sql .= " AND o.order_type = ?";
    $params[] = $orderType;
}

if (in_array($paymentStatus, $allowedPaymentStatuses, true)) {
    $sql .= " AND o.payment_status = ?";
    $params[] = $paymentStatus;
}

$sql .= "
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary Counts
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(order_status = 'Pending') AS pending_orders,
        SUM(order_status IN ('Ready for Delivery', 'Out for Delivery')) AS active_deliveries,
        SUM(order_status = 'Completed') AS completed_orders,
        SUM(order_status = 'Cancelled') AS cancelled_orders,
        SUM(payment_status = 'Pending') AS unpaid_orders
    FROM orders
");

$counts = $countStmt->fetch();

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function orderStatusClass($status)
{
    return match ($status) {
        "Pending" => "text-bg-warning",
        "Confirmed" => "text-bg-primary",
        "Preparing" => "text-bg-info",
        "Ready for Delivery",
        "Ready for Pickup" => "text-bg-warning",
        "Out for Delivery" => "text-bg-primary",
        "Completed" => "text-bg-success",
        "Cancelled" => "text-bg-danger",
        default => "text-bg-secondary"
    };
}

function paymentStatusClass($status)
{
    return match ($status) {
        "Paid" => "text-bg-success",
        "Failed" => "text-bg-danger",
        default => "text-bg-warning"
    };
}

/*
|--------------------------------------------------------------------------
| Get Items For Selected Order
|--------------------------------------------------------------------------
*/

$selectedOrder = null;
$selectedItems = [];

if (isset($_GET["view"]) && filter_var($_GET["view"], FILTER_VALIDATE_INT)) {

    $viewId = (int) $_GET["view"];

    $orderStmt = $pdo->prepare("
        SELECT
            o.*,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            r.first_name AS rider_first_name,
            r.last_name AS rider_last_name
        FROM orders o
        INNER JOIN users u
            ON o.user_id = u.user_id
        LEFT JOIN users r
            ON o.rider_id = r.user_id
        WHERE o.order_id = ?
        LIMIT 1
    ");

    $orderStmt->execute([$viewId]);
    $selectedOrder = $orderStmt->fetch();

    if ($selectedOrder) {

        $itemsStmt = $pdo->prepare("
            SELECT
                oi.quantity,
                oi.unit_price,
                oi.subtotal,
                f.food_name
            FROM order_items oi
            INNER JOIN food_items f
                ON oi.food_id = f.food_id
            WHERE oi.order_id = ?
            ORDER BY oi.order_item_id ASC
        ");

        $itemsStmt->execute([$viewId]);
        $selectedItems = $itemsStmt->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>

    :root {
        --jollibee-red: #d71920;
        --jollibee-dark-red: #b51218;
    }
    body { background: #f8f9fa; }
    .bg-jollibee { background-color: var(--jollibee-red) !important; }
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
    .text-jollibee { color: var(--jollibee-red) !important; }
    .sidebar { min-height: calc(100vh - 56px); }
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
    .table th { white-space: nowrap; }

    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-jollibee shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            Jollibee Central Plaza <span class="fw-normal">| Admin</span>
        </a>
        <div class="d-flex align-items-center gap-3 text-white">
            <span>
                <i class="bi bi-person-circle me-1"></i>
                <?= e(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </span>
            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
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
                <?= e(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </div>
            <small class="text-muted">Administrator</small>
        </div>
        <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a class="nav-link" href="menu.php">
                    <i class="bi bi-grid me-2"></i>Menu Management
                </a>
                <a class="nav-link active" href="orders.php">
                    <i class="bi bi-receipt me-2"></i>Orders
                </a>
                <a class="nav-link" href="sales.php">
                    <i class="bi bi-bar-chart-line me-2"></i>Sales Reports
                </a>
                <a class="nav-link" href="forecast.php">
                    <i class="bi bi-graph-up-arrow me-2"></i>Demand Forecasting
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear me-2"></i>System Settings
                </a>
        </nav>
    </aside>
        <main class="col-md-9 col-lg-10 p-4">


            <div class="mb-4">

                <h2 class="fw-bold mb-1">
                    Orders
                </h2>

                <p class="text-muted mb-0">
                    Monitor customer orders, payment status, and delivery progress.
                </p>

            </div>


            <!-- SUMMARY -->

            <div class="row g-3 mb-4">

                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>
                                    <div class="text-muted small">
                                        Total Orders
                                    </div>

                                    <div class="fs-4 fw-bold">
                                        <?= (int) ($counts["total_orders"] ?? 0) ?>
                                    </div>
                                </div>

                                <div
                                    class="bg-danger-subtle text-danger rounded-circle
                                           d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;"
                                >
                                    <i class="bi bi-receipt"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>
                                    <div class="text-muted small">
                                        Pending
                                    </div>

                                    <div class="fs-4 fw-bold text-warning">
                                        <?= (int) ($counts["pending_orders"] ?? 0) ?>
                                    </div>
                                </div>

                                <div
                                    class="bg-warning-subtle text-warning rounded-circle
                                           d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;"
                                >
                                    <i class="bi bi-hourglass-split"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>
                                    <div class="text-muted small">
                                        Active Deliveries
                                    </div>

                                    <div class="fs-4 fw-bold">
                                        <?= (int) ($counts["active_deliveries"] ?? 0) ?>
                                    </div>
                                </div>

                                <div
                                    class="bg-primary-subtle text-primary rounded-circle
                                           d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;"
                                >
                                    <i class="bi bi-bicycle"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>
                                    <div class="text-muted small">
                                        Unpaid Orders
                                    </div>

                                    <div class="fs-4 fw-bold text-danger">
                                        <?= (int) ($counts["unpaid_orders"] ?? 0) ?>
                                    </div>
                                </div>

                                <div
                                    class="bg-danger-subtle text-danger rounded-circle
                                           d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;"
                                >
                                    <i class="bi bi-credit-card"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- FILTERS -->

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <form
                        method="get"
                        class="row g-3"
                    >

                        <div class="col-lg-4">

                            <label class="form-label small fw-semibold">
                                Search
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Order number, customer, email, phone"
                            >

                        </div>


                        <div class="col-lg-2">

                            <label class="form-label small fw-semibold">
                                Order Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="">
                                    All Status
                                </option>

                                <?php foreach ($allowedStatuses as $itemStatus): ?>

                                    <option
                                        value="<?= e($itemStatus) ?>"
                                        <?= $status === $itemStatus ? "selected" : "" ?>
                                    >
                                        <?= e($itemStatus) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-lg-2">

                            <label class="form-label small fw-semibold">
                                Order Type
                            </label>

                            <select
                                name="order_type"
                                class="form-select"
                            >

                                <option value="">
                                    All Types
                                </option>

                                <?php foreach ($allowedOrderTypes as $itemType): ?>

                                    <option
                                        value="<?= e($itemType) ?>"
                                        <?= $orderType === $itemType ? "selected" : "" ?>
                                    >
                                        <?= e($itemType) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-lg-2">

                            <label class="form-label small fw-semibold">
                                Payment
                            </label>

                            <select
                                name="payment_status"
                                class="form-select"
                            >

                                <option value="">
                                    All Payments
                                </option>

                                <?php foreach ($allowedPaymentStatuses as $itemPayment): ?>

                                    <option
                                        value="<?= e($itemPayment) ?>"
                                        <?= $paymentStatus === $itemPayment ? "selected" : "" ?>
                                    >
                                        <?= e($itemPayment) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-lg-2 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn btn-jollibee flex-grow-1"
                            >
                                <i class="bi bi-search me-1"></i>
                                Search
                            </button>

                            <a
                                href="orders.php"
                                class="btn btn-outline-secondary"
                            >
                                Clear
                            </a>

                        </div>

                    </form>

                </div>

            </div>


            <!-- ORDER TABLE -->

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="fw-bold mb-0">
                            Order Records
                        </h5>

                        <span class="badge bg-danger">
                            <?= count($orders) ?> Orders
                        </span>

                    </div>


                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Rider</th>
                                    <th>Date</th>
                                    <th class="text-end">Action</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php if (!$orders): ?>

                                <tr>

                                    <td
                                        colspan="9"
                                        class="text-center py-5 text-muted"
                                    >

                                        <i class="bi bi-receipt fs-2 d-block mb-2"></i>

                                        No orders found.

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($orders as $order): ?>

                                    <?php
                                    $customerName = trim(
                                        $order["first_name"] . " " . $order["last_name"]
                                    );

                                    $riderName = trim(
                                        ($order["rider_first_name"] ?? "") .
                                        " " .
                                        ($order["rider_last_name"] ?? "")
                                    );

                                    if ($riderName === "") {
                                        $riderName = "Not assigned";
                                    }
                                    ?>

                                    <tr>

                                        <td>

                                            <div class="fw-semibold">
                                                <?= e($order["order_number"]) ?>
                                            </div>

                                            <small class="text-muted">
                                                #<?= (int) $order["order_id"] ?>
                                            </small>

                                        </td>


                                        <td>

                                            <div class="fw-semibold">
                                                <?= e($customerName) ?>
                                            </div>

                                            <small class="text-muted">
                                                <?= e($order["phone"] ?: $order["email"]) ?>
                                            </small>

                                        </td>


                                        <td>

                                            <?php if ($order["order_type"] === "Delivery"): ?>

                                                <span class="badge text-bg-primary">
                                                    <i class="bi bi-bicycle me-1"></i>
                                                    Delivery
                                                </span>

                                            <?php else: ?>

                                                <span class="badge text-bg-secondary">
                                                    <i class="bi bi-shop me-1"></i>
                                                    Pickup
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td class="fw-bold">
                                            ₱<?= number_format(
                                                (float) $order["total_amount"],
                                                2
                                            ) ?>
                                        </td>


                                        <td>

                                            <div>
                                                <span
                                                    class="badge <?= paymentStatusClass($order["payment_status"]) ?>"
                                                >
                                                    <?= e($order["payment_status"]) ?>
                                                </span>
                                            </div>

                                            <small class="text-muted">
                                                <?= e($order["payment_method"]) ?>
                                            </small>

                                        </td>


                                        <td>

                                            <span
                                                class="badge <?= orderStatusClass($order["order_status"]) ?>"
                                            >
                                                <?= e($order["order_status"]) ?>
                                            </span>

                                        </td>


                                        <td>

                                            <?php if ($order["order_type"] === "Delivery"): ?>

                                                <?php if ($riderName !== "Not assigned"): ?>

                                                    <span class="small">
                                                        <i class="bi bi-person-badge me-1"></i>
                                                        <?= e($riderName) ?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="text-muted small">
                                                        Not assigned
                                                    </span>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <span class="text-muted small">
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <small>
                                                <?= date(
                                                    "M d, Y",
                                                    strtotime($order["created_at"])
                                                ) ?>
                                            </small>

                                            <br>

                                            <small class="text-muted">
                                                <?= date(
                                                    "h:i A",
                                                    strtotime($order["created_at"])
                                                ) ?>
                                            </small>

                                        </td>


                                        <td class="text-end">

                                            <a
                                                href="orders.php?view=<?= (int) $order["order_id"] ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&order_type=<?= urlencode($orderType) ?>&payment_status=<?= urlencode($paymentStatus) ?>"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="bi bi-eye me-1"></i>
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <?php if ($selectedOrder): ?>

            <div
                class="modal fade"
                id="viewOrderModal"
                tabindex="-1"
                aria-labelledby="viewOrderModalLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

                    <div class="modal-content border-0 shadow">

                        <!-- HEADER -->
                        <div class="modal-header bg-jollibee text-white">

                            <div>
                                <h5 class="modal-title fw-bold mb-1" id="viewOrderModalLabel">
                                    <i class="bi bi-receipt me-2"></i>
                                    Order Details
                                </h5>

                                <small>
                                    <?= e($selectedOrder["order_number"]) ?>
                                </small>
                            </div>

                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            ></button>

                        </div>


                        <!-- BODY -->
                        <div class="modal-body">

                            <!-- ORDER SUMMARY -->
                            <div class="row g-3 mb-4">

                                <div class="col-md-3">

                                    <div class="card border-0 bg-light h-100">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Order Number
                                            </small>

                                            <div class="fw-bold">
                                                <?= e($selectedOrder["order_number"]) ?>
                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="card border-0 bg-light h-100">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Order Type
                                            </small>

                                            <div class="mt-1">

                                                <?php if ($selectedOrder["order_type"] === "Delivery"): ?>

                                                    <span class="badge text-bg-primary">
                                                        <i class="bi bi-bicycle me-1"></i>
                                                        Delivery
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge text-bg-secondary">
                                                        <i class="bi bi-shop me-1"></i>
                                                        Pickup
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="card border-0 bg-light h-100">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Order Status
                                            </small>

                                            <div class="mt-1">

                                                <span
                                                    class="badge <?= orderStatusClass($selectedOrder["order_status"]) ?>"
                                                >
                                                    <?= e($selectedOrder["order_status"]) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="card border-0 bg-light h-100">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Total Amount
                                            </small>

                                            <div class="fw-bold text-jollibee fs-5">
                                                ₱<?= number_format(
                                                    (float) $selectedOrder["total_amount"],
                                                    2
                                                ) ?>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="row g-4">

                                <!-- CUSTOMER INFORMATION -->
                                <div class="col-lg-6">

                                    <div class="card border-0 shadow-sm h-100">

                                        <div class="card-header bg-white fw-bold">

                                            <i class="bi bi-person me-2 text-jollibee"></i>
                                            Customer Information

                                        </div>

                                        <div class="card-body">

                                            <div class="mb-3">

                                                <small class="text-muted d-block">
                                                    Name
                                                </small>

                                                <span class="fw-semibold">
                                                    <?= e(
                                                        trim(
                                                            $selectedOrder["first_name"] .
                                                            " " .
                                                            $selectedOrder["last_name"]
                                                        )
                                                    ) ?>
                                                </span>

                                            </div>


                                            <div class="mb-3">

                                                <small class="text-muted d-block">
                                                    Email
                                                </small>

                                                <span>
                                                    <?= e($selectedOrder["email"]) ?>
                                                </span>

                                            </div>


                                            <div class="mb-3">

                                                <small class="text-muted d-block">
                                                    Phone
                                                </small>

                                                <span>
                                                    <?= e($selectedOrder["phone"] ?: "Not provided") ?>
                                                </span>

                                            </div>


                                            <div>

                                                <small class="text-muted d-block">
                                                    Delivery Address
                                                </small>

                                                <span>
                                                    <?= e(
                                                        $selectedOrder["delivery_address"]
                                                        ?: "Not applicable"
                                                    ) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- PAYMENT INFORMATION -->
                                <div class="col-lg-6">

                                    <div class="card border-0 shadow-sm h-100">

                                        <div class="card-header bg-white fw-bold">

                                            <i class="bi bi-credit-card me-2 text-jollibee"></i>
                                            Payment Information

                                        </div>

                                        <div class="card-body">

                                            <div class="row g-3">

                                                <div class="col-sm-6">

                                                    <small class="text-muted d-block">
                                                        Payment Method
                                                    </small>

                                                    <span class="fw-semibold">
                                                        <?= e($selectedOrder["payment_method"]) ?>
                                                    </span>

                                                </div>


                                                <div class="col-sm-6">

                                                    <small class="text-muted d-block">
                                                        Payment Status
                                                    </small>

                                                    <span
                                                        class="badge <?= paymentStatusClass($selectedOrder["payment_status"]) ?>"
                                                    >
                                                        <?= e($selectedOrder["payment_status"]) ?>
                                                    </span>

                                                </div>


                                                <div class="col-sm-6">

                                                    <small class="text-muted d-block">
                                                        Cash Received
                                                    </small>

                                                    <span>
                                                        <?php if ($selectedOrder["cash_received"] !== null): ?>

                                                            ₱<?= number_format(
                                                                (float) $selectedOrder["cash_received"],
                                                                2
                                                            ) ?>

                                                        <?php else: ?>

                                                            —

                                                        <?php endif; ?>
                                                    </span>

                                                </div>


                                                <div class="col-sm-6">

                                                    <small class="text-muted d-block">
                                                        Change
                                                    </small>

                                                    <span>
                                                        <?php if ($selectedOrder["change_amount"] !== null): ?>

                                                            ₱<?= number_format(
                                                                (float) $selectedOrder["change_amount"],
                                                                2
                                                            ) ?>

                                                        <?php else: ?>

                                                            —

                                                        <?php endif; ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- RIDER INFORMATION -->
                                <?php if ($selectedOrder["order_type"] === "Delivery"): ?>

                                    <div class="col-lg-6">

                                        <div class="card border-0 shadow-sm">

                                            <div class="card-header bg-white fw-bold">

                                                <i class="bi bi-person-badge me-2 text-jollibee"></i>
                                                Rider Information

                                            </div>

                                            <div class="card-body">

                                                <?php
                                                $selectedRider = trim(
                                                    ($selectedOrder["rider_first_name"] ?? "") .
                                                    " " .
                                                    ($selectedOrder["rider_last_name"] ?? "")
                                                );
                                                ?>

                                                <?php if ($selectedRider !== ""): ?>

                                                    <span class="fw-semibold">
                                                        <?= e($selectedRider) ?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        No rider assigned.
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </div>

                                <?php endif; ?>


                                <!-- CUSTOMER NOTES -->
                                <div class="col-lg-6">

                                    <div class="card border-0 shadow-sm">

                                        <div class="card-header bg-white fw-bold">

                                            <i class="bi bi-chat-left-text me-2 text-jollibee"></i>
                                            Customer Notes

                                        </div>

                                        <div class="card-body">

                                            <?php if (!empty($selectedOrder["customer_notes"])): ?>

                                                <?= nl2br(
                                                    e($selectedOrder["customer_notes"])
                                                ) ?>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    No customer notes.
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- ORDER ITEMS -->
                            <div class="card border-0 shadow-sm mt-4">

                                <div class="card-header bg-white fw-bold">

                                    <i class="bi bi-basket me-2 text-jollibee"></i>
                                    Ordered Items

                                </div>

                                <div class="card-body p-0">

                                    <div class="table-responsive">

                                        <table class="table table-hover align-middle mb-0">

                                            <thead class="table-light">

                                                <tr>

                                                    <th class="px-3">
                                                        Food Item
                                                    </th>

                                                    <th class="text-center">
                                                        Quantity
                                                    </th>

                                                    <th class="text-end">
                                                        Unit Price
                                                    </th>

                                                    <th class="text-end px-3">
                                                        Subtotal
                                                    </th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                <?php if (!$selectedItems): ?>

                                                    <tr>

                                                        <td
                                                            colspan="4"
                                                            class="text-center text-muted py-4"
                                                        >
                                                            No items found for this order.
                                                        </td>

                                                    </tr>

                                                <?php else: ?>

                                                    <?php foreach ($selectedItems as $item): ?>

                                                        <tr>

                                                            <td class="px-3">

                                                                <span class="fw-semibold">
                                                                    <?= e($item["food_name"]) ?>
                                                                </span>

                                                            </td>

                                                            <td class="text-center">
                                                                <?= (int) $item["quantity"] ?>
                                                            </td>

                                                            <td class="text-end">

                                                                ₱<?= number_format(
                                                                    (float) $item["unit_price"],
                                                                    2
                                                                ) ?>

                                                            </td>

                                                            <td class="text-end px-3 fw-semibold">

                                                                ₱<?= number_format(
                                                                    (float) $item["subtotal"],
                                                                    2
                                                                ) ?>

                                                            </td>

                                                        </tr>

                                                    <?php endforeach; ?>

                                                <?php endif; ?>

                                            </tbody>

                                            <tfoot class="table-light">

                                                <tr>

                                                    <th
                                                        colspan="3"
                                                        class="text-end"
                                                    >
                                                        Order Total
                                                    </th>

                                                    <th class="text-end px-3 text-jollibee">

                                                        ₱<?= number_format(
                                                            (float) $selectedOrder["total_amount"],
                                                            2
                                                        ) ?>

                                                    </th>

                                                </tr>

                                            </tfoot>

                                        </table>

                                    </div>

                                </div>

                            </div>


                            <!-- DATE INFORMATION -->
                            <div class="row mt-4 g-3">

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Order Created
                                    </small>

                                    <div class="fw-semibold">
                                        <?= date(
                                            "M d, Y h:i A",
                                            strtotime($selectedOrder["created_at"])
                                        ) ?>
                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Last Updated
                                    </small>

                                    <div class="fw-semibold">
                                        <?= date(
                                            "M d, Y h:i A",
                                            strtotime($selectedOrder["updated_at"])
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- FOOTER -->
                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-jollibee"
                                onclick="printOrderReceipt()"
                            >
                                <i class="bi bi-printer me-1"></i>
                                Print Receipt
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                            >
                                Close
                            </button>

                        </div>

                    </div>

                </div>
            </div>


            <script>
                document.addEventListener("DOMContentLoaded", function () {

                    const modalElement = document.getElementById("viewOrderModal");

                    if (modalElement) {

                        const viewOrderModal =
                            new bootstrap.Modal(modalElement);

                        viewOrderModal.show();

                    }

                });
            </script>

        <?php endif; ?>

        
        <script>
            function printOrderReceipt() {

                const receiptWindow = window.open(
                    "",
                    "_blank",
                    "width=420,height=700"
                );

                if (!receiptWindow) {
                    alert("Please allow pop-ups to print the receipt.");
                    return;
                }

                receiptWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>

                        <meta charset="UTF-8">

                        <title>
                            Receipt - <?= e($selectedOrder["order_number"]) ?>
                        </title>

                        <style>

                            * {
                                box-sizing: border-box;
                            }

                            body {
                                font-family: Arial, Helvetica, sans-serif;
                                width: 80mm;
                                margin: 0 auto;
                                padding: 10px;
                                color: #000;
                                font-size: 12px;
                            }

                            .receipt {
                                width: 100%;
                            }

                            .text-center {
                                text-align: center;
                            }

                            .text-right {
                                text-align: right;
                            }

                            .bold {
                                font-weight: bold;
                            }

                            .header-title {
                                font-size: 18px;
                                font-weight: bold;
                                margin-bottom: 3px;
                            }

                            .header-subtitle {
                                font-size: 12px;
                                margin-bottom: 10px;
                            }

                            .divider {
                                border-top: 1px dashed #000;
                                margin: 8px 0;
                            }

                            .row {
                                display: flex;
                                justify-content: space-between;
                                gap: 8px;
                                margin-bottom: 4px;
                            }

                            .items {
                                width: 100%;
                            }

                            .item {
                                margin-bottom: 7px;
                            }

                            .item-name {
                                font-weight: bold;
                            }

                            .item-details {
                                display: flex;
                                justify-content: space-between;
                            }

                            .total {
                                font-size: 15px;
                                font-weight: bold;
                            }

                            .footer {
                                margin-top: 15px;
                                text-align: center;
                                font-size: 11px;
                            }

                            @media print {

                                body {
                                    width: 80mm;
                                    margin: 0;
                                }

                                @page {
                                    size: 80mm auto;
                                    margin: 0;
                                }

                            }

                        </style>

                    </head>

                    <body>

                        <div class="receipt">

                            <div class="text-center">

                                <div class="header-title">
                                    JOLLIBEE PLAZA CENTRAL
                                </div>

                                <div class="header-subtitle">
                                    Order Receipt
                                </div>

                            </div>


                            <div class="divider"></div>


                            <div class="row">
                                <span>Order No.</span>
                                <span class="bold">
                                    <?= e($selectedOrder["order_number"]) ?>
                                </span>
                            </div>


                            <div class="row">
                                <span>Date</span>
                                <span>
                                    <?= date(
                                        "M d, Y h:i A",
                                        strtotime($selectedOrder["created_at"])
                                    ) ?>
                                </span>
                            </div>


                            <div class="divider"></div>


                            <div class="bold">
                                CUSTOMER
                            </div>

                            <div>
                                <?= e(
                                    trim(
                                        $selectedOrder["first_name"] .
                                        " " .
                                        $selectedOrder["last_name"]
                                    )
                                ) ?>
                            </div>

                            <div>
                                <?= e($selectedOrder["phone"] ?: "No phone number") ?>
                            </div>


                            <div class="divider"></div>


                            <div class="bold">
                                ORDER TYPE
                            </div>

                            <div>
                                <?= e($selectedOrder["order_type"]) ?>
                            </div>


                            <?php if ($selectedOrder["order_type"] === "Delivery"): ?>

                                <div style="margin-top: 5px;">

                                    <div class="bold">
                                        DELIVERY ADDRESS
                                    </div>

                                    <div>
                                        <?= e(
                                            $selectedOrder["delivery_address"]
                                            ?: "Not provided"
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>


                            <div class="divider"></div>


                            <div class="bold">
                                ITEMS
                            </div>


                            <div class="items">

                                <?php foreach ($selectedItems as $item): ?>

                                    <div class="item">

                                        <div class="item-name">
                                            <?= e($item["food_name"]) ?>
                                        </div>

                                        <div class="item-details">

                                            <span>
                                                <?= (int) $item["quantity"] ?>
                                                ×
                                                ₱<?= number_format(
                                                    (float) $item["unit_price"],
                                                    2
                                                ) ?>
                                            </span>

                                            <span>
                                                ₱<?= number_format(
                                                    (float) $item["subtotal"],
                                                    2
                                                ) ?>
                                            </span>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>


                            <div class="divider"></div>


                            <div class="row total">

                                <span>
                                    TOTAL
                                </span>

                                <span>
                                    ₱<?= number_format(
                                        (float) $selectedOrder["total_amount"],
                                        2
                                    ) ?>
                                </span>

                            </div>


                            <div class="divider"></div>


                            <div class="row">

                                <span>
                                    Payment
                                </span>

                                <span>
                                    <?= e($selectedOrder["payment_method"]) ?>
                                </span>

                            </div>


                            <div class="row">

                                <span>
                                    Payment Status
                                </span>

                                <span>
                                    <?= e($selectedOrder["payment_status"]) ?>
                                </span>

                            </div>


                            <?php if ($selectedOrder["cash_received"] !== null): ?>

                                <div class="row">

                                    <span>
                                        Cash Received
                                    </span>

                                    <span>
                                        ₱<?= number_format(
                                            (float) $selectedOrder["cash_received"],
                                            2
                                        ) ?>
                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if ($selectedOrder["change_amount"] !== null): ?>

                                <div class="row">

                                    <span>
                                        Change
                                    </span>

                                    <span>
                                        ₱<?= number_format(
                                            (float) $selectedOrder["change_amount"],
                                            2
                                        ) ?>
                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if (!empty($selectedOrder["customer_notes"])): ?>

                                <div class="divider"></div>

                                <div class="bold">
                                    CUSTOMER NOTES
                                </div>

                                <div>
                                    <?= nl2br(
                                        e($selectedOrder["customer_notes"])
                                    ) ?>
                                </div>

                            <?php endif; ?>


                            <div class="footer">

                                <div class="divider"></div>

                                <strong>
                                    THANK YOU FOR ORDERING!
                                </strong>

                                <br>

                                Jollibee Plaza Central

                            </div>

                        </div>

                        <script>

                            window.onload = function () {

                                window.print();

                                window.onafterprint = function () {
                                    window.close();
                                };

                            };

                        <\/script>

                    </body>
                    </html>
                `);

                receiptWindow.document.close();
            }
            </script>


        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
