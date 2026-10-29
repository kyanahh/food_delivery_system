<?php

require_once "config/database.php";
require_once "includes/auth.php";

$isLoggedIn = isset($_SESSION["user_id"]);
$firstName = $_SESSION["first_name"] ?? "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Jollibee Plaza Central | Online Food Delivery</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/public.css">
</head>

<body class="jp-page">

<nav class="navbar navbar-expand-lg jp-navbar sticky-top">
    <div class="container">

        <a href="index.php" class="jp-brand d-flex align-items-center gap-2">
            <span class="jp-brand-mark d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-shop"></i>
            </span>

            <span>
                Jollibee Plaza Central
            </span>
        </a>

        <button
            class="navbar-toggler border border-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar">
            <i class="bi bi-list text-white fs-3"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a href="#menu" class="nav-link jp-nav-link">
                        Menu
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#features" class="nav-link jp-nav-link">
                        Services
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#how-it-works" class="nav-link jp-nav-link">
                        How It Works
                    </a>
                </li>

                <?php if ($isLoggedIn): ?>

                    <li class="nav-item ms-lg-2">
                        <a href="customer/menu.php" class="btn jp-order-btn">
                            Order Now
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item ms-lg-2">
                        <a href="login.php" class="nav-link jp-nav-link">
                            Login
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a href="register.php" class="btn jp-order-btn">
                            Order Now
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>

<div class="jp-yellow-line"></div>

<section class="jp-hero">
    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-7">

                <div class="jp-kicker mb-3">
                    <i class="bi bi-geo-alt-fill me-1"></i>
                    Jollibee Plaza Central
                </div>

                <h1 class="jp-hero-title mb-4">
                    Your favorites,
                    <span>made easy.</span>
                </h1>

                <p class="jp-hero-text mb-4">
                    Order your favorite meals from Jollibee Plaza Central
                    through our convenient online food delivery system.
                    Choose delivery or pickup and keep track of your order.
                </p>

                <div class="d-flex flex-column flex-sm-row gap-2">

                    <?php if ($isLoggedIn): ?>

                        <a href="customer/menu.php" class="btn jp-primary-btn btn-lg">
                            <i class="bi bi-bag-fill me-2"></i>
                            Order Now
                        </a>

                        <a href="customer/orders.php" class="btn jp-outline-btn btn-lg">
                            <i class="bi bi-receipt me-2"></i>
                            My Orders
                        </a>

                    <?php else: ?>

                        <a href="register.php" class="btn jp-primary-btn btn-lg">
                            <i class="bi bi-bag-fill me-2"></i>
                            Start Your Order
                        </a>

                        <a href="login.php" class="btn jp-outline-btn btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Login
                        </a>

                    <?php endif; ?>

                </div>

                <div class="d-flex flex-wrap gap-4 mt-4 small text-muted">
                    <span>
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Easy ordering
                    </span>

                    <span>
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Delivery or pickup
                    </span>

                    <span>
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Order tracking
                    </span>
                </div>

            </div>

            <div class="col-lg-5">

                <div class="jp-hero-card">

                    <div class="jp-hero-food d-flex align-items-center justify-content-center text-center p-4">

                        <div>
                            <div class="jp-hero-food-icon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4">
                                <i class="bi bi-basket2-fill display-4"></i>
                            </div>

                            <div class="jp-hero-food-title">
                                Ready when you are!
                            </div>

                            <p class="mb-0 text-white-50">
                                Pick your favorites and place your order.
                            </p>
                        </div>

                    </div>

                    <div class="p-4">

                        <div class="row g-2">

                            <div class="col-4 text-center">
                                <i class="bi bi-menu-button-wide-fill fs-4"></i>
                                <div class="small fw-bold mt-1">Browse</div>
                            </div>

                            <div class="col-4 text-center">
                                <i class="bi bi-cart-check-fill fs-4"></i>
                                <div class="small fw-bold mt-1">Order</div>
                            </div>

                            <div class="col-4 text-center">
                                <i class="bi bi-truck fs-4"></i>
                                <div class="small fw-bold mt-1">Receive</div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<div id="menu" class="jp-category-strip">
    <div class="container overflow-auto">
        <div class="d-flex align-items-center">

            <a href="customer/menu.php" class="jp-category active">
                All Menu
            </a>

            <a href="customer/menu.php" class="jp-category">
                Chicken
            </a>

            <a href="customer/menu.php" class="jp-category">
                Burgers
            </a>

            <a href="customer/menu.php" class="jp-category">
                Breakfast
            </a>

            <a href="customer/menu.php" class="jp-category">
                Jolly Spaghetti
            </a>

            <a href="customer/menu.php" class="jp-category">
                Drinks
            </a>

            <a href="customer/menu.php" class="jp-category">
                Desserts
            </a>

        </div>
    </div>
