<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole('rider');

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=filter_input(INPUT_POST,'order_id',FILTER_VALIDATE_INT);
    $received=(float)($_POST['cash_received']??0);
    $uid=(int)$_SESSION['user_id'];

    $st=$pdo->prepare("SELECT total_amount,payment_method,payment_status,order_status,cash_received,change_amount
                       FROM orders WHERE order_id=? AND rider_id=? LIMIT 1");
    $st->execute([$id,$uid]);
    $o=$st->fetch();

    if(!$o){header('Location: orders.php');exit;}
    if(!in_array($o['payment_method'],['Cash on Delivery','Cash'],true)||$o['payment_status']==='Paid'||$o['order_status']!=='Out for Delivery'){
        header("Location: view-order.php?id=$id");exit;
    }

    if($received < (float)$o['total_amount']){
        $error='Cash received must be at least the order total.';
    }else{
        $change=$received-(float)$o['total_amount'];
        $st=$pdo->prepare("UPDATE orders SET payment_status='Paid',cash_received=?,change_amount=? WHERE order_id=? AND rider_id=? AND payment_status<>'Paid'");
        $st->execute([$received,$change,$id,$uid]);
        header("Location: view-order.php?id=$id&message=".urlencode('Cash payment recorded successfully.'));
        exit;
    }
}

$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$uid=(int)$_SESSION['user_id'];
$st=$pdo->prepare("SELECT * FROM orders WHERE order_id=? AND rider_id=? LIMIT 1");
$st->execute([$id,$uid]);
$o=$st->fetch();
if(!$o){header('Location: orders.php');exit;}

$expectedCash=(float)$o['cash_received'];
$expectedChange=(float)$o['change_amount'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Collect Payment | Rider</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/rider.css">
</head>
<body class="rider-page">
<main class="container py-5">
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card rider-card">
<div class="card-body p-4">
<h1 class="h4 fw-bold">Collect Cash Payment</h1>
<p class="text-muted">Order <?=e($o['order_number'])?></p>
<?php if(isset($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="alert alert-warning">
  <div class="small text-muted">Customer entered this amount during checkout:</div>
  <div class="fs-4 fw-bold">₱<?=number_format($expectedCash,2)?></div>
  <hr>
  <div class="small text-muted">Expected change:</div>
  <div class="fs-5 fw-bold text-success">₱<?=number_format($expectedChange,2)?></div>
</div>
<div class="d-flex justify-content-between border-bottom py-2"><span>Order Total</span><strong>₱<?=number_format((float)$o['total_amount'],2)?></strong></div>
<form method="post" class="mt-4">
<input type="hidden" name="order_id" value="<?=e($id)?>">
<label class="form-label fw-semibold">Actual Cash Received</label>
<input id="cash" name="cash_received" type="number" min="<?=e($o['total_amount'])?>" step="0.01" class="form-control form-control-lg" value="<?=e($expectedCash > 0 ? $expectedCash : '')?>" required>
<div class="alert alert-light border mt-3">Actual change: <strong>₱<span id="change"><?=number_format($expectedChange,2)?></span></strong></div>
<button class="btn btn-rider w-100"><i class="bi bi-cash-coin me-1"></i>Record Payment</button>
<a href="view-order.php?id=<?=e($id)?>" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
</form>
</div></div>
</div></div>
</main>
<script>
const c=document.getElementById('cash');
c.addEventListener('input',()=>{
  const t=<?=json_encode((float)$o['total_amount'])?>,v=parseFloat(c.value)||0;
  document.getElementById('change').textContent=Math.max(0,v-t).toFixed(2);
});
</script>
</body></html>
