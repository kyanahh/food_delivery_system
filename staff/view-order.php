<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


/* =========================
   HELPER FUNCTIONS
   ========================= */

function e($v)
{
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES,
        "UTF-8"
    );
}


function statusClass($s)
{
    return match ($s) {

        "Pending" =>
            "staff-status-pending",

        "Confirmed" =>
            "staff-status-confirmed",

        "Preparing" =>
            "staff-status-preparing",

        "Ready for Delivery",
        "Ready for Pickup" =>
            "staff-status-ready",

        "Out for Delivery" =>
            "staff-status-out",

        "Completed" =>
            "staff-status-completed",

        "Cancelled" =>
            "staff-status-cancelled",

        default =>
            "bg-light text-dark"
    };
}


/* =========================
   GET ORDER ID
   ========================= */

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {

    header("Location: orders.php");
    exit;

}


/* =========================
   GET ORDER DETAILS
   ========================= */

$s = $pdo->prepare(
    "SELECT
        o.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone
     FROM orders o
     INNER JOIN users u
        ON u.user_id = o.user_id
     WHERE o.order_id = ?
     LIMIT 1"
);

$s->execute([$id]);

$order = $s->fetch();

if (!$order) {

    header("Location: orders.php");
    exit;

}


/* =========================
   GET ORDER ITEMS
   ========================= */

$s = $pdo->prepare(
    "SELECT
        oi.order_item_id,
        oi.food_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        fi.food_name,
        fi.image
     FROM order_items oi
     LEFT JOIN food_items fi
        ON fi.food_id = oi.food_id
     WHERE oi.order_id = ?
     ORDER BY oi.order_item_id ASC"
);

$s->execute([$id]);

$items = $s->fetchAll();


/* =========================
   AVAILABLE NEXT STATUSES
   ========================= */

$next = [];

switch ($order["order_status"]) {

    case "Pending":

        $next = [
            "Confirmed",
            "Cancelled"
        ];

        break;


    case "Confirmed":

        $next = [
            "Preparing",
            "Cancelled"
        ];

        break;


    case "Preparing":

        $next = [
            $order["order_type"] === "Pickup"
                ? "Ready for Pickup"
                : "Ready for Delivery"
        ];

        break;

    case "Ready for Pickup":
        $next = ["Completed"];
        break;
}


/* =========================
   PAYMENT TYPE CHECKS
   ========================= */

$deliveryCash =
    $order["order_type"] === "Delivery" &&
    in_array(
        $order["payment_method"],
        [
            "Cash on Delivery",
            "Cash"
        ],
        true
    );


$pickupCash =
    $order["order_type"] === "Pickup" &&
    in_array(
        $order["payment_method"],
        [
            "Cash on Delivery",
            "Cash"
        ],
        true
    );


/* =========================
   MESSAGES
   ========================= */

