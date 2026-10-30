<?php

require_once "../includes/auth.php";
requireRole("customer");

require_once "../config/database.php";

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}

$cart = $_SESSION["cart"];

$errors = [];
$success = "";

/*
|--------------------------------------------------------------------------
| Get Cart Items
|--------------------------------------------------------------------------
*/

$foodIds = [];

foreach ($cart as $foodId => $quantity) {

    if (is_numeric($foodId) && (int)$quantity > 0) {
        $foodIds[] = (int)$foodId;
    }
}

if (empty($foodIds)) {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}

$placeholders = implode(",", array_fill(0, count($foodIds), "?"));

$stmt = $pdo->prepare("
    SELECT
        food_id,
        food_name,
        description,
        price,
        image,
        status
    FROM food_items
    WHERE food_id IN ($placeholders)
    AND status = 'available'
    ORDER BY food_name ASC
");

$stmt->execute($foodIds);

$foodItems = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Validate Cart
|--------------------------------------------------------------------------
*/

if (empty($foodItems)) {

    $errors[] = "Your cart contains no valid menu items.";

} else {

    $validFoodIds = [];

    foreach ($foodItems as $item) {
        $validFoodIds[] = (int)$item["food_id"];
    }

    foreach ($foodIds as $foodId) {

        if (!in_array($foodId, $validFoodIds, true)) {

            unset($_SESSION["cart"][$foodId]);
        }
    }

    $cart = $_SESSION["cart"];

    if (empty($cart)) {
        $errors[] = "Your cart contains no valid menu items.";
    }
}

/*
|--------------------------------------------------------------------------
| Calculate Subtotal
|--------------------------------------------------------------------------
*/

$subtotal = 0;

foreach ($foodItems as $item) {

    $foodId = (int)$item["food_id"];

    $quantity = isset($cart[$foodId])
        ? (int)$cart[$foodId]
        : 0;

    if ($quantity <= 0) {
        continue;
    }

    $subtotal += ((float)$item["price"] * $quantity);
}

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$firstName = "";
$lastName = "";

$orderType = "Delivery";
$paymentMethod = "Cash";
$deliveryAddress = "";
$orderNotes = "";

$cashReceived = "";
$changeAmount = 0;

$deliveryFee = 50;
$total = $subtotal + $deliveryFee;

/*
|--------------------------------------------------------------------------
| Get Customer Name
|--------------------------------------------------------------------------
|
| We only use the session values if they exist.
| This avoids depending on a specific users table structure.
|
*/

if (isset($_SESSION["first_name"])) {
    $firstName = $_SESSION["first_name"];
}

if (isset($_SESSION["last_name"])) {
    $lastName = $_SESSION["last_name"];
}

/*
|--------------------------------------------------------------------------
| Handle Order Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors)) {

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");

    $orderType = $_POST["order_type"] ?? "Delivery";
    $paymentMethod = $_POST["payment_method"] ?? "Cash";

    $deliveryAddress = trim($_POST["delivery_address"] ?? "");
    $orderNotes = trim($_POST["order_notes"] ?? "");

    $cashReceivedInput = trim($_POST["cash_received"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validate Order Type
    |--------------------------------------------------------------------------
    */

    if (!in_array($orderType, ["Delivery", "Pickup"], true)) {
        $orderType = "Delivery";
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Payment Method
    |--------------------------------------------------------------------------
    */

    if (!in_array($paymentMethod, ["Cash", "QRPh"], true)) {
        $paymentMethod = "Cash";
    }

    /*
    |--------------------------------------------------------------------------
    | Delivery Address
    |--------------------------------------------------------------------------
    */

    if ($orderType === "Delivery" && $deliveryAddress === "") {

        $errors[] = "Please enter your complete delivery address.";
    }

    /*
    |--------------------------------------------------------------------------
    | Pickup Address
    |--------------------------------------------------------------------------
    */

    if ($orderType === "Pickup") {

        $deliveryAddress = "Pickup at Jollibee Central Plaza";
    }

    /*
    |--------------------------------------------------------------------------
    | Delivery Fee
    |--------------------------------------------------------------------------
    */

    $deliveryFee = ($orderType === "Delivery") ? 50 : 0;

    $total = $subtotal + $deliveryFee;

    /*
    |--------------------------------------------------------------------------
    | Cash Payment
    |--------------------------------------------------------------------------
    */

    if ($paymentMethod === "Cash") {

        if ($cashReceivedInput === "") {

            $errors[] = "Please enter the amount you will give to the driver/cashier.";

        } elseif (!is_numeric($cashReceivedInput)) {

            $errors[] = "Please enter a valid cash amount.";

        } else {

            $cashReceived = (float)$cashReceivedInput;

            if ($cashReceived < $total) {

                $errors[] = "The cash amount must be at least ₱"
                    . number_format($total, 2)
                    . ".";

            } else {

                $changeAmount = $cashReceived - $total;
            }
        }

    } else {

        /*
        |--------------------------------------------------------------------------
        | QRPh Payment
        |--------------------------------------------------------------------------
        */

        $cashReceived = null;
        $changeAmount = 0;

        if (
            !isset($_FILES["payment_proof"]) ||
            $_FILES["payment_proof"]["error"] !== UPLOAD_ERR_OK
        ) {

            $errors[] = "Please upload your QRPh proof of payment.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | QRPh Proof Upload
    |--------------------------------------------------------------------------
    */

    $paymentProof = null;

    if (
        $paymentMethod === "QRPh" &&
        isset($_FILES["payment_proof"]) &&
        $_FILES["payment_proof"]["error"] === UPLOAD_ERR_OK
    ) {

        $file = $_FILES["payment_proof"];

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/jpg",
            "image/webp"
        ];

        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file["type"], $allowedTypes, true)) {

            $errors[] = "Proof of payment must be a JPG, PNG, or WEBP image.";

        } elseif ($file["size"] > $maxFileSize) {

            $errors[] = "Proof of payment must not exceed 5MB.";

        } else {

            $uploadDirectory = "../assets/uploads/payment/";

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            $extension = strtolower(
                pathinfo($file["name"], PATHINFO_EXTENSION)
            );

            $uniqueName =
                "payment_" .
                $_SESSION["user_id"] .
                "_" .
                date("YmdHis") .
                "_" .
                bin2hex(random_bytes(4)) .
                "." .
                $extension;

            $targetPath = $uploadDirectory . $uniqueName;

            if (move_uploaded_file($file["tmp_name"], $targetPath)) {

                $paymentProof = $uniqueName;

            } else {

                $errors[] = "Unable to upload the proof of payment.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Place Order
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Generate Unique Order Number
            |--------------------------------------------------------------------------
            */

            do {

                $orderNumber =
                    "ORD-" .
                    date("YmdHis") .
                    "-" .
                    random_int(100, 999);

                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM orders
                    WHERE order_number = ?
                ");

                $checkStmt->execute([$orderNumber]);

                $exists = (int)$checkStmt->fetchColumn();

            } while ($exists > 0);

            /*
            |--------------------------------------------------------------------------
            | Insert Order
            |--------------------------------------------------------------------------
            */

            $insertOrder = $pdo->prepare("
                INSERT INTO orders (
                    user_id,
                    order_number,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    order_type,
                    delivery_address,
                    customer_notes,
                    cash_received,
                    change_amount,
                    payment_proof
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $insertOrder->execute([
                $_SESSION["user_id"],
                $orderNumber,
                $total,
                $paymentMethod,
                "Pending",
                "Pending",
                $orderType,
                $deliveryAddress,
                $orderNotes !== "" ? $orderNotes : null,
                $paymentMethod === "Cash" ? $cashReceived : null,
                $paymentMethod === "Cash" ? $changeAmount : 0,
                $paymentProof
            ]);

            $orderId = $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Insert Order Items
            |--------------------------------------------------------------------------
            */

            $insertItem = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    food_id,
                    quantity,
                    unit_price,
                    subtotal
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            foreach ($foodItems as $item) {

                $foodId = (int)$item["food_id"];

                if (!isset($cart[$foodId])) {
                    continue;
                }

                $quantity = (int)$cart[$foodId];

                if ($quantity <= 0) {
                    continue;
                }

                $unitPrice = (float)$item["price"];

                $itemSubtotal = $unitPrice * $quantity;

                $insertItem->execute([
                    $orderId,
                    $foodId,
                    $quantity,
                    $unitPrice,
                    $itemSubtotal
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | Clear Cart
            |--------------------------------------------------------------------------
            */

            $_SESSION["cart"] = [];

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header(
                "Location: order-success.php?id=" .
                urlencode($orderId)
            );

            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                "Unable to place your order. " .
                $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body class="customer-page">

<nav class="navbar navbar-dark customer-topbar shadow-sm">
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
            <span class="ms-2 fw-bold">
                Jollibee Central Plaza
            </span>

        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="cart.php" class="btn btn-light btn-sm">
                <i class="bi bi-cart3 me-1"></i>Cart
            </a>
            <span class="text-white customer-user-name">
                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($firstName) ?>
            </span>
            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 customer-sidebar p-3">
            <div class="customer-profile text-center pb-3 mb-3">
                <div class="customer-avatar rounded-circle d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-person fs-3"></i>
                </div>
                <h6 class="mt-2 mb-0"><?= htmlspecialchars($firstName) ?></h6>
                <small class="text-muted">Customer</small>
            </div>
            <div class="list-group customer-nav">
                <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                <a href="menu.php" class="list-group-item list-group-item-action"><i class="bi bi-grid me-2"></i>Browse Menu</a>
                <a href="cart.php" class="list-group-item list-group-item-action active"><i class="bi bi-cart3 me-2"></i>My Cart</a>
                <a href="orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>My Orders</a>
                <a href="order-history.php" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Order History</a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </aside>

        <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Checkout</h2>
                <p class="text-muted mb-0">Confirm your order details before placing your order.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger customer-card border-0">
                    <?php foreach ($errors as $error): ?>
                        <div class="mb-1">
                            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="checkoutForm">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card customer-card">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold customer-checkout-title">
                                    <i class="bi bi-clipboard-check text-jollibee me-2"></i>Order Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">First Name</label>
                                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Order Type</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="order_type" id="delivery" value="Delivery" <?= $orderType === "Delivery" ? "checked" : "" ?>>
                                            <label class="btn btn-outline-danger w-100 py-2" for="delivery">
                                                <i class="bi bi-truck me-1"></i>Delivery
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="order_type" id="pickup" value="Pickup" <?= $orderType === "Pickup" ? "checked" : "" ?>>
                                            <label class="btn btn-outline-danger w-100 py-2" for="pickup">
                                                <i class="bi bi-shop me-1"></i>Pickup
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4" id="deliveryAddressContainer">
                                    <label class="form-label fw-semibold">Delivery Address</label>
                                    <textarea name="delivery_address" id="deliveryAddress" class="form-control" rows="3" placeholder="Enter your complete delivery address"><?= htmlspecialchars($deliveryAddress) ?></textarea>
                                    <div class="form-text">Please provide a complete and accurate address for delivery.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Payment Method</label>
                                    <select name="payment_method" id="paymentMethod" class="form-select" required>
                                        <option value="Cash" <?= $paymentMethod === "Cash" ? "selected" : "" ?>>Cash</option>
                                        <option value="QRPh" <?= $paymentMethod === "QRPh" ? "selected" : "" ?>>QRPh</option>
                                    </select>
                                </div>

                                <div id="cashPaymentContainer" class="border rounded-3 p-3 mb-4 bg-light">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-cash-coin text-jollibee fs-4 me-2"></i>
                                        <div>
                                            <div class="fw-bold">Cash Payment</div>
                                            <small class="text-muted">Enter the amount you will give.</small>
                                        </div>
                                    </div>
                                    <label class="form-label fw-semibold">Cash Amount</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="cash_received" id="cashReceived" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars((string)$cashReceived) ?>" placeholder="Enter amount">
                                    </div>
                                    <small class="text-muted">Give this amount to the <?= $orderType === "Pickup" ? "cashier" : "driver" ?>.</small>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Total</span>
                                            <strong id="cashTotal">₱<?= number_format($total, 2) ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span>Change</span>
                                            <strong id="changeDisplay" class="text-success">₱0.00</strong>
                                        </div>
                                    </div>
                                </div>

                                <div id="qrPaymentContainer" class="border rounded-3 p-3 mb-4 text-center" style="display:none;">
                                    <div class="d-flex align-items-center justify-content-center mb-3">
                                        <i class="bi bi-qr-code text-jollibee fs-4 me-2"></i>
                                        <h6 class="fw-bold mb-0">Pay via QRPh</h6>
                                    </div>
                                    <img src="../assets/uploads/payment/qrph.png"
                                         alt="QRPh Payment QR Code"
                                         class="img-fluid border rounded-3 p-2 mb-3"
                                         style="max-width:220px;"
                                         onerror="this.style.display='none'; document.getElementById('qrMissing').style.display='block';">
                                    <div id="qrMissing" class="alert alert-warning text-start" style="display:none;">
                                        Please place your QRPh QR code at <strong>assets/uploads/payment/qrph.png</strong>.
                                    </div>
                                    <div class="text-start">
                                        <label class="form-label fw-semibold">Upload Proof of Payment</label>
                                        <input type="file" name="payment_proof" id="paymentProof" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">JPG, PNG, or WEBP. Maximum 5MB.</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label fw-semibold">Order Notes <span class="text-muted fw-normal">(Optional)</span></label>
                                    <textarea name="order_notes" class="form-control" rows="3" placeholder="Any special instructions for your order?"><?= htmlspecialchars($orderNotes) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card customer-card mt-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-bag-check text-jollibee me-2"></i>Order Items</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($foodItems as $item): ?>
                                    <?php
                                    $foodId = (int)$item["food_id"];
                                    $quantity = (int)($cart[$foodId] ?? 0);
                                    if ($quantity <= 0) continue;
                                    $itemTotal = (float)$item["price"] * $quantity;
                                    ?>
                                    <div class="p-3 customer-order-item">
                                        <div class="d-flex justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($item["food_name"]) ?></div>
                                                <small class="text-muted">₱<?= number_format((float)$item["price"], 2) ?> × <?= $quantity ?></small>
                                            </div>
                                            <strong class="text-jollibee">₱<?= number_format($itemTotal, 2) ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card customer-card customer-sticky">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-receipt text-jollibee me-2"></i>Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span>₱<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Delivery Fee</span>
                                    <span id="deliveryFeeDisplay">₱<?= number_format($deliveryFee, 2) ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Total</strong>
                                    <strong class="text-jollibee fs-4" id="totalDisplay">₱<?= number_format($total, 2) ?></strong>
                                </div>

                                <div class="alert alert-light border mt-3 mb-0 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Your order will be submitted after you click Place Order.
                                </div>

                                <button type="submit" class="btn btn-jollibee w-100 mt-4 py-3 fw-semibold">
                                    <i class="bi bi-check-circle me-2"></i>Place Order
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="bi bi-arrow-left me-1"></i>Back to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const deliveryRadio = document.getElementById("delivery");
    const pickupRadio = document.getElementById("pickup");
    const paymentMethod = document.getElementById("paymentMethod");
    const deliveryAddressContainer = document.getElementById("deliveryAddressContainer");
    const deliveryAddress = document.getElementById("deliveryAddress");
    const cashPaymentContainer = document.getElementById("cashPaymentContainer");
    const qrPaymentContainer = document.getElementById("qrPaymentContainer");
    const cashReceived = document.getElementById("cashReceived");
    const changeDisplay = document.getElementById("changeDisplay");
    const cashTotal = document.getElementById("cashTotal");
    const deliveryFeeDisplay = document.getElementById("deliveryFeeDisplay");
    const totalDisplay = document.getElementById("totalDisplay");
    const paymentProof = document.getElementById("paymentProof");
    const subtotal = <?= json_encode((float)$subtotal) ?>;

    function updateCheckout() {
        const isDelivery = deliveryRadio.checked;
        const fee = isDelivery ? 50 : 0;
        const total = subtotal + fee;

        deliveryAddressContainer.style.display = isDelivery ? "block" : "none";
        deliveryAddress.required = isDelivery;

        if (!isDelivery) {
            deliveryAddress.value = "";
        }

        deliveryFeeDisplay.textContent = "₱" + fee.toFixed(2);
        totalDisplay.textContent = "₱" + total.toFixed(2);
        cashTotal.textContent = "₱" + total.toFixed(2);

        if (paymentMethod.value === "Cash") {
            cashPaymentContainer.style.display = "block";
            qrPaymentContainer.style.display = "none";
            cashReceived.required = true;
            paymentProof.required = false;
        } else {
            cashPaymentContainer.style.display = "none";
            qrPaymentContainer.style.display = "block";
            cashReceived.required = false;
            paymentProof.required = true;
        }

        calculateChange(total);
    }

    function calculateChange(total) {
        const amount = parseFloat(cashReceived.value) || 0;

        changeDisplay.classList.remove("text-success", "text-danger");

        if (amount >= total && amount > 0) {
            changeDisplay.textContent = "₱" + (amount - total).toFixed(2);
            changeDisplay.classList.add("text-success");
        } else {
            changeDisplay.textContent = "₱0.00";
            if (amount > 0) {
                changeDisplay.classList.add("text-danger");
            }
        }
    }

    deliveryRadio.addEventListener("change", updateCheckout);
    pickupRadio.addEventListener("change", updateCheckout);
    paymentMethod.addEventListener("change", updateCheckout);

    cashReceived.addEventListener("input", function () {
        const fee = deliveryRadio.checked ? 50 : 0;
        calculateChange(subtotal + fee);
    });

    updateCheckout();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
