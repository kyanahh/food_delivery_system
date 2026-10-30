<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole('rider');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$riderId = (int)$_SESSION['user_id'];

$st = $pdo->prepare("SELECT proof_of_delivery, order_status FROM orders WHERE order_id=? AND rider_id=? LIMIT 1");
$st->execute([$orderId, $riderId]);
$order = $st->fetch();

if (!$order || $order['order_status'] !== 'Out for Delivery') {
    header('Location: orders.php');
    exit;
}

if (!isset($_FILES['proof_of_delivery']) || $_FILES['proof_of_delivery']['error'] !== UPLOAD_ERR_OK) {
    header("Location: view-order.php?id=$orderId&error=" . urlencode('Please select a proof of delivery image.'));
    exit;
}

$file = $_FILES['proof_of_delivery'];
if ($file['size'] > 5 * 1024 * 1024) {
    header("Location: view-order.php?id=$orderId&error=" . urlencode('Proof of delivery must not exceed 5MB.'));
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowed[$mime])) {
    header("Location: view-order.php?id=$orderId&error=" . urlencode('Proof of delivery must be a JPG, PNG, or WEBP image.'));
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/delivery/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    header("Location: view-order.php?id=$orderId&error=" . urlencode('Unable to create the delivery upload folder.'));
    exit;
}

$filename = 'delivery_' . $orderId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
$target = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    header("Location: view-order.php?id=$orderId&error=" . urlencode('Unable to upload the proof of delivery.'));
    exit;
}

if (!empty($order['proof_of_delivery'])) {
    $old = $uploadDir . basename($order['proof_of_delivery']);
    if (is_file($old)) {
        @unlink($old);
    }
}

$st = $pdo->prepare("UPDATE orders SET proof_of_delivery=? WHERE order_id=? AND rider_id=? AND order_status='Out for Delivery'");
$st->execute([$filename, $orderId, $riderId]);

header("Location: view-order.php?id=$orderId&message=" . urlencode('Proof of delivery uploaded successfully.'));
exit;
