<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole('rider');

if($_SERVER['REQUEST_METHOD']!=='POST'){
    header('Location: orders.php');
    exit;
}

$id=filter_input(INPUT_POST,'order_id',FILTER_VALIDATE_INT);
$action=$_POST['action']??'';
$uid=(int)$_SESSION['user_id'];

$st=$pdo->prepare("SELECT order_status,payment_status,payment_method,proof_of_delivery
                   FROM orders WHERE order_id=? AND rider_id=? LIMIT 1");
$st->execute([$id,$uid]);
$o=$st->fetch();

if(!$o){
    header('Location: orders.php');
    exit;
}

if($action==='start' && $o['order_status']==='Ready for Delivery'){
    $st=$pdo->prepare("UPDATE orders SET order_status='Out for Delivery' WHERE order_id=? AND rider_id=? AND order_status='Ready for Delivery'");
    $st->execute([$id,$uid]);
    header("Location: view-order.php?id=$id&message=".urlencode('Delivery started.'));
    exit;
}

if($action==='complete' && $o['order_status']==='Out for Delivery'){
    if(in_array($o['payment_method'],['Cash on Delivery','Cash'],true) && $o['payment_status']!=='Paid'){
        header("Location: view-order.php?id=$id&error=".urlencode('Collect the cash payment before completing this delivery.'));
        exit;
    }

    if(empty($o['proof_of_delivery'])){
        header("Location: view-order.php?id=$id&error=".urlencode('Upload proof of delivery before completing this delivery.'));
        exit;
    }

    $st=$pdo->prepare("UPDATE orders SET order_status='Completed' WHERE order_id=? AND rider_id=? AND order_status='Out for Delivery'");
    $st->execute([$id,$uid]);
    header("Location: view-order.php?id=$id&message=".urlencode('Delivery completed successfully.'));
    exit;
}

header("Location: view-order.php?id=$id");
exit;
