<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

/* =========================
   ORDER COUNTS
   ========================= */

$activeStatuses = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Ready for Delivery",
    "Ready for Pickup",
    "Out for Delivery"
];

$ph = implode(",", array_fill(0, count($activeStatuses), "?"));

$s = $pdo->prepare(
    "SELECT COUNT(*) 
     FROM orders 
     WHERE order_status IN ($ph)"
);

$s->execute($activeStatuses);
$activeOrders = (int) $s->fetchColumn();

$s = $pdo->query(
    "SELECT COUNT(*) 
     FROM orders 
     WHERE order_status = 'Pending'"
);

$pendingOrders = (int) $s->fetchColumn();

$s = $pdo->query(
    "SELECT COUNT(*) 
     FROM orders 
     WHERE order_status = 'Preparing'"
);

$preparingOrders = (int) $s->fetchColumn();

$s = $pdo->query(
    "SELECT COUNT(*) 
     FROM orders 
     WHERE DATE(created_at) = CURDATE()"
);

$todayOrders = (int) $s->fetchColumn();


/* =========================
   RECENT ORDERS
   ========================= */

$s = $pdo->query(
    "SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.payment_status,
        o.order_status,
        o.order_type,
        o.created_at,
        u.first_name,
        u.last_name
     FROM orders o
     INNER JOIN users u 
        ON u.user_id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 8"
);

$recentOrders = $s->fetchAll();


/* =========================
   STATUS CLASS
   ========================= */

function statusClass($s)
{
    return match ($s) {
        "Pending" => "staff-status-pending",
        "Confirmed" => "staff-status-confirmed",
        "Preparing" => "staff-status-preparing",
        "Ready for Delivery",
        "Ready for Pickup" => "staff-status-ready",
        "Out for Delivery" => "staff-status-out",
        "Completed" => "staff-status-completed",
        "Cancelled" => "staff-status-cancelled",
        default => "bg-light text-dark"
    };
}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Staff Dashboard | Jollibee Plaza Central</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/staff.css">

</head>

<body class="staff-page">


<!-- =========================
     TOP NAVBAR
     ========================= -->

<nav class="navbar staff-topbar">

    <div class="container-fluid px-3 px-lg-4">

        <a
            href="dashboard.php"
            class="staff-brand d-flex align-items-center gap-2"
        >
            <i class="bi bi-shop fs-5"></i>
            <span>Jollibee Plaza Central</span>
        </a>

        <div class="d-flex align-items-center gap-3 text-white">

            <span class="staff-user-name small">
                <i class="bi bi-person-circle me-1"></i>

                <?= e($_SESSION["first_name"] ?? "Staff") ?>
            </span>

            <a
                href="../logout.php"
                class="btn btn-sm btn-light"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =========================
     PAGE LAYOUT
     ========================= -->

