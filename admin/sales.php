<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole("admin");

$from = $_GET["from"] ?? date("Y-m-01");
$to   = $_GET["to"] ?? date("Y-m-d");

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date("Y-m-01");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date("Y-m-d");
}

$fromDateTime = $from . " 00:00:00";
$toDateTime   = $to . " 23:59:59";

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/
$summarySql = "
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(o.total_amount), 0) AS total_sales,
        COALESCE(AVG(o.total_amount), 0) AS average_order_value,
        SUM(
            CASE
                WHEN o.order_type = 'Delivery' THEN 1
                ELSE 0
            END
        ) AS delivery_orders,
        SUM(
            CASE
                WHEN o.order_type = 'Pickup' THEN 1
                ELSE 0
            END
        ) AS pickup_orders
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
";

$stmt = $pdo->prepare($summarySql);
$stmt->execute([$fromDateTime, $toDateTime]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DAILY SALES
|--------------------------------------------------------------------------
*/
$dailySql = "
    SELECT
        DATE(o.created_at) AS sales_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(o.total_amount), 0) AS sales
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY sales_date DESC
";

$stmt = $pdo->prepare($dailySql);
$stmt->execute([$fromDateTime, $toDateTime]);
$dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| TOP FOOD ITEMS
|--------------------------------------------------------------------------
*/
$itemsSql = "
    SELECT
        oi.food_id,
        COALESCE(
            fi.food_name,
            CONCAT('Food #', oi.food_id)
        ) AS food_name,
        SUM(oi.quantity) AS quantity_sold,
        COALESCE(SUM(oi.subtotal), 0) AS item_sales
    FROM order_items oi
    INNER JOIN orders o
        ON o.order_id = oi.order_id
    LEFT JOIN food_items fi
        ON fi.food_id = oi.food_id
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
    GROUP BY oi.food_id, fi.food_name
    ORDER BY quantity_sold DESC, item_sales DESC
    LIMIT 10
";

$stmt = $pdo->prepare($itemsSql);
$stmt->execute([$fromDateTime, $toDateTime]);
$topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ORDER TYPE
|--------------------------------------------------------------------------
*/
$typeSql = "
    SELECT
        o.order_type,
        COUNT(*) AS order_count,
        COALESCE(SUM(o.total_amount), 0) AS sales
    FROM orders o
    WHERE o.payment_status = 'Paid'
      AND o.order_status <> 'Cancelled'
      AND o.created_at BETWEEN ? AND ?
    GROUP BY o.order_type
    ORDER BY sales DESC
";

