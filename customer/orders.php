<?php
require_once "../includes/auth.php";
requireRole("customer");
require_once "../config/database.php";

$userId = (int)($_SESSION['user_id'] ?? 0);
$firstName = $_SESSION['first_name'] ?? 'Customer';

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function statusClass($status) {
    return match ($status) {
        'Pending' => 'bg-warning text-dark',
        'Confirmed' => 'bg-primary',
        'Preparing' => 'bg-info text-dark',
        'Ready for Delivery' => 'bg-secondary',
        'Out for Delivery' => 'bg-dark',
        'Completed' => 'bg-success',
        'Cancelled' => 'bg-danger',
        default => 'bg-secondary'
    };
}

$sql = "SELECT order_id, order_number, total_amount, payment_method, payment_status,
               order_status, order_type, delivery_address, created_at, updated_at
        FROM orders
        WHERE user_id = :user_id
          AND order_status NOT IN ('Completed', 'Cancelled')
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $userId]);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - Food Delivery System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body class="customer-page">
<nav class="navbar customer-topbar px-3 px-lg-4">
    <div class="container-fluid px-0">
<!-- Brand -->
        <a class="customer-brand d-flex align-items-center text-decoration-none" href="dashboard.php">

            <!-- Circular Logo -->
            <div style="
                width: 55px;
                height: 55px;
                background: #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            ">
                <img 
                    src="../assets/images/logo.png" 
                    alt="Jollibee Logo"
                    style="
                        width: 35px;
                        height: 42px;
                        object-fit: contain;
                    "
                >
            </div>

            <!-- Brand Name -->
            <span class="ms-2 fw-bold">
                Jollibee Central Plaza
            </span>

        </a>        <div class="d-flex align-items-center gap-2">
            <a href="profile.php" class="btn btn-sm btn-outline-light"><i class="bi bi-person me-1"></i><span class="customer-user-name"><?= e($firstName) ?></span></a>
            <a href="../logout.php" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 customer-sidebar p-3">
            <div class="customer-profile text-center pb-3 mb-3">
                <div class="customer-avatar rounded-circle d-inline-flex align-items-center justify-content-center"><i class="bi bi-person fs-3"></i></div>
                <h6 class="mt-2 mb-0"><?= e($firstName) ?></h6>
                <small class="text-muted">Customer</small>
            </div>
            <div class="list-group customer-nav">
                <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                <a href="menu.php" class="list-group-item list-group-item-action"><i class="bi bi-grid me-2"></i>Browse Menu</a>
                <a href="cart.php" class="list-group-item list-group-item-action"><i class="bi bi-cart3 me-2"></i>My Cart</a>
                <a href="orders.php" class="list-group-item list-group-item-action active"><i class="bi bi-receipt me-2"></i>My Orders</a>
                <a href="order-history.php" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Order History</a>
                <a href="profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>My Profile</a>
            </div>
        </aside>
        <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div><h2 class="fw-bold mb-1">My Orders</h2><p class="text-muted mb-0">Track your current and ongoing orders.</p></div>
                <a href="menu.php" class="btn btn-jollibee"><i class="bi bi-plus-circle me-1"></i>Order Again</a>
            </div>

            <div class="card customer-card">
                <div class="card-body p-0">
                    <?php if (!$orders): ?>
                        <div class="text-center py-5 px-3">
                            <div class="customer-success-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-receipt fs-2"></i></div>
                            <h5 class="fw-bold">No active orders</h5>
                            <p class="text-muted">You don't have any ongoing orders right now.</p>
                            <a href="menu.php" class="btn btn-jollibee">Browse Menu</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th class="ps-4">Order</th><th>Date</th><th>Type</th><th>Total</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead>
                                <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= e($order['order_number']) ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                        <td><?= e($order['order_type']) ?></td>
                                        <td>₱<?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td><span class="badge <?= statusClass($order['order_status']) ?>"><?= e($order['order_status']) ?></span></td>
                                        <td class="text-end pe-4"><a href="order-success.php?id=<?= (int)$order['order_id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-eye me-1"></i>View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
