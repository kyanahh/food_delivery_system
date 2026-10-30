<?php
require_once "../includes/auth.php";
requireRole("customer");
require_once "../config/database.php";

$userId = (int) $_SESSION["user_id"];
$firstName = $_SESSION["first_name"] ?? "Customer";
$cartCount = array_sum($_SESSION["cart"] ?? []);

$activeStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE user_id = ?
      AND order_status IN ('Pending','Confirmed','Preparing','Ready for Delivery','Out for Delivery')
");
$activeStmt->execute([$userId]);
$activeOrders = (int) $activeStmt->fetchColumn();

$completedStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE user_id = ?
      AND order_status = 'Completed'
");
$completedStmt->execute([$userId]);
$completedOrders = (int) $completedStmt->fetchColumn();

$totalStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE user_id = ?
");
$totalStmt->execute([$userId]);
$totalOrders = (int) $totalStmt->fetchColumn();

$recentStmt = $pdo->prepare("
    SELECT order_id, order_number, total_amount, order_status, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$recentStmt->execute([$userId]);
$recentOrders = $recentStmt->fetchAll();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function statusClass($status) {
    switch ($status) {
        case "Pending": return "bg-warning text-dark";
        case "Confirmed": return "bg-primary";
        case "Preparing": return "bg-info text-dark";
        case "Ready for Delivery": return "bg-success";
        case "Out for Delivery": return "bg-primary";
        case "Completed": return "bg-success";
        case "Cancelled": return "bg-danger";
        default: return "bg-secondary";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body class="customer-page">

<nav class="navbar navbar-dark customer-topbar shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
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

        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="cart.php" class="btn btn-light btn-sm position-relative">
                <i class="bi bi-cart3 me-1"></i>Cart
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
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
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="menu.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-grid me-2"></i>Browse Menu
                </a>
                <a href="cart.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-cart3 me-2"></i>My Cart
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-receipt me-2"></i>My Orders
                </a>
                <a href="order-history.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-clock-history me-2"></i>Order History
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </aside>

        <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Welcome, <?= e($firstName) ?>!</h2>
                    <p class="text-muted mb-0">Order your favorite meals from Jollibee Central Plaza.</p>
                </div>
                <a href="menu.php" class="btn btn-jollibee">
                    <i class="bi bi-plus-circle me-1"></i>Order Now
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card customer-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Active Orders</p>
                                <h3 class="fw-bold mb-0"><?= $activeOrders ?></h3>
                            </div>
                            <div class="customer-stat-icon rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center">
                                <i class="bi bi-bag-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card customer-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Completed Orders</p>
                                <h3 class="fw-bold mb-0"><?= $completedOrders ?></h3>
                            </div>
                            <div class="customer-stat-icon rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card customer-card h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Orders</p>
                                <h3 class="fw-bold mb-0"><?= $totalOrders ?></h3>
                            </div>
                            <div class="customer-stat-icon rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                                <i class="bi bi-receipt fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card customer-card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="menu.php" class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-grid fs-4 d-block mb-1"></i>Browse Menu
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="cart.php" class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-cart3 fs-4 d-block mb-1"></i>View Cart
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="orders.php" class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-receipt fs-4 d-block mb-1"></i>Track Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card customer-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Recent Orders</h5>
                        <a href="order-history.php" class="text-danger text-decoration-none">View All</a>
                    </div>

                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                            No orders yet.
                            <div class="mt-2">
                                <a href="menu.php" class="text-danger">Start your first order</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($order["order_number"]) ?></td>
                                        <td><?= date("M d, Y h:i A", strtotime($order["created_at"])) ?></td>
                                        <td>₱<?= number_format((float)$order["total_amount"], 2) ?></td>
                                        <td><span class="badge <?= statusClass($order["order_status"]) ?>"><?= e($order["order_status"]) ?></span></td>
                                        <td class="text-end">
                                            <a href="order-success.php?id=<?= (int)$order["order_id"] ?>" class="btn btn-sm btn-outline-danger">
                                                View
                                            </a>
                                        </td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
