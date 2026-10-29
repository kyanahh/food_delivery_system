<?php

require_once "../includes/auth.php";
requireRole("customer");

require_once "../config/database.php";


$foodId = $_POST["food_id"] ?? null;
$quantity = $_POST["quantity"] ?? 1;


if (!$foodId || !is_numeric($foodId)) {
    header("Location: cart.php");
    exit;
}


$quantity = (int) $quantity;


// If quantity is zero or negative, remove it
if ($quantity <= 0) {

    unset($_SESSION["cart"][$foodId]);

    header("Location: cart.php");
    exit;
}


// Make sure the food is still available
$stmt = $pdo->prepare("
    SELECT food_id
    FROM food_items
    WHERE food_id = ?
    AND status = 'available'
    LIMIT 1
");

$stmt->execute([$foodId]);

$food = $stmt->fetch();


if (!$food) {

    unset($_SESSION["cart"][$foodId]);

    header("Location: cart.php");
    exit;
}


// Update quantity
$_SESSION["cart"][$foodId] = $quantity;


header("Location: cart.php");
exit;