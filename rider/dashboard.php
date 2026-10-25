<?php
require_once "../includes/auth.php";
requireRole("rider");
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rider Dashboard | Jollibee Central Plaza</title>

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

                    <i class="bi bi-bicycle fs-3"></i>

                </div>

                <h6 class="mt-2 mb-0">

                    <?= htmlspecialchars($_SESSION['first_name']); ?>

                </h6>

                <small class="text-muted">
                    Delivery Rider
                </small>

            </div>


            <div class="list-group list-group-flush">

                <a href="dashboard.php"
                   class="list-group-item list-group-item-action active">

                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard

                </a>


                <a href="deliveries.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-box-seam me-2"></i>
                    My Deliveries

                </a>


                <a href="history.php"
                   class="list-group-item list-group-item-action">

                    <i class="bi bi-clock-history me-2"></i>
                    Delivery History

                </a>

            </div>

        </aside>


        <!-- MAIN -->
        <main class="col-md-9 col-lg-10 p-4">


            <div class="mb-4">

                <h2 class="fw-bold mb-1">
                    Rider Dashboard
                </h2>

                <p class="text-muted mb-0">

                    Manage your assigned deliveries for Jollibee Central Plaza.

                </p>

            </div>


            <!-- STATISTICS -->
            <div class="row g-3 mb-4">


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Assigned Deliveries
                                    </p>

                                    <h3 class="fw-bold">
                                        0
                                    </h3>

                                </div>

                                <div class="bg-danger-subtle text-danger
                                            rounded-circle p-3">

                                    <i class="bi bi-box-seam fs-4"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Out for Delivery
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


                <div class="col-md-4">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-muted mb-1">
                                        Completed Today
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


            <!-- CURRENT DELIVERY -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-3">
                        Current Delivery
                    </h5>


                    <div class="text-center py-5 text-muted">

                        <i class="bi bi-bicycle fs-1 d-block mb-3"></i>

                        <h5>
                            No active delivery
                        </h5>

                        <p class="mb-0">
                            Assigned deliveries will appear here.
                        </p>

                    </div>

                </div>

            </div>


            <!-- DELIVERY LIST -->
            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="d-flex justify-content-between
                                align-items-center mb-3">

                        <h5 class="fw-bold mb-0">
                            Assigned Deliveries
                        </h5>

                        <a href="deliveries.php"
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
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Action</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td colspan="5"
                                        class="text-center text-muted py-4">

                                        No delivery assignments yet.

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