$message = $_GET["message"] ?? "";
$error   = $_GET["error"] ?? "";

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
        <?= e($order["order_number"]) ?> | Staff
    </title>


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

        <a
            href="dashboard.php"
            class="staff-brand d-flex align-items-center gap-2"
        >

            <i class="bi bi-shop"></i>

            <span>
                Jollibee Plaza Central
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
                        class="list-group-item"
                    >

                        <i class="bi bi-speedometer2 me-2"></i>

                        Dashboard

                    </a>


                    <a
                        href="orders.php"
                        class="list-group-item active"
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


            <!-- PAGE HEADER -->

            <div
                class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"
            >

                <div>

                    <a
                        href="orders.php"
                        class="text-decoration-none text-muted small"
                    >

                        <i class="bi bi-arrow-left me-1"></i>

                        Back to Orders

                    </a>


                    <h1 class="h3 fw-bold mt-2 mb-1">

                        <?= e($order["order_number"]) ?>

                    </h1>


                    <div class="text-muted small">

                        Placed
                        <?= date(
                            "M d, Y h:i A",
                            strtotime($order["created_at"])
                        ) ?>

                    </div>

                </div>


                <span
                    class="staff-status <?= statusClass($order["order_status"]) ?>"
                >

                    <?= e($order["order_status"]) ?>

                </span>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if ($message): ?>

                <div class="alert alert-success">

                    <i class="bi bi-check-circle me-1"></i>

                    <?= e($message) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-circle me-1"></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <div class="row g-4">


                <!-- =========================
                     LEFT COLUMN
                     ========================= -->

                <div class="col-xl-8">


                    <!-- ORDER ITEMS -->

                    <div class="card staff-card mb-4">

                        <div class="card-header bg-white py-3">

                            <h2 class="h5 fw-bold mb-0">
                                Order Items
                            </h2>

                        </div>


                        <div class="card-body">

                            <?php foreach ($items as $item): ?>

                                <div class="staff-order-item py-3">

                                    <div class="row align-items-center g-3">


                                        <!-- Food Image -->

                                        <div class="col-auto">

                                            <?php if (!empty($item["image"])): ?>

                                                <img
                                                    src="../assets/uploads/menu/<?= e($item["image"]) ?>"
                                                    alt="<?= e($item["food_name"] ?? "Food") ?>"
                                                    style="
                                                        width: 72px;
                                                        height: 72px;
                                                        object-fit: cover;
                                                    "
                                                    class="rounded-3 bg-light"
                                                >

                                            <?php else: ?>

                                                <div
                                                    class="rounded-3 bg-light d-flex align-items-center justify-content-center"
                                                    style="
                                                        width: 72px;
                                                        height: 72px;
                                                    "
                                                >

                                                    <i class="bi bi-image text-muted fs-4"></i>

                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <!-- Food Details -->

                                        <div class="col">

                                            <div class="fw-bold">

                                                <?= e(
                                                    $item["food_name"] ??
                                                    "Food Item"
                                                ) ?>

                                            </div>


                                            <small class="text-muted">

                                                ₱<?= number_format(
                                                    (float) $item["unit_price"],
                                                    2
                                                ) ?>

                                                ×

                                                <?= (int) $item["quantity"] ?>

                                            </small>

                                        </div>


                                        <!-- Subtotal -->

                                        <div class="col-auto fw-bold">

                                            ₱<?= number_format(
                                                (float) $item["subtotal"],
                                                2
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <!-- CUSTOMER INFORMATION -->

                    <div class="card staff-card">

                        <div class="card-header bg-white py-3">

                            <h2 class="h5 fw-bold mb-0">
                                Customer Information
                            </h2>

                        </div>


                        <div class="card-body">

                            <div class="row g-3">


                                <!-- Customer Name -->

                                <div class="col-md-6">

                                    <div class="text-muted small">
                                        Customer Name
                                    </div>

                                    <div class="fw-semibold">

                                        <?= e(
                                            $order["first_name"] .
                                            " " .
                                            $order["last_name"]
                                        ) ?>

                                    </div>

                                </div>


                                <!-- Email -->

                                <div class="col-md-6">

                                    <div class="text-muted small">
                                        Email
                                    </div>

                                    <div class="fw-semibold">

                                        <?= e($order["email"]) ?>

                                    </div>

                                </div>


                                <!-- Phone -->

                                <div class="col-md-6">

                                    <div class="text-muted small">
                                        Phone
                                    </div>

                                    <div class="fw-semibold">

                                        <?= e(
                                            $order["phone"] ?: "Not provided"
                                        ) ?>

                                    </div>

                                </div>


                                <!-- Order Type -->

                                <div class="col-md-6">

                                    <div class="text-muted small">
                                        Order Type
                                    </div>

                                    <div class="fw-semibold">

                                        <?= e($order["order_type"]) ?>

                                    </div>

                                </div>


                                <!-- Address / Pickup -->

                                <div class="col-12">

                                    <div class="text-muted small">

                                        <?= $order["order_type"] === "Delivery"
                                            ? "Delivery Address"
                                            : "Pickup Information"
                                        ?>

                                    </div>

                                    <div class="fw-semibold">

                                        <?= e(
                                            $order["delivery_address"] ?: "N/A"
                                        ) ?>

                                    </div>

                                </div>


                                <!-- Customer Notes -->

                                <?php if (!empty($order["customer_notes"])): ?>

                                    <div class="col-12">

                                        <div class="text-muted small">
                                            Customer Notes
                                        </div>

                                        <div class="alert alert-light border mb-0">

                                            <?= nl2br(
                                                e($order["customer_notes"])
                                            ) ?>

                                        </div>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     RIGHT COLUMN
                     ========================= -->

                <div class="col-xl-4">


                    <!-- PAYMENT MONITORING -->

                    <div class="card staff-card mb-4">

                        <div class="card-header bg-white py-3">

                            <h2 class="h5 fw-bold mb-0">
                                Payment Monitoring
                            </h2>

                        </div>


                        <div class="card-body">


                            <!-- PAYMENT STATE -->

                            <?php if ($order["payment_status"] === "Paid"): ?>

                                <div class="payment-state payment-state-paid mb-3">

                                    <div class="fw-bold text-success">

                                        <i class="bi bi-check-circle me-1"></i>

                                        Paid

                                    </div>

                                    <small class="text-muted">

                                        Payment has been recorded for this order.

                                    </small>

                                </div>


                            <?php elseif ($deliveryCash): ?>

                                <div class="payment-state payment-state-pending mb-3">

                                    <div class="fw-bold text-warning-emphasis">

                                        <i class="bi bi-truck me-1"></i>

                                        Pending Rider Collection

                                    </div>

                                    <small class="text-muted">

                                        For delivery cash orders, the rider
                                        collects the payment and marks it paid
                                        after delivery.

                                    </small>

                                </div>


                            <?php else: ?>

                                <div class="payment-state payment-state-pending mb-3">

                                    <div class="fw-bold text-warning-emphasis">

                                        <i class="bi bi-clock me-1"></i>

                                        Payment Pending

                                    </div>

                                    <small class="text-muted">

                                        Staff must verify/record payment before
                                        marking this order as paid.

                                    </small>

                                </div>

                            <?php endif; ?>


                            <!-- PAYMENT METHOD -->

                            <div class="staff-summary-row">

                                <span class="text-muted">
                                    Method
                                </span>

                                <strong>
                                    <?= e($order["payment_method"]) ?>
                                </strong>

                            </div>


                            <!-- PAYMENT STATUS -->

                            <div class="staff-summary-row">

                                <span class="text-muted">
                                    Status
                                </span>

                                <span
                                    class="badge
                                    <?= $order["payment_status"] === "Paid"
                                        ? "text-bg-success"
                                        : (
                                            $order["payment_status"] === "Failed"
                                                ? "text-bg-danger"
                                                : "text-bg-warning"
                                        )
                                    ?>"
                                >

                                    <?= e($order["payment_status"]) ?>

                                </span>

                            </div>


                            <!-- TOTAL -->

                            <div class="staff-summary-row">

                                <span class="text-muted">
                                    Total
                                </span>

                                <span class="staff-total">

                                    ₱<?= number_format(
                                        (float) $order["total_amount"],
                                        2
                                    ) ?>

                                </span>

                            </div>


                            <!-- CASH DETAILS -->

                            <?php if ($order["cash_received"] !== null): ?>

                                <div class="staff-summary-row">

                                    <span class="text-muted">
                                        Cash Received
                                    </span>

                                    <strong>

                                        ₱<?= number_format(
                                            (float) $order["cash_received"],
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                                <div class="staff-summary-row">

                                    <span class="text-muted">
                                        Change
                                    </span>

                                    <strong>

                                        ₱<?= number_format(
                                            (float) $order["change_amount"],
                                            2
                                        ) ?>

                                    </strong>

                                </div>

                            <?php endif; ?>


                            <!-- PAYMENT PROOF -->

                            <?php if (!empty($order["payment_proof"])): ?>

                                <div class="mt-3">

                                    <div class="small text-muted mb-2">
                                        Payment Proof
                                    </div>

                                    <img
                                        class="payment-proof w-100"
                                        src="../assets/uploads/payment/<?= e($order["payment_proof"]) ?>"
                                        alt="Payment proof"
                                    >

                                </div>

                            <?php endif; ?>


                            <!-- MANAGE PAYMENT -->

                            <?php if (
                                $order["payment_status"] !== "Paid" &&
                                !$deliveryCash
                            ): ?>

                                <a
                                    href="payments.php?order_id=<?= (int) $order["order_id"] ?>"
                                    class="btn btn-staff w-100 mt-3"
                                >

                                    <i class="bi bi-cash-stack me-1"></i>

                                    Manage Payment

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- ORDER SUMMARY -->

                    <div class="card staff-card mb-4">

                        <div class="card-header bg-white py-3">

                            <h2 class="h5 fw-bold mb-0">
                                Order Summary
                            </h2>

                        </div>


                        <div class="card-body">

                            <div class="staff-summary-row">

                                <span class="text-muted">
                                    Order Type
                                </span>

                                <strong>
                                    <?= e($order["order_type"]) ?>
                                </strong>

                            </div>


                            <div class="staff-summary-row">

                                <span class="text-muted">
                                    Total
                                </span>

                                <span class="staff-total">

                                    ₱<?= number_format(
                                        (float) $order["total_amount"],
                                        2
                                    ) ?>

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- UPDATE ORDER STATUS -->

                    <div class="card staff-card">

                        <div class="card-header bg-white py-3">

                            <h2 class="h5 fw-bold mb-0">
                                Update Order Status
                            </h2>

                        </div>


                        <div class="card-body">

                            <?php if ($next): ?>

                                <p class="text-muted small">
                                    Choose the next restaurant status.
                                </p>


                                <?php foreach ($next as $n): ?>

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            <?= $n === "Cancelled"
                                                ? "btn-outline-danger"
                                                : "btn-staff"
                                            ?>
                                            w-100
                                            mb-2
                                        "
                                        data-bs-toggle="modal"
                                        data-bs-target="#statusModal"
                                        data-status="<?= e($n) ?>"
                                    >

                                        <i
                                            class="
                                                bi
                                                <?= $n === "Cancelled"
                                                    ? "bi-x-circle"
                                                    : "bi-arrow-right-circle"
                                                ?>
                                                me-1
                                            "
                                        ></i>

                                        <?= e(
                                            $n === "Cancelled"
                                                ? "Mark as Cancelled"
                                                : "Mark as " . $n
                                        ) ?>

                                    </button>

                                <?php endforeach; ?>


                            <?php else: ?>

                                <div class="text-center">

                                    <i class="bi bi-check-circle text-success fs-2"></i>

                                    <p class="fw-semibold mt-2 mb-1">
                                        No staff action required
                                    </p>

                                    <p class="text-muted small mb-0">
                                        This order has completed the current
                                        staff workflow.
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<!-- =========================
     STATUS CONFIRMATION MODAL
     ========================= -->

<div
    class="modal fade"
    id="statusModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <!-- Modal Header -->

            <div class="modal-header staff-modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-exclamation-circle me-2"></i>

                    Confirm Status Update

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <!-- Modal Form -->

            <form
                method="POST"
                action="update-order.php"
            >

                <div class="modal-body">

                    <p class="mb-2">

                        Are you sure you want to update this order?

                    </p>


                    <div class="alert alert-light border mb-0">

                        <div class="small text-muted">
                            Order
                        </div>

                        <strong>
                            <?= e($order["order_number"]) ?>
                        </strong>


                        <div class="small text-muted mt-2">
                            New Status
                        </div>

                        <strong
                            id="modalStatusText"
                            class="text-staff"
                        ></strong>

                    </div>


                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int) $order["order_id"] ?>"
                    >


                    <input
                        type="hidden"
                        name="order_status"
                        id="modalStatusInput"
                    >

                </div>


                <!-- Modal Footer -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-staff"
                    >

                        Confirm Update

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- Status Modal Script -->

<script>

document
    .getElementById("statusModal")
    .addEventListener("show.bs.modal", function (event) {

        const status =
            event.relatedTarget.getAttribute("data-status");


        document
            .getElementById("modalStatusText")
            .textContent = status;


        document
            .getElementById("modalStatusInput")
            .value = status;

    });

</script>

</body>

</html>