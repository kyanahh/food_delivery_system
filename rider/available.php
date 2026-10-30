<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
requireRole("rider");

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$sql = "SELECT o.order_id, o.order_number, o.total_amount, o.payment_method, o.cash_received,
               o.change_amount, o.delivery_address, o.created_at,
               u.first_name, u.last_name
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        WHERE o.order_type = 'Delivery'
          AND o.order_status = 'Ready for Delivery'
          AND o.rider_id IS NULL
        ORDER BY o.created_at ASC";
$st = $pdo->query($sql);
$orders = $st->fetchAll();

$message = trim($_GET['message'] ?? '');
$error = trim($_GET['error'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Available Deliveries | Rider</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/rider.css">
</head>
<body class="rider-page">
<nav class="navbar rider-topbar">
  <div class="container-fluid px-3 px-lg-4">
    <a href="dashboard.php" class="rider-brand"><i class="bi bi-bicycle me-3"></i>Jollibee Plaza Central</a>
    <div class="d-flex align-items-center gap-3 text-white">
      <span class="small"><i class="bi bi-person-circle me-1"></i><?=e($_SESSION['first_name'] ?? 'Rider')?></span>
      <a href="../logout.php" class="btn btn-sm btn-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row">
<aside class="col-lg-2 p-0 rider-sidebar">
  <div class="p-3">
    <div class="rider-profile p-3 mb-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rider-avatar rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-bicycle fs-4"></i></div>
        <div>
          <div class="fw-bold"><?=e(trim(($_SESSION['first_name']??'').' '.($_SESSION['last_name']??'')))?></div>
          <small class="text-muted">Delivery Rider</small>
        </div>
      </div>
    </div>
    <div class="list-group rider-nav">
      <a href="dashboard.php" class="list-group-item"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a href="available.php" class="list-group-item active"><i class="bi bi-lightning-charge me-2"></i>Available Deliveries</a>
      <a href="orders.php?status=Ready%20for%20Delivery" class="list-group-item"><i class="bi bi-box-seam me-2"></i>My Deliveries</a>
      <a href="orders.php?status=Out%20for%20Delivery" class="list-group-item"><i class="bi bi-truck me-2"></i>Out for Delivery</a>
      <a href="orders.php?status=Completed" class="list-group-item"><i class="bi bi-check-circle me-2"></i>Delivery History</a>
    </div>
  </div>
</aside>

<main class="col-lg-10 rider-main p-3 p-lg-4">
  <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
    <div>
      <h1 class="h3 fw-bold mb-1">Available Deliveries</h1>
      <p class="text-muted mb-0">Claim a delivery before another rider gets it.</p>
    </div>
    <button class="btn btn-outline-secondary" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
  </div>

  <?php if($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?=e($message)?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?=e($error)?></div><?php endif; ?>

  <div class="alert alert-light border rider-card">
    <i class="bi bi-info-circle text-rider me-2"></i>
    <strong>First rider to accept gets the order.</strong> Once you claim a delivery, it will be removed from the available list for other riders.
  </div>

  <div class="row g-3">
    <?php if(!$orders): ?>
      <div class="col-12">
        <div class="card rider-card">
          <div class="card-body text-center py-5">
            <i class="bi bi-bicycle display-5 text-muted"></i>
            <h2 class="h5 fw-bold mt-3">No deliveries available</h2>
            <p class="text-muted mb-3">New orders marked Ready for Delivery will appear here.</p>
            <button class="btn btn-rider" onclick="window.location.reload()">Check Again</button>
          </div>
        </div>
      </div>
    <?php else: foreach($orders as $o): ?>
      <div class="col-xl-6">
        <div class="card rider-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <div class="fw-bold text-rider"><?=e($o['order_number'])?></div>
                <div class="small text-muted"><?=date('M d, Y h:i A', strtotime($o['created_at']))?></div>
              </div>
              <span class="badge text-bg-warning">Ready for Delivery</span>
            </div>
            <div class="mb-3">
              <div class="fw-semibold"><?=e($o['first_name'].' '.$o['last_name'])?></div>
              <div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1"></i><?=e($o['delivery_address'])?></div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6"><div class="border rounded p-2 h-100"><small class="text-muted d-block">Payment</small><strong><?=e($o['payment_method'])?></strong></div></div>
              <div class="col-6"><div class="border rounded p-2 h-100"><small class="text-muted d-block">Order Total</small><strong>₱<?=number_format((float)$o['total_amount'],2)?></strong></div></div>
            </div>
            <?php if(in_array($o['payment_method'], ['Cash','Cash on Delivery'], true)): ?>
              <div class="alert alert-warning py-2 mb-3">
                <div><strong>Customer will give:</strong> ₱<?=number_format((float)$o['cash_received'],2)?></div>
                <div><strong>Expected change:</strong> ₱<?=number_format((float)$o['change_amount'],2)?></div>
              </div>
            <?php endif; ?>
            <form method="post" action="claim-order.php" class="claim-form">
              <input type="hidden" name="order_id" value="<?=e($o['order_id'])?>">
              <button type="submit" class="btn btn-rider w-100"><i class="bi bi-lightning-charge me-1"></i>Accept Delivery</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