$stmt = $pdo->prepare($typeSql);
$stmt->execute([$fromDateTime, $toDateTime]);
$orderTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function money($value)
{
    return "₱" . number_format((float) $value, 2);
}

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Sales Reports | Jollibee Central Plaza Admin
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --jollibee-red: #d71920;
            --jollibee-dark-red: #b51218;
        }

        body {
            background: #f8f9fa;
        }

        .bg-jollibee {
            background-color: var(--jollibee-red) !important;
        }

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

        .text-jollibee {
            color: var(--jollibee-red) !important;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
        }

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
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
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

        .table th {
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | PRINT REPORT
        |--------------------------------------------------------------------------
        */

        .report-document {
            display: none;
        }

        @media print {

            body {
                background: #fff !important;
            }

            body * {
                visibility: hidden !important;
            }

            .report-document,
            .report-document * {
                visibility: visible !important;
            }

            .report-document {
                display: block !important;

                position: absolute;

                left: 0;
                top: 0;

                width: 100%;

                padding: 0.35in;

                background: #fff;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }

    </style>

</head>

<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-dark bg-jollibee shadow-sm">

    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >
            Jollibee Central Plaza | Admin
        </a>

        <div class="d-flex align-items-center gap-3 text-white">

            <span>

                <i class="bi bi-person-circle me-1"></i>

                <?= e(
                    ($_SESSION["first_name"] ?? "") .
                    " " .
                    ($_SESSION["last_name"] ?? "")
                ) ?>

            </span>

            <a
                href="../logout.php"
                class="btn btn-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<div class="container-fluid">

    <div class="row">


        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <aside
            class="col-md-3 col-lg-2 bg-white border-end sidebar p-3"
        >

            <div class="text-center py-3 mb-3 border-bottom">

                <div class="text-jollibee fs-1">

                    <i class="bi bi-shield-lock-fill"></i>

                </div>

                <div class="fw-bold">

                    <?= e(
                        ($_SESSION["first_name"] ?? "") .
                        " " .
                        ($_SESSION["last_name"] ?? "")
                    ) ?>

                </div>

                <small class="text-muted">
                    Administrator
                </small>

            </div>


            <nav class="nav flex-column">

                <a
                    class="nav-link"
                    href="dashboard.php"
                >
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>


                <a
                    class="nav-link"
                    href="users.php"
                >
                    <i class="bi bi-people me-2"></i>
                    User Management
                </a>


                <a
                    class="nav-link"
                    href="menu.php"
                >
                    <i class="bi bi-grid me-2"></i>
                    Menu Management
                </a>


                <a
                    class="nav-link"
                    href="orders.php"
                >
                    <i class="bi bi-receipt me-2"></i>
                    Orders
                </a>


                <a
                    class="nav-link active"
                    href="sales.php"
                >
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Sales Reports
                </a>


                <a
                    class="nav-link"
                    href="forecast.php"
                >
                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Demand Forecasting
                </a>


                <a
                    class="nav-link"
                    href="settings.php"
                >
                    <i class="bi bi-gear me-2"></i>
                    System Settings
                </a>

            </nav>

        </aside>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <main class="col-md-9 col-lg-10 p-4">


            <!-- HEADER -->

            <div
                class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3"
            >

                <div>

                    <h3 class="fw-bold mb-1">
                        Sales Reports
                    </h3>

                    <p class="text-muted mb-0">
                        Monitor sales performance and order activity.
                    </p>

                </div>


                <!-- FILTER -->

                <form
                    method="GET"
                    class="row g-2 align-items-end"
                >

                    <div class="col-auto">

                        <label
                            class="form-label small fw-semibold mb-1"
                        >
                            From
                        </label>

                        <input
                            type="date"
                            name="from"
                            class="form-control"
                            value="<?= e($from) ?>"
                        >

                    </div>


                    <div class="col-auto">

                        <label
                            class="form-label small fw-semibold mb-1"
                        >
                            To
                        </label>

                        <input
                            type="date"
                            name="to"
                            class="form-control"
                            value="<?= e($to) ?>"
                        >

                    </div>


                    <div class="col-auto">

                        <button
                            type="submit"
                            class="btn btn-jollibee"
                        >

                            <i class="bi bi-filter me-1"></i>

                            Generate

                        </button>

                    </div>


                    <div class="col-auto">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            onclick="printSalesReport()"
                        >

                            <i class="bi bi-printer me-1"></i>

                            Print Report

                        </button>

                    </div>


                    <div class="col-auto">

                        <button
                            type="button"
                            class="btn btn-danger"
                            onclick="downloadSalesReportPDF()"
                        >

                            <i class="bi bi-file-earmark-pdf me-1"></i>

                            Download PDF

                        </button>

                    </div>

                </form>

            </div>


            <!-- INFO -->

            <div class="alert alert-light border mb-4">

                <i class="bi bi-info-circle me-2 text-jollibee"></i>

                Showing paid orders from

                <strong>
                    <?= e(
                        date(
                            "M d, Y",
                            strtotime($from)
                        )
                    ) ?>
                </strong>

                to

                <strong>
                    <?= e(
                        date(
                            "M d, Y",
                            strtotime($to)
                        )
                    ) ?>
                </strong>

                .

                Cancelled orders are excluded.

            </div>


            <!-- =================================================
                 SUMMARY CARDS
            ================================================== -->

            <div class="row g-4 mb-4">


                <!-- TOTAL SALES -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between align-items-center"
                            >

                                <div>

                                    <div class="text-muted small">
                                        Total Sales
                                    </div>

                                    <h4 class="fw-bold mb-0">

                                        <?= money(
                                            $summary["total_sales"]
                                        ) ?>

                                    </h4>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-cash-stack"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PAID ORDERS -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between align-items-center"
                            >

                                <div>

                                    <div class="text-muted small">
                                        Paid Orders
                                    </div>

                                    <h4 class="fw-bold mb-0">

                                        <?= number_format(
                                            (int) $summary["total_orders"]
                                        ) ?>

                                    </h4>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-receipt"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- AVERAGE ORDER -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between align-items-center"
                            >

                                <div>

                                    <div class="text-muted small">
                                        Average Order Value
                                    </div>

                                    <h4 class="fw-bold mb-0">

                                        <?= money(
                                            $summary["average_order_value"]
                                        ) ?>

                                    </h4>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-graph-up"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- DELIVERY / PICKUP -->

                <div class="col-md-6 col-xl-3">

                    <div class="card summary-card h-100">

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between align-items-center"
                            >

                                <div>

                                    <div class="text-muted small">
                                        Delivery / Pickup
                                    </div>

                                    <h4 class="fw-bold mb-0">

                                        <?= (int)
                                            $summary["delivery_orders"] ?>

                                        /

                                        <?= (int)
                                            $summary["pickup_orders"] ?>

                                    </h4>

                                </div>

                                <div class="summary-icon">

                                    <i class="bi bi-truck"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 DAILY SALES
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white py-3">

                    <h5 class="fw-bold mb-0">

                        <i class="bi bi-calendar3 me-2 text-jollibee"></i>

                        Daily Sales

                    </h5>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th>
                                        Date
                                    </th>

                                    <th class="text-end">
                                        Orders
                                    </th>

                                    <th class="text-end">
                                        Sales
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (!empty($dailySales)): ?>

                                    <?php foreach (
                                        $dailySales
                                        as $row
                                    ): ?>

                                        <tr>

                                            <td>

                                                <?= e(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $row["sales_date"]
                                                        )
                                                    )
                                                ) ?>

                                            </td>


                                            <td class="text-end">

                                                <?= number_format(
                                                    (int)
                                                    $row["order_count"]
                                                ) ?>

                                            </td>


                                            <td class="text-end fw-semibold">

                                                <?= money(
                                                    $row["sales"]
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="text-center text-muted py-4"
                                        >

                                            No sales data found
                                            for the selected period.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TWO TABLES
            ================================================== -->

            <div class="row g-4">


                <!-- ORDER TYPE -->

                <div class="col-lg-5">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">

                                <i class="bi bi-basket me-2 text-jollibee"></i>

                                Order Type

                            </h5>

                        </div>


                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle mb-0"
                                >

                                    <thead class="table-light">

                                        <tr>

                                            <th>
                                                Type
                                            </th>

                                            <th class="text-end">
                                                Orders
                                            </th>

                                            <th class="text-end">
                                                Sales
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php if (!empty($orderTypes)): ?>

                                            <?php foreach (
                                                $orderTypes
                                                as $row
                                            ): ?>

                                                <tr>

                                                    <td>

                                                        <?= e(
                                                            $row["order_type"]
                                                        ) ?>

                                                    </td>

                                                    <td class="text-end">

                                                        <?= number_format(
                                                            (int)
                                                            $row["order_count"]
                                                        ) ?>

                                                    </td>

                                                    <td class="text-end fw-semibold">

                                                        <?= money(
                                                            $row["sales"]
                                                        ) ?>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <tr>

                                                <td
                                                    colspan="3"
                                                    class="text-center text-muted py-4"
                                                >

                                                    No order type
                                                    data.

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TOP ITEMS -->

                <div class="col-lg-7">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">

                                <i
                                    class="bi bi-trophy me-2 text-jollibee"
                                ></i>

                                Top-Selling Food Items

                            </h5>

                        </div>


                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table
                                    class="table table-hover align-middle mb-0"
                                >

                                    <thead class="table-light">

                                        <tr>

                                            <th>
                                                #
                                            </th>

                                            <th>
                                                Food Item
                                            </th>

                                            <th class="text-end">
                                                Quantity
                                            </th>

                                            <th class="text-end">
                                                Sales
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php if (!empty($topItems)): ?>

                                            <?php foreach (
                                                $topItems
                                                as $index => $item
                                            ): ?>

                                                <tr>

                                                    <td>

                                                        <?= $index + 1 ?>

                                                    </td>

                                                    <td class="fw-semibold">

                                                        <?= e(
                                                            $item["food_name"]
                                                        ) ?>

                                                    </td>

                                                    <td class="text-end">

                                                        <?= number_format(
                                                            (int)
                                                            $item["quantity_sold"]
                                                        ) ?>

                                                    </td>

                                                    <td class="text-end fw-semibold">

                                                        <?= money(
                                                            $item["item_sales"]
                                                        ) ?>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <tr>

                                                <td
                                                    colspan="4"
                                                    class="text-center text-muted py-4"
                                                >

                                                    No item sales
                                                    data found.

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 PROFESSIONAL PRINT DOCUMENT
            ================================================== -->

            <section
                id="reportDocument"
                class="report-document"
            >

                <!-- BUSINESS HEADER -->

                <div
                    style="
                        text-align:center;
                        border-bottom:3px solid #d71920;
                        padding-bottom:12px;
                        margin-bottom:18px;
                    "
                >

                    <div
                        style="
                            font-size:24px;
                            font-weight:bold;
                            color:#d71920;
                        "
                    >
                        JOLLIBEE PLAZA CENTRAL
                    </div>

                    <div
                        style="
                            font-size:11px;
                            color:#555;
                            margin-top:4px;
                        "
                    >
                        GF, Plaza, Central Bldg,
                        Katihan Rd, Brgy Poblacion,
                        Muntinlupa, Metro Manila
                    </div>

                    <div
                        style="
                            font-size:11px;
                            color:#555;
                        "
                    >
                        Contact No.:
                        (02) 8851 6362
                    </div>

                </div>


                <!-- REPORT TITLE -->

                <div
                    style="
                        text-align:center;
                        margin-bottom:20px;
                    "
                >

                    <div
                        style="
                            font-size:20px;
                            font-weight:bold;
                        "
                    >
                        SALES REPORT
                    </div>

                    <div
                        style="
                            font-size:11px;
                            color:#555;
                            margin-top:5px;
                        "
                    >

                        Reporting Period:

                        <?= e(
                            date(
                                "F d, Y",
                                strtotime($from)
                            )
                        ) ?>

                        -

                        <?= e(
                            date(
                                "F d, Y",
                                strtotime($to)
                            )
                        ) ?>

                    </div>

                </div>


                <!-- SUMMARY -->

                <h4
                    style="
                        font-size:15px;
                        margin:0 0 8px;
                        border-bottom:2px solid #222;
                        padding-bottom:5px;
                    "
                >
                    Sales Summary
                </h4>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:11px;
                        margin-bottom:22px;
                    "
                >

                    <tbody>

                        <tr>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    font-weight:bold;
                                "
                            >
                                Total Sales
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    text-align:right;
                                "
                            >
                                <?= money(
                                    $summary["total_sales"]
                                ) ?>
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    font-weight:bold;
                                "
                            >
                                Paid Orders
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    text-align:right;
                                "
                            >
                                <?= number_format(
                                    (int)
                                    $summary["total_orders"]
                                ) ?>
                            </td>

                        </tr>


                        <tr>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    font-weight:bold;
                                "
                            >
                                Average Order Value
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    text-align:right;
                                "
                            >
                                <?= money(
                                    $summary["average_order_value"]
                                ) ?>
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    font-weight:bold;
                                "
                            >
                                Delivery / Pickup
                            </td>

                            <td
                                style="
                                    border:1px solid #ddd;
                                    padding:8px;
                                    text-align:right;
                                "
                            >
                                <?= (int)
                                    $summary["delivery_orders"] ?>

                                /

                                <?= (int)
                                    $summary["pickup_orders"] ?>

                            </td>

                        </tr>

                    </tbody>

                </table>


                <!-- DAILY SALES -->

                <h4
                    style="
                        font-size:15px;
                        margin:0 0 8px;
                        border-bottom:2px solid #222;
                        padding-bottom:5px;
                    "
                >
                    Daily Sales
                </h4>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:11px;
                        margin-bottom:22px;
                    "
                >

                    <thead>

                        <tr>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:left;
                                "
                            >
                                Date
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Orders
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Sales
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($dailySales)): ?>

                            <?php foreach (
                                $dailySales
                                as $row
                            ): ?>

                                <tr>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                        "
                                    >

                                        <?= e(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $row["sales_date"]
                                                )
                                            )
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= number_format(
                                            (int)
                                            $row["order_count"]
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= money(
                                            $row["sales"]
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="3"
                                    style="
                                        border:1px solid #ddd;
                                        padding:7px;
                                        text-align:center;
                                    "
                                >
                                    No sales data found
                                    for the selected period.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>


                <!-- ORDER TYPE -->

                <h4
                    style="
                        font-size:15px;
                        margin:0 0 8px;
                        border-bottom:2px solid #222;
                        padding-bottom:5px;
                    "
                >
                    Order Type
                </h4>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:11px;
                        margin-bottom:22px;
                    "
                >

                    <thead>

                        <tr>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:left;
                                "
                            >
                                Order Type
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Orders
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Sales
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($orderTypes)): ?>

                            <?php foreach (
                                $orderTypes
                                as $row
                            ): ?>

                                <tr>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                        "
                                    >

                                        <?= e(
                                            $row["order_type"]
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= number_format(
                                            (int)
                                            $row["order_count"]
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= money(
                                            $row["sales"]
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="3"
                                    style="
                                        border:1px solid #ddd;
                                        padding:7px;
                                        text-align:center;
                                    "
                                >
                                    No order type data.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>


                <!-- TOP FOOD ITEMS -->

                <h4
                    style="
                        font-size:15px;
                        margin:0 0 8px;
                        border-bottom:2px solid #222;
                        padding-bottom:5px;
                    "
                >
                    Top-Selling Food Items
                </h4>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:11px;
                    "
                >

                    <thead>

                        <tr>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:center;
                                "
                            >
                                #
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:left;
                                "
                            >
                                Food Item
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Quantity Sold
                            </th>

                            <th
                                style="
                                    border:1px solid #bbb;
                                    padding:7px;
                                    text-align:right;
                                "
                            >
                                Item Sales
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($topItems)): ?>

                            <?php foreach (
                                $topItems
                                as $index => $item
                            ): ?>

                                <tr>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:center;
                                        "
                                    >

                                        <?= $index + 1 ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                        "
                                    >

                                        <?= e(
                                            $item["food_name"]
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= number_format(
                                            (int)
                                            $item["quantity_sold"]
                                        ) ?>

                                    </td>

                                    <td
                                        style="
                                            border:1px solid #ddd;
                                            padding:7px;
                                            text-align:right;
                                        "
                                    >

                                        <?= money(
                                            $item["item_sales"]
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="4"
                                    style="
                                        border:1px solid #ddd;
                                        padding:7px;
                                        text-align:center;
                                    "
                                >
                                    No item sales data found.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>


                <!-- FOOTER -->

                <div
                    style="
                        margin-top:28px;
                        padding-top:10px;
                        border-top:1px solid #ddd;
                        font-size:10px;
                        color:#666;
                        display:flex;
                        justify-content:space-between;
                    "
                >

                    <span>
                        Generated by Jollibee Plaza Central
                        Online Food Delivery System
                    </span>

                    <span>
                        Generated:
                        <?= e(
                            date("F d, Y h:i A")
                        ) ?>
                    </span>

                </div>

            </section>

        </main>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- jsPDF -->

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"
></script>


<!-- jsPDF AutoTable -->

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"
></script>


<script>

/*
|--------------------------------------------------------------------------
| PRINT PROFESSIONAL REPORT
|--------------------------------------------------------------------------
*/

function printSalesReport()
{
    window.print();
}


/*
|--------------------------------------------------------------------------
| DOWNLOAD PROFESSIONAL PDF
|--------------------------------------------------------------------------
*/

function downloadSalesReportPDF()
{

    if (
        !window.jspdf ||
        !window.jspdf.jsPDF
    ) {

        alert(
            "The PDF generator could not be loaded. Please check your internet connection and try again."
        );

        return;
    }


    const {
        jsPDF
    } = window.jspdf;


    const doc = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4"
    });


    const red = [
        215,
        25,
        32
    ];

    const dark = [
        35,
        35,
        35
    ];

    const gray = [
        90,
        90,
        90
    ];


    /*
    |--------------------------------------------------------------------------
    | BUSINESS HEADER
    |--------------------------------------------------------------------------
    */

    doc.setFont(
        "helvetica",
        "bold"
    );

    doc.setFontSize(18);

    doc.setTextColor(
        ...red
    );

    doc.text(
        "JOLLIBEE PLAZA CENTRAL",
        105,
        18,
        {
            align: "center"
        }
    );


    doc.setFont(
        "helvetica",
        "normal"
    );

    doc.setFontSize(9);

    doc.setTextColor(
        ...gray
    );


    doc.text(
        "GF, Plaza, Central Bldg, Katihan Rd, Brgy Poblacion, Muntinlupa, Metro Manila",
        105,
        24,
        {
            align: "center"
        }
    );


    doc.text(
        "Contact No.: (02) 8851 6362",
        105,
        29,
        {
            align: "center"
        }
    );


    /*
    |--------------------------------------------------------------------------
    | REPORT TITLE
    |--------------------------------------------------------------------------
    */

    doc.setFont(
        "helvetica",
        "bold"
    );

    doc.setFontSize(14);

    doc.setTextColor(
        ...dark
    );


    doc.text(
        "SALES REPORT",
        105,
        39,
        {
            align: "center"
        }
    );


    doc.setFont(
        "helvetica",
        "normal"
    );

    doc.setFontSize(9);

    doc.setTextColor(
        ...gray
    );


    doc.text(
        "Reporting Period: <?= e(date("F d, Y", strtotime($from))) ?> - <?= e(date("F d, Y", strtotime($to))) ?>",
        105,
        45,
        {
            align: "center"
        }
    );


    /*
    |--------------------------------------------------------------------------
    | RED DIVIDER
    |--------------------------------------------------------------------------
    */

    doc.setDrawColor(
        ...red
    );

    doc.setLineWidth(
        0.7
    );

    doc.line(
        15,
        50,
        195,
        50
    );


    /*
    |--------------------------------------------------------------------------
    | SUMMARY TABLE
    |--------------------------------------------------------------------------
    */

    doc.autoTable({

        startY: 56,

        theme: "grid",

        head: [
            [
                "Summary",
                "Value",
                "Summary",
                "Value"
            ]
        ],

        body: [

            [
                "Total Sales",
                "<?= money($summary["total_sales"]) ?>",

                "Paid Orders",
                "<?= number_format((int)$summary["total_orders"]) ?>"
            ],

            [
                "Average Order Value",
                "<?= money($summary["average_order_value"]) ?>",

                "Delivery / Pickup",
                "<?= (int)$summary["delivery_orders"] ?> / <?= (int)$summary["pickup_orders"] ?>"
            ]

        ],

        styles: {
            font: "helvetica",
            fontSize: 9,
            cellPadding: 3
        },

        headStyles: {
            fillColor: red,
            textColor: [
                255,
                255,
                255
            ],
            fontStyle: "bold"
        },

        columnStyles: {

            1: {
                halign: "right"
            },

            3: {
                halign: "right"
            }

        },

        margin: {
            left: 15,
            right: 15
        }

    });


    let y =
        doc.lastAutoTable.finalY +
        10;


    /*
    |--------------------------------------------------------------------------
    | DAILY SALES
    |--------------------------------------------------------------------------
    */

    doc.setFont(
        "helvetica",
        "bold"
    );

    doc.setFontSize(12);

    doc.setTextColor(
        ...dark
    );

    doc.text(
        "Daily Sales",
        15,
        y
    );


    y += 4;


    const dailyRows =
        <?= json_encode(
            array_map(
                function ($row) {

                    return [

                        date(
                            "M d, Y",
                            strtotime(
                                $row["sales_date"]
                            )
                        ),

                        number_format(
                            (int)
                            $row["order_count"]
                        ),

                        money(
                            $row["sales"]
                        )

                    ];

                },
                $dailySales
            ),
            JSON_UNESCAPED_UNICODE
        ) ?>;


    doc.autoTable({

        startY: y,

        head: [
            [
                "Date",
                "Orders",
                "Sales"
            ]
        ],

        body:
            dailyRows.length
                ? dailyRows
                : [
                    [
                        "No sales data",
                        "-",
                        "-"
                    ]
                ],

        theme: "grid",

        styles: {
            font: "helvetica",
            fontSize: 8.5,
            cellPadding: 2.5
        },

        headStyles: {
            fillColor: red,
            textColor: [
                255,
                255,
                255
            ]
        },

        columnStyles: {

            1: {
                halign: "right"
            },

            2: {
                halign: "right"
            }

        },

        margin: {
            left: 15,
            right: 15
        }

    });


    y =
        doc.lastAutoTable.finalY +
        10;


    /*
    |--------------------------------------------------------------------------
    | ORDER TYPE
    |--------------------------------------------------------------------------
    */

    doc.setFont(
        "helvetica",
        "bold"
    );

    doc.setFontSize(12);

    doc.setTextColor(
        ...dark
    );

    doc.text(
        "Order Type",
        15,
        y
    );


    y += 4;


    const orderTypeRows =
        <?= json_encode(
            array_map(
                function ($row) {

                    return [

                        $row["order_type"],

                        number_format(
                            (int)
                            $row["order_count"]
                        ),

                        money(
                            $row["sales"]
                        )

                    ];

                },
                $orderTypes
            ),
            JSON_UNESCAPED_UNICODE
        ) ?>;


    doc.autoTable({

        startY: y,

        head: [
            [
                "Order Type",
                "Orders",
                "Sales"
            ]
        ],

        body:
            orderTypeRows.length
                ? orderTypeRows
                : [
                    [
                        "No order type data",
                        "-",
                        "-"
                    ]
                ],

        theme: "grid",

        styles: {
            font: "helvetica",
            fontSize: 8.5,
            cellPadding: 2.5
        },

        headStyles: {
            fillColor: red,
            textColor: [
                255,
                255,
                255
            ]
        },

        columnStyles: {

            1: {
                halign: "right"
            },

            2: {
                halign: "right"
            }

        },

        margin: {
            left: 15,
            right: 15
        }

    });


    y =
        doc.lastAutoTable.finalY +
        10;


    /*
    |--------------------------------------------------------------------------
    | TOP FOOD ITEMS
    |--------------------------------------------------------------------------
    */

    doc.setFont(
        "helvetica",
        "bold"
    );

    doc.setFontSize(12);

    doc.setTextColor(
        ...dark
    );

    doc.text(
        "Top-Selling Food Items",
        15,
        y
    );


    y += 4;


    const topItemRows =
        <?= json_encode(
            array_map(
                function ($item, $index) {

                    return [

                        $index + 1,

                        $item["food_name"],

                        number_format(
                            (int)
                            $item["quantity_sold"]
                        ),

                        money(
                            $item["item_sales"]
                        )

                    ];

                },
                $topItems,
                array_keys($topItems)
            ),
            JSON_UNESCAPED_UNICODE
        ) ?>;


    doc.autoTable({

        startY: y,

        head: [
            [
                "#",
                "Food Item",
                "Quantity Sold",
                "Item Sales"
            ]
        ],

        body:
            topItemRows.length
                ? topItemRows
                : [
                    [
                        "-",
                        "No item sales data",
                        "-",
                        "-"
                    ]
                ],

        theme: "grid",

        styles: {
            font: "helvetica",
            fontSize: 8.5,
            cellPadding: 2.5
        },

        headStyles: {
            fillColor: red,
            textColor: [
                255,
                255,
                255
            ]
        },

        columnStyles: {

            0: {
                halign: "center"
            },

            2: {
                halign: "right"
            },

            3: {
                halign: "right"
            }

        },

        margin: {
            left: 15,
            right: 15
        }

    });


    /*
    |--------------------------------------------------------------------------
    | PAGE NUMBERS
    |--------------------------------------------------------------------------
    */

    const pageCount =
        doc.getNumberOfPages();


    for (
        let i = 1;
        i <= pageCount;
        i++
    ) {

        doc.setPage(i);


        doc.setFont(
            "helvetica",
            "normal"
        );

        doc.setFontSize(8);

        doc.setTextColor(
            ...gray
        );


        doc.text(
            "Generated by Jollibee Plaza Central Online Food Delivery System",
            15,
            289
        );


        doc.text(
            `Page ${i} of ${pageCount}`,
            195,
            289,
            {
                align: "right"
            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE FILE
    |--------------------------------------------------------------------------
    */

    doc.save(
        "Jollibee_Plaza_Central_Sales_Report_<?= e($from) ?>_to_<?= e($to) ?>.pdf"
    );

}

</script>

</body>

</html>