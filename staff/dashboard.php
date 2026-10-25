<?php
require_once "../includes/auth.php";
requireRole("staff");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard | Jollibee Central Plaza</title>

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

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-jollibee shadow-sm">

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

                    <i class="bi bi-person-badge fs-3"></i>

                </div>

                <h6 class="mt-2 mb-0">
                    <?= htmlspecialchars($_SESSION['first_name']); ?>
                </h6>

                <small class="text-muted">
                    Restaurant Staff
                </small>

            </div>


            <div class="list-group list-group-flush">

                <a href="dashboard.php"
                   class="list-group-item list-group-item-action active">

                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard

                </a>

                <a href="orders.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-receipt me-2"></i>
                    Incoming Orders

                </a>

                <a href="preparation.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-fire me-2"></i>
                    Food Preparation

                </a>

                <a href="menu.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-menu-button-wide me-2"></i>
                    Menu

                </a>

                <a href="forecast.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Demand Forecast

                </a>

                <a href="sales.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-bar-chart me-2"></i>
                    Sales

                </a>

            </div>

        </aside>


        <!-- MAIN CONTENT -->
        <main class="col-md-9 col-lg-10 p-4">

            <!-- HEADER -->
            <div class="mb-4">

                <h2 class="fw-bold mb-1">
                    Staff Dashboard
                </h2>

                <p class="text-muted mb-0">
                    Manage orders and food preparation for Jollibee Central Plaza.
                </p>

            </div>


            <!-- STATISTICS -->
            <div class="row g-3 mb-4">

                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>
                                    <p class="text-muted mb-1">
                                        Today's Orders
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>
                                </div>

                                <div class="bg-danger-subtle text-danger
                                            rounded-circle p-3">

                                    <i class="bi bi-receipt fs-4"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Pending
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>

                                </div>

                                <div class="bg-warning-subtle text-warning
                                            rounded-circle p-3">

                                    <i class="bi bi-hourglass-split fs-4"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Preparing
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>

                                </div>

                                <div class="bg-info-subtle text-info
                                            rounded-circle p-3">

                                    <i class="bi bi-fire fs-4"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Ready
                                    </p>

                                    <h3 class="fw-bold">
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

            </div>


            <!-- DEMAND FORECAST -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="fw-bold mb-1">
                                Peak-Hour Demand Forecast
                            </h5>

                            <p class="text-muted mb-0">
                                Expected order demand during peak hours.
                            </p>

                        </div>

                        <a href="forecast.php"
                           class="btn btn-jollibee btn-sm">

                            View Forecast

                        </a>

                    </div>


                    <div class="row mt-4">

                        <div class="col-md-4">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Lunch Peak
                                </small>

                                <h4 class="fw-bold text-jollibee mt-1">
                                    11:00 AM – 1:00 PM
                                </h4>

                                <span class="badge text-bg-warning">
                                    Forecast Pending
                                </span>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Expected Demand
                                </small>

                                <h4 class="fw-bold mt-1">
                                    0 Orders
                                </h4>

                                <span class="badge text-bg-secondary">
                                    No Data
                                </span>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Preparation Status
                                </small>

                                <h4 class="fw-bold mt-1">
                                    Normal
                                </h4>

                                <span class="badge text-bg-success">
                                    Ready
                                </span>

                            </div>

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

                        <a href="orders.php"
                           class="text-danger text-decoration-none">

                            View All

                        </a>

                    </div>


                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Order Time</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td colspan="6"
                                        class="text-center text-muted py-4">

                                        <i class="bi bi-receipt fs-2 d-block mb-2"></i>

                                        No orders available.

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