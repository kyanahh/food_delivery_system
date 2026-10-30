<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("rider");

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$st = $pdo->prepare("
    SELECT 
        o.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone
    FROM orders o
    JOIN users u ON u.user_id = o.user_id
    WHERE o.order_id = ?
      AND o.rider_id = ?
    LIMIT 1
");

$st->execute([$orderId, $userId]);
$order = $st->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$st = $pdo->prepare("
    SELECT 
        oi.*,
        fi.food_name,
        fi.image
    FROM order_items oi
    JOIN food_items fi ON fi.food_id = oi.food_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id
");

$st->execute([$orderId]);
$items = $st->fetchAll();

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = trim($_GET['message'] ?? '');
$error = trim($_GET['error'] ?? '');

/*
|--------------------------------------------------------------------------
| Payment Information
|--------------------------------------------------------------------------
*/

$isCash = in_array(
    $order['payment_method'],
    ['Cash', 'Cash on Delivery'],
    true
);

$proof = $order['proof_of_delivery'] ?? null;

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
        Delivery <?= e($order['order_number']) ?>
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

<!-- =========================================================
     TOP NAVIGATION
========================================================= -->

<nav class="navbar rider-topbar">

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

        <div class="d-flex align-items-center gap-3">

            <span class="text-white small">
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

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="h3 fw-bold mb-1">
                Order <?= e($order['order_number']) ?>
            </h1>

            <span
                class="badge
                <?= $order['order_status'] === 'Completed'
                    ? 'text-bg-success'
                    : (
                        $order['order_status'] === 'Out for Delivery'
                            ? 'text-bg-primary'
                            : 'text-bg-warning'
                    )
                ?>"
            >
                <?= e($order['order_status']) ?>
            </span>

        </div>

        <a
            href="orders.php"
            class="btn btn-outline-secondary"
        >
            Back
        </a>

    </div>

    <!-- =====================================================
         SUCCESS / ERROR MESSAGES
    ====================================================== -->

    <?php if ($message): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle me-2"></i>

            <?= e($message) ?>

        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div class="col-lg-7">

            <!-- Customer & Delivery -->

            <div class="card rider-card mb-4">

                <div class="card-header bg-white">

                    <h2 class="h5 fw-bold mb-0">
                        Customer & Delivery
                    </h2>

                </div>

                <div class="card-body">

                    <p class="mb-1">

                        <strong>
                            <?= e(
                                $order['first_name'] . ' ' . $order['last_name']
                            ) ?>
                        </strong>

                    </p>

                    <p class="mb-1">

                        <i class="bi bi-telephone me-2"></i>

                        <?= e(
                            $order['phone'] ?: 'Not provided'
                        ) ?>

                    </p>

                    <p class="mb-0">

                        <i class="bi bi-geo-alt me-2"></i>

                        <?= e($order['delivery_address']) ?>

                    </p>

                    <?php if ($order['customer_notes']): ?>

                        <div class="alert alert-light border mt-3 mb-0">

                            <strong>
                                Customer Notes:
                            </strong>

                            <br>

                            <?= nl2br(
                                e($order['customer_notes'])
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Order Items -->

            <div class="card rider-card">

                <div class="card-header bg-white">

                    <h2 class="h5 fw-bold mb-0">
                        Order Items
                    </h2>

                </div>

                <div class="card-body p-0">

                    <div class="list-group list-group-flush">

                        <?php foreach ($items as $item): ?>

                            <div
                                class="list-group-item d-flex justify-content-between align-items-center"
                            >

                                <div>

                                    <strong>
                                        <?= e($item['food_name']) ?>
                                    </strong>

                                    <div class="text-muted small">

                                        Qty:
                                        <?= e($item['quantity']) ?>

                                        ×

                                        ₱<?= number_format(
                                            (float) $item['unit_price'],
                                            2
                                        ) ?>

                                    </div>

                                </div>

                                <strong>

                                    ₱<?= number_format(
                                        (float) $item['subtotal'],
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <div class="col-lg-5">

            <!-- Delivery & Payment -->

            <div class="card rider-card mb-4">

                <div class="card-header bg-white">

                    <h2 class="h5 fw-bold mb-0">
                        Delivery & Payment
                    </h2>

                </div>

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between py-2 border-bottom"
                    >

                        <span>
                            Order Type
                        </span>

                        <strong>
                            <?= e($order['order_type']) ?>
                        </strong>

                    </div>


                    <div
                        class="d-flex justify-content-between py-2 border-bottom"
                    >

                        <span>
                            Payment
                        </span>

                        <strong>
                            <?= e($order['payment_method']) ?>
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between py-2">

                        <span>
                            Total
                        </span>

                        <strong class="text-rider">

                            ₱<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>

                        </strong>

                    </div>


                    <div class="mt-3">

                        <span>
                            Payment Status
                        </span>

                        <div>

                            <span
                                class="badge
                                <?= $order['payment_status'] === 'Paid'
                                    ? 'text-bg-success'
                                    : 'text-bg-warning'
                                ?>"
                            >
                                <?= e($order['payment_status']) ?>
                            </span>

                        </div>

                    </div>


                    <?php if ($isCash): ?>

                        <div class="alert alert-warning mt-3 mb-0">

                            <div>

                                <strong>
                                    Customer will give:
                                </strong>

                                ₱<?= number_format(
                                    (float) $order['cash_received'],
                                    2
                                ) ?>

                            </div>

                            <div>

                                <strong>
                                    Expected change:
                                </strong>

                                ₱<?= number_format(
                                    (float) $order['change_amount'],
                                    2
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 PROOF OF DELIVERY
            ================================================== -->

            <?php if ($order['order_status'] === 'Out for Delivery'): ?>

                <div class="card rider-card mb-4">

                    <div class="card-header bg-white">

                        <h2 class="h5 fw-bold mb-0">
                            Proof of Delivery
                        </h2>

                    </div>

                    <div class="card-body">

                        <?php if ($proof): ?>

                            <div class="alert alert-success py-2">

                                <i class="bi bi-check-circle me-1"></i>

                                Proof uploaded.

                            </div>


                            <img
                                src="../assets/uploads/delivery/<?= e(basename($proof)) ?>"
                                class="img-fluid rounded border mb-3"
                                alt="Proof of delivery"
                            >


                            <!-- Replace Proof -->

                            <form
                                method="post"
                                action="upload-proof.php"
                                enctype="multipart/form-data"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= e($orderId) ?>"
                                >

                                <label class="form-label small fw-semibold">

                                    Replace proof (optional)

                                </label>

                                <input
                                    type="file"
                                    name="proof_of_delivery"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                >

                                <button
                                    class="btn btn-outline-danger w-100 mt-2"
                                    type="submit"
                                >

                                    Replace Proof

                                </button>

                            </form>

                        <?php else: ?>

                            <p class="text-muted small">

                                Take a clear photo showing that the order
                                was delivered, then upload it here.

                            </p>


                            <!-- Upload Proof -->

                            <form
                                method="post"
                                action="upload-proof.php"
                                enctype="multipart/form-data"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= e($orderId) ?>"
                                >

                                <input
                                    type="file"
                                    name="proof_of_delivery"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                >

                                <small class="text-muted">

                                    JPG, PNG, or WEBP · maximum 5MB

                                </small>

                                <button
                                    class="btn btn-outline-danger w-100 mt-2"
                                    type="submit"
                                >

                                    <i class="bi bi-camera me-1"></i>

                                    Upload Proof of Delivery

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 DELIVERY ACTIONS
            ================================================== -->

            <div class="card rider-card">

                <div class="card-body">

                    <?php if ($order['order_status'] === 'Ready for Delivery'): ?>

                        <!-- Start Delivery -->

                        <form
                            method="post"
                            action="update-order.php"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="start"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= e($order['order_id']) ?>"
                            >

                            <button
                                class="btn btn-rider w-100"
                                type="submit"
                            >

                                <i class="bi bi-truck me-1"></i>

                                Accept & Start Delivery

                            </button>

                        </form>


                    <?php elseif ($order['order_status'] === 'Out for Delivery'): ?>

                        <!-- Collect Cash -->

                        <?php if (
                            $isCash &&
                            $order['payment_status'] !== 'Paid'
                        ): ?>

                            <a
                                href="payment.php?id=<?= (int) $order['order_id'] ?>"
                                class="btn btn-outline-danger w-100 mb-2"
                            >

                                <i class="bi bi-cash-coin me-1"></i>

                                Collect Payment

                            </a>

                        <?php endif; ?>


                        <!-- Complete Delivery -->

                        <?php if (!$proof): ?>

                            <button
                                class="btn btn-secondary w-100"
                                type="button"
                                disabled
                            >

                                <i class="bi bi-image me-1"></i>

                                Upload Proof First

                            </button>

                        <?php else: ?>

                            <form
                                method="post"
                                action="update-order.php"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="complete"
                                >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= e($order['order_id']) ?>"
                                >

                                <button
                                    class="btn btn-rider w-100"
                                    type="submit"
                                    <?= (
                                        $isCash &&
                                        $order['payment_status'] !== 'Paid'
                                    )
                                        ? 'disabled'
                                        : ''
                                    ?>
                                >

                                    <i class="bi bi-check-circle me-1"></i>

                                    Mark as Delivered

                                </button>

                            </form>


                            <?php if (
                                $isCash &&
                                $order['payment_status'] !== 'Paid'
                            ): ?>

                                <small class="text-danger d-block mt-2">

                                    Collect the cash payment before marking
                                    the delivery completed.

                                </small>

                            <?php endif; ?>

                        <?php endif; ?>


                    <?php else: ?>

                        <div class="alert alert-success mb-0">

                            <i class="bi bi-check-circle me-2"></i>

                            This delivery is completed.

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</main>

</body>

</html>