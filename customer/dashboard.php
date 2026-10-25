<?php
require_once "../includes/auth.php";
requireRole("customer");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | Jollibee Central Plaza</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-jollibee shadow-sm">
    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="dashboard.php">
            Jollibee Central Plaza
        </a>

        <div class="d-flex align-items-center">

            <span class="text-white me-3">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($_SESSION['first_name']); ?>
            </span>

            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>

        </div>
    </div>
</nav>


<div class="container-fluid">

    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">

            <div class="text-center mb-4">
                <div class="bg-jollibee text-white rounded-circle
                            d-inline-flex align-items-center justify-content-center"
                     style="width:60px;height:60px;">
                    <i class="bi bi-person fs-3"></i>
                </div>

                <h6 class="mt-2 mb-0">
                    <?= htmlspecialchars($_SESSION['first_name']); ?>
                </h6>

                <small class="text-muted">Customer</small>
            </div>

            <div class="list-group list-group-flush">

                <a href="dashboard.php"
                   class="list-group-item list-group-item-action active">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>

                <a href="menu.php"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-menu-button-wide me-2"></i>
                    Browse Menu
                </a>

                <a href="cart.php"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-cart3 me-2"></i>
                    My Cart
                </a>

                <a href="orders.php"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-receipt me-2"></i>
                    My Orders
                </a>

                <a href="history.php"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-clock-history me-2"></i>
                    Order History
                </a>

            </div>

        </aside>


        <!-- MAIN CONTENT -->
        <main class="col-md-9 col-lg-10 p-4">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h2 class="fw-bold mb-1">
                        Welcome,
                        <?= htmlspecialchars($_SESSION['first_name']); ?>!
                    </h2>

                    <p class="text-muted mb-0">
                        Order your favorite meals from Jollibee Central Plaza.
                    </p>
                </div>

                <a href="menu.php" class="btn btn-jollibee">
                    <i class="bi bi-plus-circle me-1"></i>
                    Order Now
                </a>

            </div>


            <!-- STATISTICS -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>
                                    <p class="text-muted mb-1">
                                        Active Orders
                                    </p>

                                    <h3 class="fw-bold mb-0">
                                        0
                                    </h3>
                                </div>

                                <div class="bg-danger-subtle text-danger
                                            rounded-circle p-3">
                                    <i class="bi bi-bag-check fs-4"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>
                                    <p class="text-muted mb-1">
                                        Completed Orders
                                    </p>

                                    <h3 class="fw-bold mb-0">
                                        0
                                    </h3>
                                </div>

                                <div class="bg-success-subtle text-success
                                            rounded-circle p-3">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>
                                    <p class="text-muted mb-1">
                                        Total Orders
                                    </p>

                                    <h3 class="fw-bold mb-0">
                                        0
                                    </h3>
                                </div>

                                <div class="bg-warning-subtle text-warning
                                            rounded-circle p-3">
                                    <i class="bi bi-receipt fs-4"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- QUICK ACTIONS -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-3">
                        Quick Actions
                    </h5>

                    <div class="row g-3">

                        <div class="col-md-4">

                            <a href="menu.php"
                               class="btn btn-outline-danger w-100 py-3">

                                <i class="bi bi-grid fs-4 d-block mb-1"></i>

                                Browse Menu

                            </a>

                        </div>

                        <div class="col-md-4">

                            <a href="cart.php"
                               class="btn btn-outline-danger w-100 py-3">

                                <i class="bi bi-cart3 fs-4 d-block mb-1"></i>

                                View Cart

                            </a>

                        </div>

                        <div class="col-md-4">

                            <a href="orders.php"
                               class="btn btn-outline-danger w-100 py-3">

                                <i class="bi bi-receipt fs-4 d-block mb-1"></i>

                                Track Order

                            </a>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RECENT ORDERS -->
            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between
                                align-items-center mb-3">

                        <h5 class="fw-bold mb-0">
                            Recent Orders
                        </h5>

                        <a href="history.php"
                           class="text-danger text-decoration-none">
                            View All
                        </a>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">

                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td colspan="5"
                                        class="text-center text-muted py-4">

                                        <i class="bi bi-receipt fs-2 d-block mb-2"></i>

                                        No orders yet.

                                        <br>

                                        <a href="menu.php"
                                           class="text-danger">
                                            Start your first order
                                        </a>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>