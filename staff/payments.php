<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);
$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");

$message = trim($_GET["message"] ?? "");
$error = trim($_GET["error"] ?? "");


/*
|--------------------------------------------------------------------------
| Get Payment Records
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.*,
        u.first_name,
        u.last_name
    FROM orders o
    INNER JOIN users u
        ON u.user_id = o.user_id
    WHERE 1=1
";

$params = [];


/*
|--------------------------------------------------------------------------
| Specific Order
|--------------------------------------------------------------------------
*/

if ($orderId) {

    $sql .= " AND o.order_id = ?";
    $params[] = $orderId;
}


/*
|--------------------------------------------------------------------------
| Payment Status Filter
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $status,
        ["Pending", "Paid", "Failed"],
        true
    )
) {

    $sql .= " AND o.payment_status = ?";
    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        )
    ";

    $searchValue = "%{$search}%";

    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Order By
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY o.created_at DESC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$payments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Payment Counts
|--------------------------------------------------------------------------
*/

$counts = [];

foreach (
    ["Pending", "Paid", "Failed"] as $paymentStatus
) {

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders
         WHERE payment_status = ?"
    );

    $stmt->execute([$paymentStatus]);

    $counts[$paymentStatus] = (int)$stmt->fetchColumn();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Payment Monitoring | Staff</title>

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

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <!-- Staff CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/staff.css"
    >

</head>

<body class="staff-page">


<!-- =========================================================
     TOP NAVBAR
========================================================= -->

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



<!-- =========================================================
     PAGE LAYOUT
========================================================= -->

