<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("rider");

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$status = trim($_GET['status'] ?? '');

/*
|--------------------------------------------------------------------------
| Get Rider Orders
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.*,
        u.first_name,
        u.last_name
    FROM orders o
    JOIN users u
        ON u.user_id = o.user_id
    WHERE o.rider_id = ?
";

$p = [$userId];

/*
|--------------------------------------------------------------------------
| Filter by Status
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $status,
        ['Ready for Delivery', 'Out for Delivery', 'Completed'],
        true
    )
) {

    $sql .= " AND o.order_status = ?";
    $p[] = $status;
}

$sql .= " ORDER BY o.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute($p);

$orders = $st->fetchAll();

/*
|--------------------------------------------------------------------------
| Page Heading and Description
|--------------------------------------------------------------------------
*/

$heading = $status === 'Out for Delivery'
    ? 'Out for Delivery'
    : (
        $status === 'Completed'
            ? 'Delivery History'
            : 'My Deliveries'
    );

$description = $status === 'Out for Delivery'
    ? 'Deliveries currently on the way.'
    : (
        $status === 'Completed'
            ? 'Your completed delivery records.'
            : 'Orders you have accepted.'
    );

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
        <?= e($heading) ?> | Rider
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

                    <div class="list-group rider-nav">

                        <a
                            href="dashboard.php"
                            class="list-group-item"
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
                        </a>

                        <a
                            href="orders.php?status=Ready%20for%20Delivery"
                            class="list-group-item
                                <?= $status === 'Ready for Delivery' || $status === ''
                                    ? 'active'
                                    : ''
                                ?>"
                        >
                            <i class="bi bi-box-seam me-2"></i>
                            My Deliveries
                        </a>

                        <a
                            href="orders.php?status=Out%20for%20Delivery"
                            class="list-group-item
                                <?= $status === 'Out for Delivery'
                                    ? 'active'
                                    : ''
                                ?>"
                        >
                            <i class="bi bi-truck me-2"></i>
                            Out for Delivery
                        </a>

                        <a
                            href="orders.php?status=Completed"
                            class="list-group-item
                                <?= $status === 'Completed'
                                    ? 'active'
                                    : ''
                                ?>"
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
                    class="d-flex justify-content-between align-items-center mb-4"
                >

                    <div>

                        <h1 class="h3 fw-bold mb-1">
                            <?= e($heading) ?>
                        </h1>

                        <p class="text-muted mb-0">
                            <?= e($description) ?>
                        </p>

                    </div>

                    <a
                        href="available.php"
                        class="btn btn-outline-danger"
                    >
                        Find Available Deliveries
                    </a>

                </div>


                <!-- Orders Table -->
                <div class="card rider-card">

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
                                        Address
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
                                            colspan="7"
                                            class="text-center py-5 text-muted"
                                        >
                                            No orders found.
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

                                            <td
                                                class="small"
                                                style="max-width: 260px"
                                            >
                                                <?= e(
                                                    $o['delivery_address']
                                                ) ?>
                                            </td>

                                            <td>

                                                <?= e(
                                                    $o['payment_method']
                                                ) ?>

                                                <?php if (
                                                    in_array(
                                                        $o['payment_method'],
                                                        ['Cash', 'Cash on Delivery'],
                                                        true
                                                    )
                                                ): ?>

                                                    <div class="small text-warning">

                                                        Give ₱<?= number_format(
                                                            (float) $o['cash_received'],
                                                            2
                                                        ) ?>

                                                        ·

                                                        Change ₱<?= number_format(
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
                                                    <?= $o['order_status'] === 'Completed'
                                                        ? 'text-bg-success'
                                                        : (
                                                            $o['order_status'] === 'Out for Delivery'
                                                                ? 'text-bg-primary'
                                                                : 'text-bg-warning'
                                                        )
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