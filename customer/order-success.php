<?php

require_once "../includes/auth.php";
requireRole("customer");

require_once "../config/database.php";

$orderId = $_GET['id'] ?? null;

if (!$orderId || !is_numeric($orderId)) {
    header("Location: dashboard.php");
    exit;
}

$orderId = (int) $orderId;

$userId = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$orderSql = "
    SELECT
        o.order_id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.order_type,
        o.delivery_address,
        o.customer_notes,
        o.cash_received,
        o.change_amount,
        o.payment_proof,
        o.created_at
    FROM orders o
    WHERE o.order_id = :order_id
      AND o.user_id = :user_id
    LIMIT 1
";

$orderStmt = $pdo->prepare($orderSql);

$orderStmt->execute([
    ':order_id' => $orderId,
    ':user_id' => $userId
]);

$order = $orderStmt->fetch();

if (!$order) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$itemSql = "
    SELECT
        oi.order_item_id,
        oi.food_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        fi.food_name,
        fi.image
    FROM order_items oi
    LEFT JOIN food_items fi
        ON oi.food_id = fi.food_id
    WHERE oi.order_id = :order_id
    ORDER BY oi.order_item_id ASC
";

$itemStmt = $pdo->prepare($itemSql);

$itemStmt->execute([
    ':order_id' => $orderId
]);

$orderItems = $itemStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function peso($amount)
{
    return '₱' . number_format((float) $amount, 2);
}


/*
|--------------------------------------------------------------------------
| Customer Name
|--------------------------------------------------------------------------
*/

$firstName = $_SESSION['first_name'] ?? 'Customer';
$lastName = $_SESSION['last_name'] ?? '';

$customerName = trim($firstName . ' ' . $lastName);

if ($customerName === '') {
    $customerName = 'Customer';
}


/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$statusClass = 'bg-secondary';

switch ($order['order_status']) {

    case 'Pending':
        $statusClass = 'bg-warning text-dark';
        break;

    case 'Confirmed':
        $statusClass = 'bg-primary';
        break;

    case 'Preparing':
        $statusClass = 'bg-info text-dark';
        break;

    case 'Ready for Delivery':
        $statusClass = 'bg-success';
        break;

    case 'Out for Delivery':
        $statusClass = 'bg-primary';
        break;

    case 'Completed':
        $statusClass = 'bg-success';
        break;

    case 'Cancelled':
        $statusClass = 'bg-danger';
        break;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body class="customer-page">

<nav class="navbar navbar-dark customer-topbar shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a href="dashboard.php" class="customer-brand">
            <i class="bi bi-shop me-2"></i>Jollibee Central Plaza
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="menu.php" class="btn btn-light btn-sm">
                <i class="bi bi-grid me-1"></i>Menu
            </a>
            <a href="profile.php" class="btn btn-sm btn-outline-light"><i class="bi bi-person me-1"></i><span class="customer-user-name"><?= e($firstName) ?></span></a>
            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 customer-sidebar p-3">
            <div class="customer-profile text-center pb-3 mb-3">
                <div class="customer-avatar rounded-circle d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-person fs-3"></i>
                </div>
                <h6 class="mt-2 mb-0"><?= e($firstName) ?></h6>
                <small class="text-muted">Customer</small>
            </div>
            <div class="list-group customer-nav">
                <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                <a href="menu.php" class="list-group-item list-group-item-action"><i class="bi bi-grid me-2"></i>Browse Menu</a>
                <a href="cart.php" class="list-group-item list-group-item-action"><i class="bi bi-cart3 me-2"></i>My Cart</a>
                <a href="orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>My Orders</a>
                <a href="order-history.php" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Order History</a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </aside>

        <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
            <div class="text-center py-2 py-lg-4 mb-4">
                <div class="customer-success-icon rounded-circle d-inline-flex align-items-center justify-content-center fs-1 mb-3">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h1 class="fw-bold mb-2">Order Placed Successfully!</h1>
                <p class="text-muted mb-1">Thank you for ordering from Jollibee Central Plaza.</p>
                <div class="text-jollibee fw-bold fs-5">Order #<?= e($order["order_number"]) ?></div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card customer-success-card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-bag-check text-jollibee me-2"></i>Order Items
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orderItems)): ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="customer-order-item py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($item["image"])): ?>
                                                    <img src="../assets/uploads/menu/<?= e($item["image"]) ?>"
                                                         class="customer-item-image rounded"
                                                         alt="<?= e($item["food_name"] ?? "Food item") ?>"
                                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                                    <div class="customer-item-placeholder rounded d-none align-items-center justify-content-center d-flex">
                                                        <i class="bi bi-egg-fried text-muted"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="customer-item-placeholder rounded d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-egg-fried text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?= e($item["food_name"] ?? "Food Item") ?></div>
                                                <div class="text-muted small">
                                                    ₱<?= number_format((float)$item["unit_price"], 2) ?> × <?= (int)$item["quantity"] ?>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-jollibee">
                                                ₱<?= number_format((float)$item["subtotal"], 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-box-open fs-2"></i>
                                    <p class="mb-0 mt-2">No order items found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card customer-success-card">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-info-circle text-jollibee me-2"></i>Order Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <div class="small text-muted mb-1">Order Type</div>
                                    <div class="fw-semibold"><?= e($order["order_type"]) ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="small text-muted mb-1">Order Date</div>
                                    <div class="fw-semibold"><?= date("M d, Y h:i A", strtotime($order["created_at"])) ?></div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-muted mb-1"><?= $order["order_type"] === "Pickup" ? "Pickup Location" : "Delivery Address" ?></div>
                                    <div class="fw-semibold"><?= nl2br(e($order["delivery_address"])) ?></div>
                                </div>
                                <?php if (!empty($order["customer_notes"])): ?>
                                    <div class="col-12">
                                        <div class="small text-muted mb-1">Order Notes</div>
                                        <div><?= nl2br(e($order["customer_notes"])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card customer-success-card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-clock-history text-jollibee me-2"></i>Order Status
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <span class="badge <?= $statusClass ?> fs-6 px-3 py-2"><?= e($order["order_status"]) ?></span>
                            <p class="text-muted small mt-3 mb-0">Your order status will be updated by the restaurant staff.</p>
                        </div>
                    </div>

                    <div class="card customer-success-card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-receipt text-jollibee me-2"></i>Order Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="customer-summary-row">
                                <span>Payment Method</span>
                                <strong><?= e($order["payment_method"]) ?></strong>
                            </div>
                            <div class="customer-summary-row">
                                <span>Payment Status</span>
                                <span class="badge bg-light text-dark border"><?= e($order["payment_status"]) ?></span>
                            </div>

                            <?php if ($order["payment_method"] === "Cash" && $order["cash_received"] !== null): ?>
                                <div class="customer-summary-row">
                                    <span>Cash Received</span>
                                    <strong>₱<?= number_format((float)$order["cash_received"], 2) ?></strong>
                                </div>
                                <div class="customer-summary-row">
                                    <span>Change</span>
                                    <strong>₱<?= number_format((float)$order["change_amount"], 2) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="customer-summary-row pt-3">
                                <strong>Total</strong>
                                <strong class="customer-total">₱<?= number_format((float)$order["total_amount"], 2) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="orders.php" class="btn btn-jollibee btn-lg">
                            <i class="bi bi-truck me-2"></i>Track My Order
                        </a>
                        <a href="menu.php" class="btn btn-outline-secondary">
                            <i class="bi bi-utensils me-2"></i>Continue Shopping
                        </a>
                        <a href="dashboard.php" class="btn btn-link text-decoration-none">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
