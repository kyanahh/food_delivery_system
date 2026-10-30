<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


/* =========================
   HELPER FUNCTIONS
   ========================= */

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

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


/* =========================
   FILTERS
   ========================= */

$status = trim($_GET["status"] ?? "");
$search = trim($_GET["search"] ?? "");

$allowed = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Ready for Delivery",
    "Ready for Pickup",
    "Out for Delivery",
    "Completed",
    "Cancelled"
];


/* =========================
   GET ORDERS
   ========================= */

$sql = "
    SELECT
        o.order_id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.order_type,
        o.created_at,
        u.first_name,
        u.last_name
    FROM orders o
    INNER JOIN users u
        ON u.user_id = o.user_id
    WHERE 1 = 1
";

$params = [];


/* Status Filter */

if (in_array($status, $allowed, true)) {

    $sql .= " AND o.order_status = ?";
    $params[] = $status;

}


/* Search Filter */

if ($search !== "") {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.first_name LIKE ?
            OR u.last_name LIKE ?
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        )
    ";

    $t = "%$search%";

    array_push(
        $params,
        $t,
        $t,
        $t,
        $t
    );
}


/* Order Results */

$sql .= " ORDER BY o.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute($params);

$orders = $st->fetchAll();


/* =========================
   ORDER COUNTS
   ========================= */

$counts = [];

foreach ($allowed as $x) {

    $s = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders
         WHERE order_status = ?"
    );

    $s->execute([$x]);

    $counts[$x] = (int) $s->fetchColumn();
}

?>


<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Orders | Staff</title>


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

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/staff.css"
    >

</head>


<body class="staff-page">


<!-- =========================
     TOP NAVBAR
     ========================= -->

