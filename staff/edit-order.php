<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$orderId) {

    header("Location: orders.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone
    FROM orders o
    INNER JOIN users u
        ON u.user_id = o.user_id
    WHERE o.order_id = ?
    LIMIT 1
");

$stmt->execute([$orderId]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    header("Location: orders.php?error=" . urlencode(
        "Order not found."
    ));

    exit;

}


/*
|--------------------------------------------------------------------------
| COMPLETED / CANCELLED ORDERS CANNOT BE EDITED
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $order["order_status"],
        ["Completed", "Cancelled"],
        true
    )
) {

    header("Location: view-order.php?id=" . $orderId .
        "&error=" . urlencode(
            "Completed or cancelled orders cannot be edited."
        )
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| SAVE ORDER CHANGES
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quantities =
        $_POST["quantity"] ?? [];

    $foodIdsToAdd =
        $_POST["add_food_id"] ?? [];

    $addQuantities =
        $_POST["add_quantity"] ?? [];


    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | CURRENT ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                oi.order_item_id,
                oi.food_id,
                oi.quantity,
                oi.unit_price
            FROM order_items oi
            WHERE oi.order_id = ?
        ");

        $stmt->execute([$orderId]);

        $currentItems =
            $stmt->fetchAll(PDO::FETCH_ASSOC);


        $total = 0;


        /*
        |--------------------------------------------------------------------------
        | UPDATE / REMOVE EXISTING ITEMS
        |--------------------------------------------------------------------------
        */

        foreach ($currentItems as $item) {

            $itemId =
                (int) $item["order_item_id"];

            $foodId =
                (int) $item["food_id"];

            $quantity =
                isset($quantities[$itemId])
                    ? (int) $quantities[$itemId]
                    : 0;


            /*
            |--------------------------------------------------------------------------
            | REMOVE ITEM
            |--------------------------------------------------------------------------
            */

            if ($quantity <= 0) {

                $delete = $pdo->prepare("
                    DELETE FROM order_items
                    WHERE order_item_id = ?
                      AND order_id = ?
                ");

                $delete->execute([
                    $itemId,
                    $orderId
                ]);

                continue;

            }


            /*
            |--------------------------------------------------------------------------
            | GET CURRENT MENU PRICE
            |--------------------------------------------------------------------------
            */

            $foodStmt = $pdo->prepare("
                SELECT
                    food_id,
                    price,
                    status
                FROM food_items
                WHERE food_id = ?
                LIMIT 1
            ");

            $foodStmt->execute([
                $foodId
            ]);

            $food = $foodStmt->fetch(
                PDO::FETCH_ASSOC
            );


            if (!$food) {

                throw new Exception(
                    "A food item in this order no longer exists."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE ITEM
            |--------------------------------------------------------------------------
            */

            $unitPrice =
                (float) $food["price"];

            $subtotal =
                $unitPrice * $quantity;


            $update = $pdo->prepare("
                UPDATE order_items
                SET
                    quantity = ?,
                    unit_price = ?,
                    subtotal = ?
                WHERE order_item_id = ?
                  AND order_id = ?
            ");

            $update->execute([
                $quantity,
                $unitPrice,
                $subtotal,
                $itemId,
                $orderId
            ]);


            $total += $subtotal;

        }


        /*
        |--------------------------------------------------------------------------
        | ADD NEW ITEMS
        |--------------------------------------------------------------------------
        */

        if (is_array($foodIdsToAdd)) {

            foreach (
                $foodIdsToAdd
                as $index => $foodId
            ) {

                $foodId =
                    (int) $foodId;

                $quantity =
                    isset($addQuantities[$index])
                        ? (int) $addQuantities[$index]
                        : 0;


                if (
                    $foodId <= 0 ||
                    $quantity <= 0
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | CHECK FOOD
                |--------------------------------------------------------------------------
                */

                $foodStmt = $pdo->prepare("
                    SELECT
                        food_id,
                        food_name,
                        price,
                        status
                    FROM food_items
                    WHERE food_id = ?
                    LIMIT 1
                ");

                $foodStmt->execute([
                    $foodId
                ]);

                $food = $foodStmt->fetch(
                    PDO::FETCH_ASSOC
                );


                if (!$food) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | ONLY AVAILABLE FOOD
                |--------------------------------------------------------------------------
                */

                if (
                    strtolower(
                        (string) $food["status"]
                    ) !== "available"
                ) {
                    continue;
                }


                $unitPrice =
                    (float) $food["price"];

                $subtotal =
                    $unitPrice * $quantity;


                /*
                |--------------------------------------------------------------------------
                | CHECK IF ITEM ALREADY EXISTS
                |--------------------------------------------------------------------------
                */

                $existingStmt = $pdo->prepare("
                    SELECT
                        order_item_id,
                        quantity
                    FROM order_items
                    WHERE order_id = ?
                      AND food_id = ?
                    LIMIT 1
                ");

                $existingStmt->execute([
                    $orderId,
                    $foodId
                ]);

                $existing =
                    $existingStmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if ($existing) {

                    $newQuantity =
                        (int) $existing["quantity"]
                        + $quantity;


                    $newSubtotal =
                        $unitPrice *
                        $newQuantity;


                    $update = $pdo->prepare("
                        UPDATE order_items
                        SET
                            quantity = ?,
                            unit_price = ?,
                            subtotal = ?
                        WHERE order_item_id = ?
                          AND order_id = ?
                    ");

                    $update->execute([
                        $newQuantity,
                        $unitPrice,
                        $newSubtotal,
                        $existing["order_item_id"],
                        $orderId
                    ]);

                } else {

                    $insert = $pdo->prepare("
                        INSERT INTO order_items
                        (
                            order_id,
                            food_id,
                            quantity,
                            unit_price,
                            subtotal
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");

                    $insert->execute([
                        $orderId,
                        $foodId,
                        $quantity,
                        $unitPrice,
                        $subtotal
                    ]);

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | RECALCULATE TOTAL FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $totalStmt = $pdo->prepare("
            SELECT
                COALESCE(
                    SUM(subtotal),
                    0
                )
            FROM order_items
            WHERE order_id = ?
        ");

        $totalStmt->execute([
            $orderId
        ]);

        $total =
            (float) $totalStmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER TOTAL
        |--------------------------------------------------------------------------
        */

        $updateOrder = $pdo->prepare("
            UPDATE orders
            SET
                total_amount = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE order_id = ?
        ");

        $updateOrder->execute([
            $total,
            $orderId
        ]);


        $pdo->commit();


        header(
            "Location: view-order.php?id=" .
            $orderId .
            "&message=" .
            urlencode(
                "Order updated successfully."
            )
        );

        exit;

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }


        $error =
            $e->getMessage();

    }

}


/*
|--------------------------------------------------------------------------
| GET CURRENT ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        oi.order_item_id,
        oi.food_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        fi.food_name,
        fi.status
    FROM order_items oi
    INNER JOIN food_items fi
        ON fi.food_id = oi.food_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");

$stmt->execute([
    $orderId
]);

$items =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET AVAILABLE FOOD
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        food_id,
        food_name,
        price
    FROM food_items
    WHERE status = 'available'
    ORDER BY food_name ASC
");

$availableFoods =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Edit Order | Staff
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
        href="../assets/css/staff.css"
    >

</head>


<body class="staff-page">


<!-- TOPBAR -->

<nav class="navbar staff-topbar">

    <div
        class="container-fluid px-3 px-lg-4"
    >

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


        <div
            class="d-flex align-items-center gap-3 text-white"
        >

            <span class="staff-user-name small">

                <i class="bi bi-person-circle me-1"></i>

                <?= e(
                    $_SESSION["first_name"] ?? "Staff"
                ) ?>

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


        <!-- SIDEBAR -->

        <aside
            class="col-lg-2 p-0 staff-sidebar"
        >

            <div class="p-3">

                <div
                    class="staff-profile p-3 mb-3"
                >

                    <div
                        class="d-flex align-items-center gap-3"
                    >

                        <div
                            class="staff-avatar rounded-circle d-flex align-items-center justify-content-center"
                        >

                            <i
                                class="bi bi-person-fill fs-4"
                            ></i>

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


                <div
                    class="list-group staff-nav"
                >

                    <a
                        href="dashboard.php"
                        class="list-group-item"
                    >
                        <i
                            class="bi bi-speedometer2 me-2"
                        ></i>
                        Dashboard
                    </a>


                    <a
                        href="orders.php"
                        class="list-group-item active"
                    >
                        <i
                            class="bi bi-receipt me-2"
                        ></i>
                        Orders
                    </a>


                    <a
                        href="orders.php?status=Pending"
                        class="list-group-item"
                    >
                        <i
                            class="bi bi-bell me-2"
                        ></i>
                        Incoming Orders
                    </a>


                    <a
                        href="payments.php"
                        class="list-group-item"
                    >
                        <i
                            class="bi bi-cash-stack me-2"
                        ></i>
                        Payment Monitoring
                    </a>

                </div>

            </div>

        </aside>


        <!-- MAIN -->

        <main
            class="col-lg-10 staff-main p-3 p-lg-4"
        >


            <div
                class="d-flex justify-content-between align-items-center mb-4"
            >

                <div>

                    <h1 class="h3 fw-bold mb-1">

                        Edit Order

                    </h1>

                    <p class="text-muted mb-0">

                        Order
                        <strong>
                            <?= e(
                                $order["order_number"]
                            ) ?>
                        </strong>

                    </p>

                </div>


                <a
                    href="view-order.php?id=<?= $orderId ?>"
                    class="btn btn-outline-secondary"
                >

                    <i
                        class="bi bi-arrow-left me-1"
                    ></i>

                    Back to Order

                </a>

            </div>


            <?php if (!empty($error)): ?>

                <div
                    class="alert alert-danger"
                >

                    <i
                        class="bi bi-exclamation-triangle me-2"
                    ></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <!-- CUSTOMER INFO -->

            <div
                class="card staff-card mb-4"
            >

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <small class="text-muted">
                                Customer
                            </small>

                            <div class="fw-semibold">

                                <?= e(
                                    $order["first_name"] .
                                    " " .
                                    $order["last_name"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                Order Type
                            </small>

                            <div class="fw-semibold">

                                <?= e(
                                    $order["order_type"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                Current Status
                            </small>

                            <div class="fw-semibold">

                                <?= e(
                                    $order["order_status"]
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- EDIT FORM -->

            <form
                method="POST"
                id="editOrderForm"
            >

                <div
                    class="card staff-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <h2 class="h5 fw-bold mb-0">

                            <i
                                class="bi bi-cart3 me-2"
                            ></i>

                            Order Items

                        </h2>

                    </div>


                    <div class="card-body p-0">

                        <div
                            class="table-responsive"
                        >

                            <table
                                class="table align-middle mb-0"
                            >

                                <thead
                                    class="table-light"
                                >

                                    <tr>

                                        <th class="ps-4">
                                            Food Item
                                        </th>

                                        <th>
                                            Availability
                                        </th>

                                        <th>
                                            Unit Price
                                        </th>

                                        <th
                                            style="width:130px"
                                        >
                                            Quantity
                                        </th>

                                        <th class="text-end pe-4">
                                            Subtotal
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $items
                                        as $item
                                    ): ?>

                                        <tr>

                                            <td class="ps-4">

                                                <div class="fw-semibold">

                                                    <?= e(
                                                        $item["food_name"]
                                                    ) ?>

                                                </div>

                                            </td>


                                            <td>

                                                <?php if (
                                                    strtolower(
                                                        $item["status"]
                                                    ) === "available"
                                                ): ?>

                                                    <span
                                                        class="badge text-bg-success"
                                                    >
                                                        Available
                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="badge text-bg-danger"
                                                    >
                                                        Unavailable
                                                    </span>

                                                    <small
                                                        class="text-danger d-block mt-1"
                                                    >
                                                        Set quantity to 0
                                                        to remove.
                                                    </small>

                                                <?php endif; ?>

                                            </td>


                                            <td>

                                                ₱<?= number_format(
                                                    (float)
                                                    $item["unit_price"],
                                                    2
                                                ) ?>

                                            </td>


                                            <td>

                                                <input
                                                    type="number"
                                                    name="quantity[<?= (int) $item["order_item_id"] ?>]"
                                                    class="form-control"
                                                    min="0"
                                                    value="<?= (int) $item["quantity"] ?>"
                                                >

                                            </td>


                                            <td
                                                class="text-end pe-4 fw-semibold"
                                            >

                                                ₱<?= number_format(
                                                    (float)
                                                    $item["subtotal"],
                                                    2
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <?php if (!$items): ?>

                                        <tr>

                                            <td
                                                colspan="5"
                                                class="text-center py-4 text-muted"
                                            >

                                                No items in this order.

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- ADD ITEM -->

                <div
                    class="card staff-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <h2 class="h5 fw-bold mb-0">

                            <i
                                class="bi bi-plus-circle me-2"
                            ></i>

                            Add Replacement / Additional Item

                        </h2>

                    </div>


                    <div class="card-body">

                        <div
                            id="additionalItems"
                        >

                            <div
                                class="row g-2 mb-2 additional-item"
                            >

                                <div class="col-md-7">

                                    <select
                                        name="add_food_id[]"
                                        class="form-select"
                                    >

                                        <option value="">
                                            Select food item
                                        </option>


                                        <?php foreach (
                                            $availableFoods
                                            as $food
                                        ): ?>

                                            <option
                                                value="<?= (int) $food["food_id"] ?>"
                                            >

                                                <?= e(
                                                    $food["food_name"]
                                                ) ?>

                                                —
                                                ₱<?= number_format(
                                                    (float)
                                                    $food["price"],
                                                    2
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <div class="col-md-3">

                                    <input
                                        type="number"
                                        name="add_quantity[]"
                                        class="form-control"
                                        min="1"
                                        value="1"
                                        placeholder="Quantity"
                                    >

                                </div>


                                <div class="col-md-2">

                                    <button
                                        type="button"
                                        class="btn btn-outline-danger w-100 remove-additional"
                                    >

                                        <i
                                            class="bi bi-trash"
                                        ></i>

                                    </button>

                                </div>

                            </div>

                        </div>


                        <button
                            type="button"
                            id="addItemButton"
                            class="btn btn-outline-primary btn-sm"
                        >

                            <i
                                class="bi bi-plus-lg me-1"
                            ></i>

                            Add Another Item

                        </button>

                    </div>

                </div>


                <!-- WARNING -->

                <div
                    class="alert alert-warning"
                >

                    <i
                        class="bi bi-exclamation-triangle me-2"
                    ></i>

                    <strong>Important:</strong>

                    If a customer ordered an item that is unavailable,
                    set its quantity to <strong>0</strong> to remove it,
                    then add an available replacement if needed.

                    The order total will be recalculated automatically.

                </div>


                <!-- SAVE -->

                <div
                    class="d-flex justify-content-end gap-2"
                >

                    <a
                        href="view-order.php?id=<?= $orderId ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>


                    <button
                        type="button"
                        class="btn btn-staff"
                        data-bs-toggle="modal"
                        data-bs-target="#saveModal"
                    >

                        <i
                            class="bi bi-check-lg me-1"
                        ></i>

                        Save Order Changes

                    </button>

                </div>


                <!-- CONFIRMATION MODAL -->

                <div
                    class="modal fade"
                    id="saveModal"
                    tabindex="-1"
                    aria-hidden="true"
                >

                    <div
                        class="modal-dialog modal-dialog-centered"
                    >

                        <div class="modal-content">

                            <div class="modal-header">

                                <h5
                                    class="modal-title fw-bold"
                                >
                                    Confirm Order Changes
                                </h5>

                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                ></button>

                            </div>


                            <div class="modal-body">

                                Are you sure you want to update
                                this customer's order?

                                <div
                                    class="alert alert-warning mt-3 mb-0"
                                >

                                    <i
                                        class="bi bi-info-circle me-1"
                                    ></i>

                                    The order total will be
                                    recalculated based on the
                                    updated items.

                                </div>

                            </div>


                            <div class="modal-footer">

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal"
                                >
                                    Cancel
                                </button>


                                <button
                                    type="submit"
                                    class="btn btn-staff"
                                >

                                    <i
                                        class="bi bi-check-lg me-1"
                                    ></i>

                                    Yes, Update Order

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

document
    .getElementById("addItemButton")
    .addEventListener(
        "click",
        function () {

            const container =
                document.getElementById(
                    "additionalItems"
                );

            const first =
                container.querySelector(
                    ".additional-item"
                );

            const clone =
                first.cloneNode(true);


            clone
                .querySelector(
                    "select"
                )
                .value = "";


            clone
                .querySelector(
                    "input"
                )
                .value = 1;


            container.appendChild(
                clone
            );

        }
    );


document.addEventListener(
    "click",
    function (event) {

        if (
            event.target.closest(
                ".remove-additional"
            )
        ) {

            const rows =
                document.querySelectorAll(
                    ".additional-item"
                );


            if (rows.length > 1) {

                event.target
                    .closest(
                        ".additional-item"
                    )
                    .remove();

            }

        }

    }
);

</script>

</body>

</html>