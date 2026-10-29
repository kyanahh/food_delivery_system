<?php

require_once "../includes/auth.php";
requireRole("customer");

require_once "../config/database.php";


$foodId = $_POST["food_id"] ?? null;
$quantity = $_POST["quantity"] ?? 1;


if (!$foodId || !is_numeric($foodId)) {
    header("Location: menu.php");
    exit;
}


$quantity = (int) $quantity;

if ($quantity < 1) {
    $quantity = 1;
}


// Verify that food exists and is available
$stmt = $pdo->prepare("
    SELECT
        food_id,
        food_name,
        price
    FROM food_items
    WHERE food_id = ?
    AND status = 'available'
    LIMIT 1
");

$stmt->execute([$foodId]);

$food = $stmt->fetch();


if (!$food) {
    header("Location: menu.php?error=food_unavailable");
    exit;
}


// Create cart if it does not exist
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


// Add quantity if item already exists
if (isset($_SESSION["cart"][$foodId])) {

    $_SESSION["cart"][$foodId] += $quantity;

} else {

    $_SESSION["cart"][$foodId] = $quantity;

}


header("Location: cart.php?success=added");
exit;