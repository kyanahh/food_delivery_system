<?php
require_once "../includes/auth.php";
requireRole("customer");
require_once "../config/database.php";

$cart = $_SESSION["cart"] ?? [];
$cartItems = [];
$subtotal = 0;

if (!empty($cart)) {
    $foodIds = array_keys($cart);
    $placeholders = implode(",", array_fill(0, count($foodIds), "?"));

    $stmt = $pdo->prepare("
        SELECT food_id, food_name, description, price, image, status
        FROM food_items
        WHERE food_id IN ($placeholders)
    ");
    $stmt->execute($foodIds);
    $foods = $stmt->fetchAll();

    foreach ($foods as $food) {
        $foodId = (int)$food["food_id"];
        $quantity = (int)($cart[$foodId] ?? 0);

        if ($quantity < 1) {
            continue;
        }

        $itemSubtotal = (float)$food["price"] * $quantity;
        $subtotal += $itemSubtotal;
        $food["quantity"] = $quantity;
        $food["item_subtotal"] = $itemSubtotal;
        $cartItems[] = $food;
    }
}

$success = $_GET["success"] ?? "";
$firstName = $_SESSION["first_name"] ?? "Customer";
$cartCount = array_sum($cart);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Jollibee Central Plaza</title>
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
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark"><?= $cartCount ?></span>
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
                <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                <a href="menu.php" class="list-group-item list-group-item-action"><i class="bi bi-grid me-2"></i>Browse Menu</a>
                <a href="cart.php" class="list-group-item list-group-item-action active"><i class="bi bi-cart3 me-2"></i>My Cart</a>
                <a href="orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>My Orders</a>
                <a href="order-history.php" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Order History</a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </aside>

        <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">My Cart</h2>
                    <p class="text-muted mb-0">Review your selected food items before checkout.</p>
                </div>
                <a href="menu.php" class="btn btn-outline-danger">
                    <i class="bi bi-grid me-1"></i>Continue Shopping
                </a>
            </div>

            <?php if ($success === "added"): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Food item was added to your cart.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="card customer-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                        <h4 class="fw-bold mt-3">Your cart is empty</h4>
                        <p class="text-muted">Add some delicious food from the menu.</p>
                        <a href="menu.php" class="btn btn-jollibee">
                            <i class="bi bi-grid me-1"></i>Browse Menu
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card customer-card">
                            <div class="card-header bg-white py-3">
                                <h5 class="fw-bold mb-0">
                                    <i class="bi bi-cart3 me-2 text-jollibee"></i>Cart Items
                                </h5>
                            </div>

                            <div class="card-body p-0">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="p-3 customer-order-item">
                                        <div class="row align-items-center g-3">
                                            <div class="col-auto">
                                                <?php if (!empty($item["image"])): ?>
                                                    <img src="../assets/uploads/menu/<?= e($item["image"]) ?>"
                                                         class="customer-item-image rounded"
                                                         alt="<?= e($item["food_name"]) ?>"
                                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                                    <div class="customer-item-placeholder rounded d-none align-items-center justify-content-center d-flex">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="customer-item-placeholder rounded d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col">
                                                <h6 class="fw-bold mb-1"><?= e($item["food_name"]) ?></h6>
                                                <small class="text-muted">₱<?= number_format((float)$item["price"], 2) ?> each</small>
                                            </div>

                                            <div class="col-12 col-md-auto">
                                                <form action="update-cart.php" method="POST" class="d-flex align-items-center">
                                                    <input type="hidden" name="food_id" value="<?= (int)$item["food_id"] ?>">
                                                    <input type="number" name="quantity" value="<?= (int)$item["quantity"] ?>" min="1" class="form-control form-control-sm" style="width:80px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                                        Update
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="col-auto text-end">
                                                <strong class="text-jollibee">₱<?= number_format((float)$item["item_subtotal"], 2) ?></strong>
                                            </div>

                                            <div class="col-auto">
                                                <form action="remove-from-cart.php" method="POST">
                                                    <input type="hidden" name="food_id" value="<?= (int)$item["food_id"] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card customer-card customer-sticky">
                            <div class="card-header bg-white py-3">
                                <h5 class="fw-bold mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span>₱<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Delivery Fee</span>
                                    <span class="text-muted">Calculated at checkout</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Estimated Total</strong>
                                    <strong class="text-jollibee">₱<?= number_format($subtotal, 2) ?></strong>
                                </div>

                                <a href="checkout.php" class="btn btn-jollibee w-100">
                                    <i class="bi bi-credit-card me-1"></i>Proceed to Checkout
                                </a>
                                <a href="menu.php" class="btn btn-outline-secondary w-100 mt-2">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