<nav class="navbar staff-topbar">

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
            <span class="ms-2 fw-bold text-white">
                Jollibee Central Plaza
            </span>

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
                            class="staff-avatar rounded-circle
                            d-flex align-items-center
                            justify-content-center"
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


                <!-- Sidebar Navigation -->

                <div class="list-group staff-nav">


                    <!-- Dashboard -->

                    <a
                        href="dashboard.php"
                        class="list-group-item"
                    >

                        <i class="bi bi-speedometer2 me-2"></i>

                        Dashboard

                    </a>


                    <!-- Orders -->

                    <a
                        href="orders.php"
                        class="list-group-item active"
                    >

                        <i class="bi bi-receipt me-2"></i>

                        Orders

                    </a>


                    <!-- Incoming Orders -->

                    <a
                        href="orders.php?status=Pending"
                        class="list-group-item"
                    >

                        <i class="bi bi-bell me-2"></i>

                        Incoming Orders


                        <?php if ($counts["Pending"]): ?>

                            <span class="badge text-bg-danger float-end">

                                <?= $counts["Pending"] ?>

                            </span>

                        <?php endif; ?>

                    </a>


                    <!-- Payment Monitoring -->

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
                    Orders
                </h1>

                <p class="text-muted mb-0">
                    Review customer orders and manage restaurant preparation.
                </p>

            </div>


            <!-- =========================
                 STATUS SUMMARY
                 ========================= -->

            <div class="row g-2 mb-4">

                <?php

                $statusSummary = [
                    "Pending",
                    "Confirmed",
                    "Preparing",
                    "Ready for Delivery",
                    "Ready for Pickup"
                ];

                ?>

                <?php foreach ($statusSummary as $x): ?>

                    <div class="col-6 col-md">

                        <a
                            href="orders.php?status=<?= urlencode($x) ?>"
                            class="btn btn-light border w-100 text-start"
                        >

                            <small class="text-muted d-block">

                                <?= e($x) ?>

                            </small>

                            <strong>

                                <?= $counts[$x] ?>

                            </strong>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- =========================
                 SEARCH AND FILTER
                 ========================= -->

            <div class="card staff-card mb-4">

                <div class="card-body">

                    <form
                        method="GET"
                        class="row g-2"
                    >


                        <!-- Search -->

                        <div class="col-md-5">

                            <label class="form-label small fw-semibold">
                                Search Orders
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Order number or customer name"
                            >

                        </div>


                        <!-- Status -->

                        <div class="col-md-4">

                            <label class="form-label small fw-semibold">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="">
                                    All Statuses
                                </option>


                                <?php foreach ($allowed as $x): ?>

                                    <option
                                        value="<?= e($x) ?>"
                                        <?= $status === $x ? "selected" : "" ?>
                                    >

                                        <?= e($x) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- Buttons -->

                        <div
                            class="col-md-3 d-flex align-items-end gap-2"
                        >

                            <button
                                type="submit"
                                class="btn btn-staff flex-grow-1"
                            >

                                <i class="bi bi-search me-1"></i>

                                Search

                            </button>


                            <a
                                href="orders.php"
                                class="btn btn-outline-secondary"
                            >
                                Clear
                            </a>

                        </div>

                    </form>

                </div>

            </div>


            <!-- =========================
                 ORDERS TABLE
                 ========================= -->

            <div class="card staff-card">

                <div class="card-header bg-white py-3">

                    <h2 class="h5 fw-bold mb-0">

                        <?= count($orders) ?>

                        Order<?= count($orders) === 1 ? "" : "s" ?>

                    </h2>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table align-middle mb-0">


                            <!-- Table Header -->

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
                                        Payment
                                    </th>

                                    <th>
                                        Total
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


                            <!-- Table Body -->

                            <tbody>


                                <?php if (!$orders): ?>

                                    <tr>

                                        <td
                                            colspan="8"
                                            class="text-center py-5 text-muted"
                                        >

                                            No orders match your search.

                                        </td>

                                    </tr>


                                <?php else: ?>


                                    <?php foreach ($orders as $o): ?>

                                        <tr>


                                            <!-- Order Number -->

                                            <td class="ps-4 fw-bold">

                                                <?= e(
                                                    $o["order_number"]
                                                ) ?>

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

                                                    <?= e(
                                                        $o["order_type"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- Payment -->

                                            <td>

                                                <div class="small">

                                                    <?= e(
                                                        $o["payment_method"]
                                                    ) ?>

                                                </div>


                                                <span
                                                    class="badge
                                                    <?= $o["payment_status"] === "Paid"
                                                        ? "text-bg-success"
                                                        : (
                                                            $o["payment_status"] === "Failed"
                                                                ? "text-bg-danger"
                                                                : "text-bg-warning"
                                                        )
                                                    ?>"
                                                >

                                                    <?= e(
                                                        $o["payment_status"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- Total -->

                                            <td class="fw-semibold">

                                                ₱<?= number_format(
                                                    (float) $o["total_amount"],
                                                    2
                                                ) ?>

                                            </td>


                                            <!-- Status -->

                                            <td>

                                                <span
                                                    class="staff-status <?= statusClass($o["order_status"]) ?>"
                                                >

                                                    <?= e(
                                                        $o["order_status"]
                                                    ) ?>

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

                                                <div class="d-flex justify-content-end gap-1">

                                                    <!-- VIEW -->
                                                    <a
                                                        href="view-order.php?id=<?= (int) $o["order_id"] ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="View Order"
                                                    >
                                                        <i class="bi bi-eye"></i>
                                                    </a>


                                                    <!-- EDIT -->
                                                    <?php if (
                                                        !in_array(
                                                            $o["order_status"],
                                                            ["Completed", "Cancelled"],
                                                            true
                                                        )
                                                    ): ?>

                                                        <a
                                                            href="edit-order.php?id=<?= (int) $o["order_id"] ?>"
                                                            class="btn btn-sm btn-outline-primary"
                                                            title="Edit Order"
                                                        >
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>

                                                    <?php endif; ?>


                                                    <!-- PRINT RECEIPT -->
                                                    <a
                                                        href="print-receipt.php?id=<?= (int) $o["order_id"] ?>"
                                                        target="_blank"
                                                        class="btn btn-sm btn-outline-dark"
                                                        title="Print Receipt"
                                                    >
                                                        <i class="bi bi-printer"></i>
                                                    </a>

                                                </div>

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