<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


/*
|--------------------------------------------------------------------------
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: payments.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Request Data
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);

$action = trim(
    $_POST["action"] ?? ""
);


if (!$orderId) {

    header("Location: payments.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        order_id,
        total_amount,
        payment_method,
        payment_status,
        order_type
    FROM orders
    WHERE order_id = ?
    LIMIT 1
");

$stmt->execute([$orderId]);

$order = $stmt->fetch();


if (!$order) {

    header("Location: payments.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Already Paid
|--------------------------------------------------------------------------
*/

if ($order["payment_status"] === "Paid") {

    $message =
        "Payment is already marked as paid.";

    header(
        "Location: payments.php?order_id="
        . $orderId
        . "&message="
        . urlencode($message)
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Delivery Cash Payment
|--------------------------------------------------------------------------
|
| Staff must NOT mark delivery COD/Cash as paid.
|
| The rider will collect the money and update
| the payment status after delivery.
|--------------------------------------------------------------------------
*/

$isDeliveryCash =
    $order["order_type"] === "Delivery"
    &&
    in_array(
        $order["payment_method"],
        [
            "Cash on Delivery",
            "Cash"
        ],
        true
    );


if ($isDeliveryCash) {

    $error =
        "Delivery cash payments must be collected and marked paid by the rider after delivery.";

    header(
        "Location: payments.php?order_id="
        . $orderId
        . "&error="
        . urlencode($error)
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| CASH PAYMENT
|--------------------------------------------------------------------------
*/

if ($action === "cash") {

    $received =
        (float)($_POST["cash_received"] ?? 0);

    $total =
        (float)$order["total_amount"];


    /*
    |--------------------------------------------------------------------------
    | Validate Cash
    |--------------------------------------------------------------------------
    */

    if ($received < $total) {

        $error =
            "Cash received must be at least the order total.";

        header(
            "Location: payments.php?order_id="
            . $orderId
            . "&error="
            . urlencode($error)
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Change
    |--------------------------------------------------------------------------
    */

    $change =
        $received - $total;


    /*
    |--------------------------------------------------------------------------
    | Update Payment
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE orders
        SET
            payment_status = 'Paid',
            cash_received = ?,
            change_amount = ?
        WHERE order_id = ?
    ");

    $stmt->execute([
        $received,
        $change,
        $orderId
    ]);

}


/*
|--------------------------------------------------------------------------
| VERIFY QR / MANUAL PAYMENT
|--------------------------------------------------------------------------
*/

elseif ($action === "verify") {

    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'Paid'
        WHERE order_id = ?
    ");

    $stmt->execute([
        $orderId
    ]);

}


/*
|--------------------------------------------------------------------------
| FAILED PAYMENT
|--------------------------------------------------------------------------
*/

elseif ($action === "failed") {

    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'Failed'
        WHERE order_id = ?
    ");

    $stmt->execute([
        $orderId
    ]);

}


/*
|--------------------------------------------------------------------------
| INVALID ACTION
|--------------------------------------------------------------------------
*/

else {

    $error =
        "Invalid payment action.";

    header(
        "Location: payments.php?order_id="
        . $orderId
        . "&error="
        . urlencode($error)
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
|
| Redirect back to Payment Monitoring.
|
| The success message is passed through the URL.
|
| payments.php detects $message and DOES NOT
| automatically reopen the payment modal.
|--------------------------------------------------------------------------
*/

$message =
    "Payment status updated successfully.";

header(
    "Location: payments.php?order_id="
    . $orderId
    . "&message="
    . urlencode($message)
);

exit;

?>