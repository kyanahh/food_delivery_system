<?php

require_once "../includes/auth.php";
requireRole("admin");

require_once "../config/database.php";


// Get categories
$categoryQuery = $pdo->query("
    SELECT *
    FROM food_categories
    ORDER BY category_name ASC
");

$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);


// Get food items
$foodQuery = $pdo->query("
    SELECT
        food_items.*,
        food_categories.category_name
    FROM food_items
    INNER JOIN food_categories
        ON food_items.category_id = food_categories.category_id
    ORDER BY food_items.created_at DESC
");

$foods = $foodQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>

    :root {
        --jollibee-red: #d71920;
        --jollibee-dark-red: #b51218;
    }
    body { background: #f8f9fa; }
    .bg-jollibee { background-color: var(--jollibee-red) !important; }
    .btn-jollibee {
        background-color: var(--jollibee-red);
        border-color: var(--jollibee-red);
        color: #fff;
    }
    .btn-jollibee:hover {
        background-color: var(--jollibee-dark-red);
        border-color: var(--jollibee-dark-red);
        color: #fff;
    }
    .text-jollibee { color: var(--jollibee-red) !important; }
    .sidebar { min-height: calc(100vh - 56px); }
    .sidebar .nav-link {
        color: #343a40;
        border-radius: .5rem;
        margin-bottom: .25rem;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background: var(--jollibee-red);
        color: #fff;
    }
    .summary-card {
        border: 0;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    }
    .summary-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(215, 25, 32, .1);
        color: var(--jollibee-red);
        font-size: 1.35rem;
    }
    .table th { white-space: nowrap; }

    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-jollibee shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            Jollibee Central Plaza <span class="fw-normal">| Admin</span>
        </a>
        <div class="d-flex align-items-center gap-3 text-white">
            <span>
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </span>
            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
<aside class="col-md-3 col-lg-2 bg-white border-end sidebar p-3">
        <div class="text-center py-3 mb-3 border-bottom">
            <div class="text-jollibee fs-1">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="fw-bold">
                <?= htmlspecialchars(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </div>
            <small class="text-muted">Administrator</small>
        </div>
        <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a class="nav-link active" href="menu.php">
                    <i class="bi bi-grid me-2"></i>Menu Management
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-receipt me-2"></i>Orders
                </a>
                <a class="nav-link" href="sales.php">
                    <i class="bi bi-bar-chart-line me-2"></i>Sales Reports
                </a>
                <a class="nav-link" href="forecast.php">
                    <i class="bi bi-graph-up-arrow me-2"></i>Demand Forecasting
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear me-2"></i>System Settings
                </a>
        </nav>
    </aside>
        <main class="col-md-9 col-lg-10 p-4">



            <!-- PAGE HEADER -->

            <div class="d-flex
                        justify-content-between
                        align-items-center
                        mb-4">


                <div>

                    <h2 class="fw-bold mb-1">

                        Menu Management

                    </h2>


                    <p class="text-muted mb-0">

                        Manage food items and menu
                        availability for Jollibee Central Plaza.

                    </p>

                </div>


                <a href="add-food.php"
                   class="btn btn-jollibee">

                    <i class="bi bi-plus-circle me-1"></i>

                    Add Food Item

                </a>


            </div>



            <!-- CATEGORY CARDS -->

            <div class="row g-3 mb-4">

                <?php foreach ($categories as $category): ?>

                    <div class="col-md-4 col-lg-2">

                        <div class="card
                                    border-0
                                    shadow-sm
                                    h-100">

                            <div class="card-body
                                        text-center">

                                <div
                                    class="bg-danger-subtle
                                           text-danger
                                           rounded-circle
                                           d-inline-flex
                                           align-items-center
                                           justify-content-center
                                           mb-2"

                                    style="
                                        width:45px;
                                        height:45px;
                                    "
                                >

                                    <i class="bi bi-grid"></i>

                                </div>


                                <h6 class="fw-bold">

                                    <?= htmlspecialchars(
                                        $category['category_name']
                                    ); ?>

                                </h6>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>



            <!-- MENU TABLE -->

            <div class="card
                        border-0
                        shadow-sm">


                <div class="card-body">


                    <div class="d-flex
                                justify-content-between
                                align-items-center
                                mb-3">


                        <h5 class="fw-bold mb-0">

                            Food Menu

                        </h5>


                        <span class="badge
                                     bg-danger">

                            <?= count($foods); ?> Items

                        </span>


                    </div>



                    <div class="table-responsive">


                        <table
                            class="table
                                   table-hover
                                   align-middle"
                        >

                            <thead class="table-light">

                                <tr>

                                    <th>ID</th>

                                    <th>Image</th>

                                    <th>Food</th>

                                    <th>Category</th>

                                    <th>Price</th>

                                    <th>Status</th>

                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (!empty($foods)): ?>


                                <?php foreach ($foods as $food): ?>


                                    <tr>


                                        <td>

                                            #<?= $food['food_id']; ?>

                                        </td>

                                        <td>
                                            <?php if (!empty($food['image'])): ?>
                                                <img
                                                    src="../assets/uploads/menu/<?= htmlspecialchars($food['image']); ?>"
                                                    alt="<?= htmlspecialchars($food['food_name']); ?>"
                                                    class="rounded border"
                                                    style="
                                                        width: 70px;
                                                        height: 70px;
                                                        object-fit: cover;
                                                    "
                                                >
                                            <?php else: ?>
                                                <div
                                                    class="bg-light border rounded d-flex align-items-center justify-content-center"
                                                    style="
                                                        width: 70px;
                                                        height: 70px;
                                                    "
                                                >
                                                    <i class="bi bi-image text-muted fs-4"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>


                                        <td>

                                            <div class="fw-semibold">

                                                <?= htmlspecialchars(
                                                    $food['food_name']
                                                ); ?>

                                            </div>


                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    $food['description'] ?? ''
                                                ); ?>

                                            </small>

                                        </td>


                                        <td>

                                            <span
                                                class="badge
                                                       text-bg-light
                                                       border"
                                            >

                                                <?= htmlspecialchars(
                                                    $food['category_name']
                                                ); ?>

                                            </span>

                                        </td>


                                        <td class="fw-bold">

                                            ₱<?= number_format(
                                                $food['price'],
                                                2
                                            ); ?>

                                        </td>


                                        <td>


                                            <?php if (
                                                $food['status']
                                                === 'available'
                                            ): ?>

                                                <span
                                                    class="badge
                                                           text-bg-success"
                                                >

                                                    Available

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="badge
                                                           text-bg-secondary"
                                                >

                                                    Unavailable

                                                </span>

                                            <?php endif; ?>


                                        </td>


                                        <td>


                                            <a
                                                href="edit-food.php?id=
                                                <?= $food['food_id']; ?>"
                                                class="btn
                                                       btn-outline-danger
                                                       btn-sm"
                                            >

                                                <i
                                                    class="bi
                                                          bi-pencil"
                                                ></i>

                                                Edit

                                            </a>


                                        </td>


                                    </tr>


                               <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center
                                               text-muted
                                               py-5"
                                    >

                                        <i
                                            class="bi
                                                  bi-menu-button-wide
                                                  fs-1
                                                  d-block
                                                  mb-3"
                                        ></i>

                                        No food items found.

                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


        
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