</div>

<section id="features" class="jp-section">
    <div class="container">

        <div class="text-center mb-5">
            <div class="jp-kicker">Jollibee Plaza Central</div>
            <h2 class="jp-section-title mt-2">
                A simple way to order
            </h2>
            <p class="jp-section-subtitle mb-0">
                Enjoy a convenient ordering experience from one place.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="jp-feature-card p-4">
                    <div class="jp-feature-icon rounded-3 d-flex align-items-center justify-content-center mb-4">
                        <i class="bi bi-grid-3x3-gap-fill fs-4"></i>
                    </div>

                    <h5 class="fw-bold">Browse the Menu</h5>

                    <p class="text-muted mb-0">
                        Explore available food items and choose your favorites.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="jp-feature-card p-4">
                    <div class="jp-feature-icon rounded-3 d-flex align-items-center justify-content-center mb-4">
                        <i class="bi bi-cart-check-fill fs-4"></i>
                    </div>

                    <h5 class="fw-bold">Place Your Order</h5>

                    <p class="text-muted mb-0">
                        Review your cart, choose delivery or pickup, and check out.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="jp-feature-card p-4">
                    <div class="jp-feature-icon rounded-3 d-flex align-items-center justify-content-center mb-4">
                        <i class="bi bi-receipt-cutoff fs-4"></i>
                    </div>

                    <h5 class="fw-bold">Track Your Order</h5>

                    <p class="text-muted mb-0">
                        View your active orders and order history from your account.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<section id="how-it-works" class="jp-section bg-light">
    <div class="container">

        <div class="text-center mb-5">
            <div class="jp-kicker">How It Works</div>
            <h2 class="jp-section-title mt-2">
                Three simple steps
            </h2>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="text-center px-3">
                    <div class="jp-step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                        1
                    </div>

                    <h5 class="fw-bold">Choose</h5>

                    <p class="text-muted mb-0">
                        Browse the menu and select the food you want.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center px-3">
                    <div class="jp-step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                        2
                    </div>

                    <h5 class="fw-bold">Checkout</h5>

                    <p class="text-muted mb-0">
                        Enter your order information and select your payment method.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-center px-3">
                    <div class="jp-step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                        3
                    </div>

                    <h5 class="fw-bold">Enjoy</h5>

                    <p class="text-muted mb-0">
                        Receive your order or pick it up when it is ready.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<section class="jp-cta py-5">
    <div class="container">

        <div class="row align-items-center g-4">

            <div class="col-lg-8">
                <h2 class="fw-bold mb-2">
                    Ready to order from Jollibee Plaza Central?
                </h2>

                <p class="mb-0 text-white-50">
                    Get started and enjoy a convenient online ordering experience.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">

                <?php if ($isLoggedIn): ?>

                    <a href="customer/menu.php" class="btn jp-primary-btn btn-lg">
                        Order Now
                    </a>

                <?php else: ?>

                    <a href="register.php" class="btn jp-primary-btn btn-lg">
                        Create Account
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>
</section>

<footer class="jp-footer py-4">
    <div class="container">

        <div class="row align-items-center g-3">

            <div class="col-md-7">
                <div class="jp-footer-brand">
                    <i class="bi bi-shop me-2"></i>
                    Jollibee Plaza Central
                </div>

                <small>
                    Online Food Delivery System
                </small>
            </div>

            <div class="col-md-5 text-md-end">
                <small>
                    &copy; <?= date("Y") ?> Jollibee Plaza Central
                </small>
            </div>

        </div>

    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
