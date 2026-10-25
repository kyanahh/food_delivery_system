<?php
require_once "../includes/auth.php";
requireRole("admin");
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Jollibee Central Plaza</title>

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
            <span class="fw-normal">| Admin</span>

        </a>


        <div class="d-flex align-items-center">

            <span class="text-white me-3">

                <i class="bi bi-person-circle"></i>

                <?= htmlspecialchars($_SESSION['first_name']); ?>

            </span>


            <a href="../logout.php"
               class="btn btn-light btn-sm">

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

                    <i class="bi bi-shield-lock fs-3"></i>

                </div>


                <h6 class="mt-2 mb-0">

                    <?= htmlspecialchars($_SESSION['first_name']); ?>

                </h6>


                <small class="text-muted">
                    Administrator
                </small>

            </div>


            <div class="list-group list-group-flush">


                <a href="dashboard.php"
                   class="list-group-item list-group-item-action active">

                    <i class="bi bi-speedometer2 me-2"></i>

                    Dashboard

                </a>


                <a href="users.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-people me-2"></i>

                    User Management

                </a>


                <a href="menu.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-menu-button-wide me-2"></i>

                    Menu Management

                </a>


                <a href="orders.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-receipt me-2"></i>

                    Orders

                </a>


                <a href="sales.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-bar-chart me-2"></i>

                    Sales Reports

                </a>


                <a href="forecast.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-graph-up-arrow me-2"></i>

                    Demand Forecasting

                </a>


                <a href="settings.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-gear me-2"></i>

                    System Settings

                </a>


            </div>

        </aside>


        <!-- MAIN CONTENT -->
        <main class="col-md-9 col-lg-10 p-4">


            <!-- HEADER -->
            <div class="mb-4">

                <h2 class="fw-bold mb-1">
                    Admin Dashboard
                </h2>

                <p class="text-muted mb-0">

                    Monitor the Jollibee Central Plaza
                    food delivery system.

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
                                        Today's Sales
                                    </p>

                                    <h3 class="fw-bold">
                                        ₱0.00
                                    </h3>

                                </div>


                                <div class="bg-success-subtle text-success
                                            rounded-circle p-3">

                                    <i class="bi bi-cash-stack fs-4"></i>

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
                                        Customers
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>

                                </div>


                                <div class="bg-info-subtle text-info
                                            rounded-circle p-3">

                                    <i class="bi bi-people fs-4"></i>

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
                                        Active Riders
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>

                                </div>


                                <div class="bg-warning-subtle text-warning
                                            rounded-circle p-3">

                                    <i class="bi bi-bicycle fs-4"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


            </div>


            <!-- FORECAST CARD -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">


                    <div class="d-flex justify-content-between
                                align-items-center">


                        <div>

                            <h5 class="fw-bold mb-1">

                                Demand Forecasting

                            </h5>


                            <p class="text-muted mb-0">

                                Monitor predicted food orders during
                                peak hours.

                            </p>

                        </div>


                        <a href="forecast.php"
                           class="btn btn-jollibee">

                            <i class="bi bi-graph-up-arrow me-1"></i>

                            View Forecast

                        </a>

                    </div>


                    <div class="row mt-4">


                        <div class="col-md-6">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Current Forecast Status
                                </small>


                                <h4 class="fw-bold mt-1">
                                    Pending
                                </h4>


                                <span class="badge text-bg-secondary">
                                    Awaiting Data
                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="border rounded p-3">

                                <small class="text-muted">
                                    Peak-Hour Monitoring
                                </small>


                                <h4 class="fw-bold mt-1">
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


            <!-- SYSTEM OVERVIEW -->
            <div class="row g-4">


                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <h5 class="fw-bold mb-3">
                                Recent Orders
                            </h5>


                            <div class="table-responsive">

                                <table class="table table-hover align-middle">

                                    <thead class="table-light">

                                        <tr>

                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Status</th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted py-4">

                                                No orders available.

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-4">


                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <h5 class="fw-bold mb-3">
                                System Overview
                            </h5>


                            <div class="d-flex justify-content-between
                                        border-bottom py-2">

                                <span>
                                    System Status
                                </span>

                                <span class="badge text-bg-success">
                                    Online
                                </span>

                            </div>


                            <div class="d-flex justify-content-between
                                        border-bottom py-2">

                                <span>
                                    Database
                                </span>

                                <span class="badge text-bg-success">
                                    Connected
                                </span>

                            </div>


                            <div class="d-flex justify-content-between
                                        py-2">

                                <span>
                                    Forecasting
                                </span>

                                <span class="badge text-bg-warning">
                                    Pending Data
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
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>