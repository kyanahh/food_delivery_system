<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole("rider");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: available.php');
    exit;
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$riderId = (int)$_SESSION['user_id'];

if (!$orderId) {
    header('Location: available.php?error=' . urlencode('Invalid delivery order.'));
    exit;
}

// Atomic claim: only the first rider who updates the unassigned order wins.
$st = $pdo->prepare("UPDATE orders
    SET rider_id = ?
    WHERE order_id = ?
      AND order_type = 'Delivery'
      AND order_status = 'Ready for Delivery'
      AND rider_id IS NULL");
$st->execute([$riderId, $orderId]);

if ($st->rowCount() === 1) {
    header('Location: view-order.php?id=' . $orderId . '&message=' . urlencode('Delivery accepted successfully.'));
    exit;
}

header('Location: available.php?error=' . urlencode('This delivery was already accepted by another rider. Please choose another available delivery.'));
exit;