<div class="container-fluid">

    <div class="row">


        <!-- =================================================
             SIDEBAR
        ================================================== -->

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
                                        ($_SESSION["first_name"] ?? "")
                                        . " "
                                        . ($_SESSION["last_name"] ?? "")
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

                    </a>


                    <a
                        href="payments.php"
                        class="list-group-item active"
                    >

                        <i class="bi bi-cash-stack me-2"></i>

                        Payment Monitoring

                    </a>

                </div>

            </div>

        </aside>



        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <main class="col-lg-10 staff-main p-3 p-lg-4">


            <!-- Page Header -->

            <div class="mb-4">

                <h1 class="h3 fw-bold mb-1">
                    Payment Monitoring
                </h1>

                <p class="text-muted mb-0">

                    Monitor payment status and record payments
                    handled by the restaurant.

                </p>

            </div>



            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if ($message): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-check-circle me-2"></i>

                    <?= e($message) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if ($error): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-exclamation-circle me-2"></i>

                    <?= e($error) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 PAYMENT COUNTERS
            ================================================== -->

            <div class="row g-3 mb-4">

                <!-- Pending -->

                <div class="col-md-4">

                    <a
                        href="payments.php?status=Pending"
                        class="text-decoration-none"
                    >

                        <div class="card staff-card h-100">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Pending Payments
                                </div>

                                <div class="fs-3 fw-bold text-warning">

                                    <?= $counts["Pending"] ?>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>



                <!-- Paid -->

                <div class="col-md-4">

                    <a
                        href="payments.php?status=Paid"
                        class="text-decoration-none"
                    >

                        <div class="card staff-card h-100">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Paid Payments
                                </div>

                                <div class="fs-3 fw-bold text-success">

                                    <?= $counts["Paid"] ?>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>



                <!-- Failed -->

                <div class="col-md-4">

                    <a
                        href="payments.php?status=Failed"
                        class="text-decoration-none"
                    >

                        <div class="card staff-card h-100">

                            <div class="card-body">

                                <div class="text-muted small">
                                    Failed Payments
                                </div>

                                <div class="fs-3 fw-bold text-danger">

                                    <?= $counts["Failed"] ?>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>

            </div>



            <!-- =================================================
                 SEARCH / FILTER
            ================================================== -->

            <div class="card staff-card mb-4">

                <div class="card-body">

                    <form
                        method="GET"
                        class="row g-2"
                    >

                        <div class="col-md-5">

                            <label class="form-label small fw-semibold">
                                Search
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Order number or customer"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label small fw-semibold">
                                Payment Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="">
                                    All
                                </option>

                                <?php foreach (
                                    ["Pending", "Paid", "Failed"]
                                    as $paymentStatus
                                ): ?>

                                    <option
                                        value="<?= e($paymentStatus) ?>"
                                        <?= $status === $paymentStatus ? "selected" : "" ?>
                                    >

                                        <?= e($paymentStatus) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-3 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn btn-staff flex-grow-1"
                            >

                                <i class="bi bi-search me-1"></i>

                                Filter

                            </button>


                            <a
                                href="payments.php"
                                class="btn btn-outline-secondary"
                            >

                                Clear

                            </a>

                        </div>

                    </form>

                </div>

            </div>



            <!-- =================================================
                 PAYMENT TABLE
            ================================================== -->

            <div class="card staff-card">

                <div class="card-header bg-white py-3">

                    <h2 class="h5 fw-bold mb-0">
                        Payment Records
                    </h2>

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
                                        Method
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

                                <?php if (!$payments): ?>

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center py-5 text-muted"
                                        >

                                            No payment records found.

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($payments as $payment): ?>

                                        <tr>

                                            <td class="ps-4 fw-bold">

                                                <?= e(
                                                    $payment["order_number"]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= e(
                                                    $payment["first_name"]
                                                    . " "
                                                    . $payment["last_name"]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= e(
                                                    $payment["order_type"]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= e(
                                                    $payment["payment_method"]
                                                ) ?>

                                            </td>


                                            <td>

                                                ₱<?= number_format(
                                                    (float)$payment["total_amount"],
                                                    2
                                                ) ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="badge
                                                    <?=
                                                        $payment["payment_status"] === "Paid"
                                                            ? "text-bg-success"
                                                            : (
                                                                $payment["payment_status"] === "Failed"
                                                                    ? "text-bg-danger"
                                                                    : "text-bg-warning"
                                                            )
                                                    ?>"
                                                >

                                                    <?= e(
                                                        $payment["payment_status"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td class="text-end pe-4">

                                                <a
                                                    href="view-order.php?id=<?= (int)$payment["order_id"] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                >

                                                    View

                                                </a>


                                                <?php

                                                $isDeliveryCash =
                                                    $payment["order_type"] === "Delivery"
                                                    &&
                                                    in_array(
                                                        $payment["payment_method"],
                                                        [
                                                            "Cash on Delivery",
                                                            "Cash"
                                                        ],
                                                        true
                                                    );

                                                ?>


                                                <?php if (
                                                    $payment["payment_status"] !== "Paid"
                                                    &&
                                                    !$isDeliveryCash
                                                ): ?>

                                                    <a
                                                        href="payments.php?order_id=<?= (int)$payment["order_id"] ?>"
                                                        class="btn btn-sm btn-staff ms-1"
                                                    >

                                                        Manage

                                                    </a>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 PAYMENT MODAL
            ================================================== -->

            <?php if ($orderId && $payments): ?>

                <?php $payment = $payments[0]; ?>


                <div
                    class="modal fade"
                    id="paymentModal"
                    tabindex="-1"
                    aria-hidden="true"
                >

                    <div class="modal-dialog modal-dialog-centered">

                        <div class="modal-content">


                            <!-- Modal Header -->

                            <div class="modal-header staff-modal-header">

                                <h5 class="modal-title">

                                    Manage Payment —
                                    <?= e($payment["order_number"]) ?>

                                </h5>


                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                ></button>

                            </div>



                            <!-- Modal Body -->

                            <div class="modal-body">


                                <!-- Total -->

                                <div class="staff-summary-row">

                                    <span>
                                        Total
                                    </span>

                                    <strong>

                                        ₱<?= number_format(
                                            (float)$payment["total_amount"],
                                            2
                                        ) ?>

                                    </strong>

                                </div>



                                <!-- Payment Method -->

                                <div class="staff-summary-row">

                                    <span>
                                        Method
                                    </span>

                                    <strong>

                                        <?= e(
                                            $payment["payment_method"]
                                        ) ?>

                                    </strong>

                                </div>



                                <!-- Payment Proof -->

                                <?php if (!empty($payment["payment_proof"])): ?>

                                    <img
                                        src="../assets/uploads/payment/<?= e($payment["payment_proof"]) ?>"
                                        class="payment-proof w-100 my-3"
                                        alt="Payment proof"
                                    >

                                <?php endif; ?>



                                <!-- =================================================
                                     PICKUP CASH PAYMENT
                                ================================================== -->

                                <?php if (
                                    $payment["order_type"] === "Pickup"
                                    &&
                                    in_array(
                                        $payment["payment_method"],
                                        [
                                            "Cash on Delivery",
                                            "Cash"
                                        ],
                                        true
                                    )
                                ): ?>


                                    <form
                                        method="POST"
                                        action="payment-action.php"
                                        class="mt-3"
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int)$payment["order_id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cash"
                                        >


                                        <label class="form-label fw-semibold">

                                            Cash Received

                                        </label>


                                        <input
                                            id="cashReceived"
                                            name="cash_received"
                                            type="number"
                                            min="<?= e($payment["total_amount"]) ?>"
                                            step="0.01"
                                            class="form-control"
                                            required
                                        >


                                        <div class="alert alert-light border mt-3 mb-0">

                                            Change:

                                            <strong>

                                                ₱<span id="changeAmount">
                                                    0.00
                                                </span>

                                            </strong>

                                        </div>


                                        <button
                                            type="submit"
                                            class="btn btn-staff w-100 mt-3"
                                        >

                                            <i class="bi bi-check-circle me-1"></i>

                                            Record Cash & Mark Paid

                                        </button>

                                    </form>



                                <!-- =================================================
                                     OTHER PAYMENT TYPES
                                ================================================== -->

                                <?php else: ?>


                                    <div class="alert alert-info mt-3">

                                        <i class="bi bi-info-circle me-1"></i>


                                        <?php if ($isDeliveryCash): ?>

                                            This delivery uses cash collection.
                                            The rider must mark it paid after delivery.

                                        <?php else: ?>

                                            Verify the payment/proof before
                                            marking this order as paid.

                                        <?php endif; ?>

                                    </div>



                                    <?php if (!$isDeliveryCash): ?>

                                        <form
                                            method="POST"
                                            action="payment-action.php"
                                        >

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?= (int)$payment["order_id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="verify"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-staff w-100"
                                            >

                                                <i class="bi bi-check-circle me-1"></i>

                                                Verify Payment & Mark Paid

                                            </button>

                                        </form>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </main>

    </div>

</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<?php if ($orderId && $payments): ?>

<script>

/*
|--------------------------------------------------------------------------
| Cash Change Calculator
|--------------------------------------------------------------------------
*/

const cashReceived = document.getElementById("cashReceived");

if (cashReceived) {

    cashReceived.addEventListener("input", function () {

        const total =
            <?= json_encode((float)$payment["total_amount"]) ?>;

        const value =
            parseFloat(cashReceived.value) || 0;

        const change =
            Math.max(0, value - total);

        document.getElementById("changeAmount")
            .textContent = change.toFixed(2);

    });

}


/*
|--------------------------------------------------------------------------
| Automatically Open Payment Modal
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| If payment was successfully updated, $message is not empty.
| Therefore the modal will NOT open again.
|
| This allows the page to show:
|
| "Payment status updated successfully."
|
| without reopening the payment modal.
|
|--------------------------------------------------------------------------
*/

<?php if (!$message && !$error): ?>

document.addEventListener("DOMContentLoaded", function () {

    const modalElement =
        document.getElementById("paymentModal");

    if (modalElement) {

        const paymentModal =
            new bootstrap.Modal(modalElement);

        paymentModal.show();

    }

});

<?php endif; ?>


/*
|--------------------------------------------------------------------------
| Close Modal When Page Is Showing Success Message
|--------------------------------------------------------------------------
|
| This is an additional safeguard.
|--------------------------------------------------------------------------
*/

<?php if ($message): ?>

document.addEventListener("DOMContentLoaded", function () {

    const modalElement =
        document.getElementById("paymentModal");

    if (modalElement) {

        const existingModal =
            bootstrap.Modal.getInstance(modalElement);

        if (existingModal) {

            existingModal.hide();

        }

    }

});

<?php endif; ?>

</script>

<?php endif; ?>

</body>

</html>