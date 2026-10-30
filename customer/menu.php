<?php
require_once "../includes/auth.php";
requireRole("customer");
require_once "../config/database.php";

$categoryStmt = $pdo->query("
    SELECT *
    FROM food_categories
    WHERE status = 'active'
    ORDER BY category_name ASC
");
$categories = $categoryStmt->fetchAll();

$selectedCategory = $_GET["category"] ?? "";

if ($selectedCategory !== "" && is_numeric($selectedCategory)) {
    $foodStmt = $pdo->prepare("
        SELECT food_items.*, food_categories.category_name
        FROM food_items
        INNER JOIN food_categories
            ON food_items.category_id = food_categories.category_id
        WHERE food_items.status = 'available'
          AND food_items.category_id = ?
          AND food_categories.status = 'active'
        ORDER BY food_items.food_name ASC
    ");
    $foodStmt->execute([(int)$selectedCategory]);
} else {
    $foodStmt = $pdo->query("
        SELECT food_items.*, food_categories.category_name
        FROM food_items
        INNER JOIN food_categories
            ON food_items.category_id = food_categories.category_id
        WHERE food_items.status = 'available'
          AND food_categories.status = 'active'
        ORDER BY food_categories.category_name ASC, food_items.food_name ASC
    ");
}

$foodItems = $foodStmt->fetchAll();
$firstName = $_SESSION["first_name"] ?? "Customer";
$cartCount = array_sum($_SESSION["cart"] ?? []);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Menu | Jollibee Central Plaza</title>
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
                <a href="menu.php" class="list-group-item list-group-item-action active"><i class="bi bi-grid me-2"></i>Browse Menu</a>
                <a href="cart.php" class="list-group-item list-group-item-action"><i class="bi bi-cart3 me-2"></i>My Cart</a>
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
                    <h2 class="fw-bold mb-1">Browse Menu</h2>
                    <p class="text-muted mb-0">Choose your favorite meals from Jollibee Central Plaza.</p>
                </div>
                <a href="cart.php" class="btn btn-outline-danger">
                    <i class="bi bi-cart3 me-1"></i>View Cart
                </a>
            </div>

            <div class="card customer-card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="menu.php" class="btn customer-category <?= $selectedCategory === "" ? "btn-jollibee" : "btn-outline-danger" ?>">All</a>
                        <?php foreach ($categories as $category): ?>
                            <a href="menu.php?category=<?= (int)$category["category_id"] ?>"
                               class="btn customer-category <?= $selectedCategory == $category["category_id"] ? "btn-jollibee" : "btn-outline-danger" ?>">
                                <?= e($category["category_name"]) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (empty($foodItems)): ?>
                <div class="card customer-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-emoji-frown fs-1 text-muted"></i>
                        <h5 class="mt-3">No food items available.</h5>
                        <p class="text-muted mb-0">Please try another category.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($foodItems as $food): ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card customer-card customer-food-card h-100">
                                <?php if (!empty($food["image"])): ?>
                                    <img src="../assets/uploads/menu/<?= e($food["image"]) ?>"
                                         class="customer-food-image"
                                         alt="<?= e($food["food_name"]) ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                    <div class="customer-food-placeholder d-none align-items-center justify-content-center d-flex">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="customer-food-placeholder d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <small class="text-jollibee fw-semibold"><?= e($food["category_name"]) ?></small>
                                    <h5 class="card-title fw-bold mt-1 mb-2"><?= e($food["food_name"]) ?></h5>
                                    <p class="card-text text-muted small">
                                        <?= e($food["description"] ?? "") ?>
                                    </p>

                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold text-jollibee fs-5">₱<?= number_format((float)$food["price"], 2) ?></span>
                                        </div>
                                        <form action="add-to-cart.php" method="POST">
                                            <input type="hidden" name="food_id" value="<?= (int)$food["food_id"] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-jollibee w-100">
                                                <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
