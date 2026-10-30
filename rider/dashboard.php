<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("rider");

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Available Deliveries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_type = 'Delivery'
      AND order_status = 'Ready for Delivery'
      AND rider_id IS NULL
");

$available = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Rider's Ready Deliveries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE rider_id = ?
      AND order_status = 'Ready for Delivery'
");

$stmt->execute([$userId]);

$ready = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Rider's Out for Delivery
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE rider_id = ?
      AND order_status = 'Out for Delivery'
");

$stmt->execute([$userId]);

$out = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Completed Deliveries Today
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE rider_id = ?
      AND order_status = 'Completed'
      AND DATE(updated_at) = CURDATE()
");

$stmt->execute([$userId]);

$completed = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Current Rider Deliveries
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.order_number,
        o.order_status,
        o.order_type,
        o.total_amount,
        o.payment_method,
        o.cash_received,
        o.change_amount,
        u.first_name,
        u.last_name
    FROM orders o
    JOIN users u
        ON u.user_id = o.user_id
    WHERE o.rider_id = ?
      AND o.order_status IN (
          'Ready for Delivery',
          'Out for Delivery'
      )
    ORDER BY o.created_at DESC
    LIMIT 10
");

$stmt->execute([$userId]);

$orders = $stmt->fetchAll();

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Rider Dashboard | Jollibee Plaza Central
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/rider.css"
    >

</head>

<body class="rider-page">

    <!-- Top Navigation -->
    <nav class="navbar rider-topbar">

        <div class="container-fluid px-3 px-lg-4">

            <a
                href="dashboard.php"
                class="rider-brand"
            >
                <i class="bi bi-bicycle me-2"></i>
                Jollibee Plaza Central
            </a>

            <div class="d-flex align-items-center gap-3 text-white">

                <span class="small">

                    <i class="bi bi-person-circle me-1"></i>

                    <?= e($_SESSION['first_name'] ?? 'Rider') ?>

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


    <div class="container-fluid">

        <div class="row">

            <!-- Sidebar -->
            <aside class="col-lg-2 p-0 rider-sidebar">

                <div class="p-3">

                    <div class="rider-profile p-3 mb-3">

                        <div class="d-flex align-items-center gap-3">

                            <div
                                class="rider-avatar rounded-circle d-flex align-items-center justify-content-center"
                            >
                                <i class="bi bi-bicycle fs-4"></i>
                            </div>

                            <div>

                                <div class="fw-bold">

                                    <?= e(
                                        trim(
                                            ($_SESSION['first_name'] ?? '')
                                            . ' '
                                            . ($_SESSION['last_name'] ?? '')
                                        )
                                    ) ?>

                                </div>

                                <small class="text-muted">
                                    Delivery Rider
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- Rider Navigation -->
                    <div class="list-group rider-nav">

                        <a
                            href="dashboard.php"
                            class="list-group-item active"
                        >
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard
                        </a>

                        <a
                            href="available.php"
                            class="list-group-item"
                        >

                            <i class="bi bi-lightning-charge me-2"></i>

                            Available Deliveries

                            <?php if ($available > 0): ?>

                                <span class="badge text-bg-danger float-end">
                                    <?= $available ?>
                                </span>

                            <?php endif; ?>

                        </a>

                        <a
                            href="orders.php?status=Ready%20for%20Delivery"
                            class="list-group-item"
                        >
                            <i class="bi bi-box-seam me-2"></i>
                            My Deliveries
                        </a>

                        <a
                            href="orders.php?status=Out%20for%20Delivery"
                            class="list-group-item"
                        >
                            <i class="bi bi-truck me-2"></i>
                            Out for Delivery
                        </a>

                        <a
                            href="orders.php?status=Completed"
                            class="list-group-item"
                        >
                            <i class="bi bi-check-circle me-2"></i>
                            Delivery History
                        </a>

                    </div>

                </div>

            </aside>


            <!-- Main Content -->
            <main class="col-lg-10 rider-main p-3 p-lg-4">

                <!-- Page Header -->
                <div
                    class="d-flex justify-content-between align-items-center mb-4 gap-3"
                >

                    <div>

                        <h1 class="h3 fw-bold mb-1">
                            Rider Dashboard
                        </h1>

                        <p class="text-muted mb-0">
                            Find available deliveries, manage accepted
                            orders, collect cash, and submit proof of delivery.
                        </p>

                    </div>

                    <a
                        href="available.php"
                        class="btn btn-rider"
                    >
                        <i class="bi bi-lightning-charge me-1"></i>
                        Find Deliveries
                    </a>

                </div>


                <!-- Dashboard Statistics -->
                <div class="row g-3 mb-4">

                    <!-- Available Deliveries -->
                    <div class="col-md-3">

                        <div class="card rider-card">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Available Deliveries
                                </div>

                                <div class="fs-3 fw-bold text-danger">
                                    <?= $available ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- Ready Deliveries -->
                    <div class="col-md-3">

                        <div class="card rider-card">

                            <div class="card-body">

                                <div class="text-muted small">
                                    My Ready Deliveries
                                </div>

                                <div class="fs-3 fw-bold text-warning">
                                    <?= $ready ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- Out for Delivery -->
                    <div class="col-md-3">

                        <div class="card rider-card">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Out for Delivery
                                </div>

                                <div class="fs-3 fw-bold text-primary">
                                    <?= $out ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- Completed Today -->
                    <div class="col-md-3">

                        <div class="card rider-card">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Completed Today
                                </div>

                                <div class="fs-3 fw-bold text-success">
                                    <?= $completed ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Current Deliveries -->
                <div class="card rider-card">

                    <div class="card-header bg-white py-3">

                        <h2 class="h5 fw-bold mb-0">
                            My Current Deliveries
                        </h2>

                    </div>

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
                                        Payment
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th class="text-end pe-4">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (!$orders): ?>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center py-5 text-muted"
                                        >
                                            You have no accepted deliveries yet.
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($orders as $o): ?>

                                        <tr>

                                            <td class="ps-4 fw-bold">
                                                <?= e($o['order_number']) ?>
                                            </td>

                                            <td>
                                                <?= e(
                                                    $o['first_name']
                                                    . ' '
                                                    . $o['last_name']
                                                ) ?>
                                            </td>

                                            <td>

                                                <?= e($o['payment_method']) ?>

                                                <?php if (
                                                    in_array(
                                                        $o['payment_method'],
                                                        ['Cash', 'Cash on Delivery'],
                                                        true
                                                    )
                                                ): ?>

                                                    <div class="small text-warning">

                                                        Give:
                                                        ₱<?= number_format(
                                                            (float) $o['cash_received'],
                                                            2
                                                        ) ?>

                                                        ·

                                                        Change:
                                                        ₱<?= number_format(
                                                            (float) $o['change_amount'],
                                                            2
                                                        ) ?>

                                                    </div>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                ₱<?= number_format(
                                                    (float) $o['total_amount'],
                                                    2
                                                ) ?>

                                            </td>

                                            <td>

                                                <span
                                                    class="badge
                                                    <?= $o['order_status'] === 'Out for Delivery'
                                                        ? 'text-bg-primary'
                                                        : 'text-bg-warning'
                                                    ?>"
                                                >
                                                    <?= e($o['order_status']) ?>
                                                </span>

                                            </td>

                                            <td class="text-end pe-4">

                                                <a
                                                    class="btn btn-sm btn-rider"
                                                    href="view-order.php?id=<?= (int) $o['order_id'] ?>"
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

            </main>

        </div>

    </div>

</body>

</html>