<div class="container-fluid">

    <div class="row">


        <!-- =========================
             SIDEBAR
             ========================= -->

        <aside class="col-lg-2 p-0 staff-sidebar">

            <div class="p-3">


                <!-- Staff Profile -->

                <div class="staff-profile p-3 mb-3">

                    <div class="d-flex align-items-center gap-3">

                        <div
                            class="staff-avatar rounded-circle d-flex align-items-center justify-content-center"
                        >
                            <i class="bi bi-person-fill fs-4"></i>
                        </div>

                        <div>

                            <div class="fw-bold">

                                <?= e(
                                    trim(
                                        ($_SESSION["first_name"] ?? "") .
                                        " " .
                                        ($_SESSION["last_name"] ?? "")
                                    )
                                ) ?>

                            </div>

                            <small class="text-muted">
                                Restaurant Staff
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Navigation -->

                <div class="list-group staff-nav">

                    <a
                        href="dashboard.php"
                        class="list-group-item active"
                    >
                        <i class="bi bi-speedometer2 me-2"></i>
                        Dashboard
                    </a>


                    <a
                        href="orders.php"
                        class="list-group-item"
                    >
                        <i class="bi bi-receipt me-2"></i>
                        Orders
                    </a>


                    <a
                        href="orders.php?status=Pending"
                        class="list-group-item"
                    >

                        <i class="bi bi-bell me-2"></i>

                        Incoming Orders

                        <?php if ($pendingOrders): ?>

                            <span class="badge text-bg-danger float-end">
                                <?= $pendingOrders ?>
                            </span>

                        <?php endif; ?>

                    </a>


                    <a
                        href="payments.php"
                        class="list-group-item"
                    >
                        <i class="bi bi-cash-stack me-2"></i>
                        Payment Monitoring
                    </a>

                </div>

            </div>

        </aside>


        <!-- =========================
             MAIN CONTENT
             ========================= -->

        <main class="col-lg-10 staff-main p-3 p-lg-4">


            <!-- Page Header -->

            <div class="mb-4">

                <h1 class="h3 fw-bold mb-1">
                    Staff Dashboard
                </h1>

                <p class="text-muted mb-0">
                    Manage restaurant orders, preparation, pickup,
                    and payment monitoring.
                </p>

            </div>


            <!-- =========================
                 STATISTICS
                 ========================= -->

            <div class="row g-3 mb-4">

                <?php

                $statistics = [
                    [
                        "Active Orders",
                        $activeOrders,
                        "bi-receipt",
                        "danger"
                    ],
                    [
                        "Pending",
                        $pendingOrders,
                        "bi-bell",
                        "warning"
                    ],
                    [
                        "Preparing",
                        $preparingOrders,
                        "bi-fire",
                        "info"
                    ],
                    [
                        "Today's Orders",
                        $todayOrders,
                        "bi-calendar-check",
                        "success"
                    ]
                ];

                ?>

                <?php foreach ($statistics as $x): ?>

                    <div class="col-sm-6 col-xl-3">

                        <div class="card staff-card h-100">

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >

                                    <div>

                                        <div class="text-muted small">
                                            <?= e($x[0]) ?>
                                        </div>

                                        <div class="fs-3 fw-bold">
                                            <?= $x[1] ?>
                                        </div>

                                    </div>


                                    <div
                                        class="staff-stat-icon rounded-circle
                                        bg-<?= $x[3] ?>-subtle
                                        text-<?= $x[3] ?>
                                        d-flex align-items-center justify-content-center"
                                    >

                                        <i
                                            class="bi <?= $x[2] ?> fs-5"
                                        ></i>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- =========================
                 RECENT ORDERS
                 ========================= -->

            <div class="card staff-card">

                <div class="card-header bg-white py-3">

                    <div
                        class="d-flex flex-wrap justify-content-between
                        align-items-center gap-2"
                    >

                        <div>

                            <h2 class="h5 fw-bold mb-1">
                                Recent Orders
                            </h2>

                            <small class="text-muted">
                                Latest customer orders requiring
                                staff attention.
                            </small>

                        </div>

                        <a
                            href="orders.php"
                            class="btn btn-sm btn-staff"
                        >
                            View All Orders
                        </a>

                    </div>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th class="ps-4">
                                        Order
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th class="text-end pe-4">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (!$recentOrders): ?>

                                    <tr>

                                        <td
                                            colspan="8"
                                            class="text-center py-5 text-muted"
                                        >
                                            No orders found.
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($recentOrders as $o): ?>

                                        <tr class="staff-order-row">


                                            <!-- Order Number -->

                                            <td class="ps-4 fw-bold">

                                                <?= e($o["order_number"]) ?>

                                            </td>


                                            <!-- Customer -->

                                            <td>

                                                <?= e(
                                                    $o["first_name"] .
                                                    " " .
                                                    $o["last_name"]
                                                ) ?>

                                            </td>


                                            <!-- Order Type -->

                                            <td>

                                                <span class="badge text-bg-light">

                                                    <?= e($o["order_type"]) ?>

                                                </span>

                                            </td>


                                            <!-- Total -->

                                            <td>

                                                ₱<?= number_format(
                                                    (float) $o["total_amount"],
                                                    2
                                                ) ?>

                                            </td>


                                            <!-- Payment -->

                                            <td>

                                                <span
                                                    class="badge
                                                    <?= $o["payment_status"] === "Paid"
                                                        ? "text-bg-success"
                                                        : "text-bg-warning" ?>"
                                                >

                                                    <?= e($o["payment_status"]) ?>

                                                </span>

                                            </td>


                                            <!-- Status -->

                                            <td>

                                                <span
                                                    class="staff-status <?= statusClass($o["order_status"]) ?>"
                                                >
                                                    <?= e($o["order_status"]) ?>
                                                </span>

                                            </td>


                                            <!-- Date -->

                                            <td>

                                                <small>

                                                    <?= date(
                                                        "M d, Y h:i A",
                                                        strtotime($o["created_at"])
                                                    ) ?>

                                                </small>

                                            </td>


                                            <!-- Action -->

                                            <td class="text-end pe-4">

                                                <a
                                                    href="view-order.php?id=<?= (int) $o["order_id"] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    View
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


</body>

</html>