<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("staff");


/* =========================
   REQUEST VALIDATION
   ========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: orders.php");
    exit;

}


$id = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);

$new = trim(
    $_POST["order_status"] ?? ""
);


if (!$id || !$new) {

    header("Location: orders.php");
    exit;

}


/* =========================
   GET CURRENT ORDER
   ========================= */

$s = $pdo->prepare(
    "SELECT
        order_status,
        order_type
     FROM orders
     WHERE order_id = ?
     LIMIT 1"
);

$s->execute([$id]);

$o = $s->fetch();


if (!$o) {

    header("Location: orders.php");
    exit;

}


/* =========================
   ALLOWED STATUS TRANSITIONS
   ========================= */

$allowed = [

    "Pending" => [
        "Confirmed",
        "Cancelled"
    ],

    "Confirmed" => [
        "Preparing",
        "Cancelled"
    ],

    "Preparing" => [
        $o["order_type"] === "Pickup"
            ? "Ready for Pickup"
            : "Ready for Delivery"
    ],

    "Ready for Delivery" => [],

    "Ready for Pickup" => [],

    "Out for Delivery" => [],

    "Completed" => [],

    "Cancelled" => []

];


/* =========================
   VALIDATE STATUS TRANSITION
   ========================= */

if (
    !isset($allowed[$o["order_status"]]) ||
    !in_array(
        $new,
        $allowed[$o["order_status"]],
        true
    )
) {

    header(
        "Location: view-order.php?id=$id&error=" .
        urlencode("Invalid order status transition.")
    );

    exit;
}


/* =========================
   UPDATE ORDER STATUS
   ========================= */

$s = $pdo->prepare(
    "UPDATE orders
     SET order_status = ?
     WHERE order_id = ?"
);

$s->execute([
    $new,
    $id
]);


/* =========================
   SUCCESS REDIRECT
   ========================= */

header(
    "Location: view-order.php?id=$id&message=" .
    urlencode("Order status updated successfully.")
);

exit;