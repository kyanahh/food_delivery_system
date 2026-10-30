<?php
require_once "../includes/auth.php";
requireRole("customer");
require_once "../config/database.php";

$userId = (int)($_SESSION['user_id'] ?? 0);
$success = '';
$errors = [];

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// Detect the actual users table columns so the profile page remains compatible
// with the existing database instead of assuming a different schema.
$columnStmt = $pdo->query("DESCRIBE users");
$userColumns = [];
foreach ($columnStmt->fetchAll() as $column) {
    $userColumns[$column['Field']] = true;
}

$editableMap = [
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'email' => 'Email Address',
    'phone' => 'Phone Number',
    'contact_number' => 'Contact Number',
    'address' => 'Address'
];

$editableColumns = array_values(array_intersect(array_keys($editableMap), array_keys($userColumns)));

$selectColumns = ['user_id'];
foreach ($editableColumns as $column) {
    $selectColumns[] = $column;
}
if (isset($userColumns['username'])) $selectColumns[] = 'username';
if (isset($userColumns['role'])) $selectColumns[] = 'role';

$userSql = "SELECT " . implode(', ', array_map(fn($c) => '`' . $c . '`', $selectColumns)) . " FROM users WHERE user_id = :user_id LIMIT 1";
$userStmt = $pdo->prepare($userSql);
$userStmt->execute([':user_id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $params = [':user_id' => $userId];

    foreach ($editableColumns as $column) {
        $value = trim($_POST[$column] ?? '');
        if ($column === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
            continue;
        }
        $updates[] = "`$column` = :$column";
        $params[":$column"] = $value;
    }

    if (isset($userColumns['password']) && !empty($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $updates[] = '`password` = :password';
            $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    }

    if (!$errors && $updates) {
        try {
            $updateSql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($params);

            // Keep common session values in sync for the rest of the customer pages.
            if (isset($userColumns['first_name'])) $_SESSION['first_name'] = trim($_POST['first_name'] ?? '');
            if (isset($userColumns['last_name'])) $_SESSION['last_name'] = trim($_POST['last_name'] ?? '');
            if (isset($userColumns['email'])) $_SESSION['email'] = trim($_POST['email'] ?? '');

            $success = 'Your profile has been updated successfully.';
            $userStmt->execute([':user_id' => $userId]);
            $user = $userStmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Unable to update your profile. Please check if the email or other information is already in use.';
        }
    } elseif (!$errors && !$updates) {
        $success = 'No changes were made.';
    }
}

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'Customer');
$firstName = $user['first_name'] ?? ($_SESSION['first_name'] ?? 'Customer');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile - Food Delivery System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body class="customer-page">
<nav class="navbar customer-topbar px-3 px-lg-4">
    <div class="container-fluid px-0">
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

        </a>        <div class="d-flex align-items-center gap-2">
            <a href="profile.php" class="btn btn-sm btn-light"><i class="bi bi-person me-1"></i>Profile</a>
            <a href="../logout.php" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>
<div class="container-fluid"><div class="row">
    <aside class="col-md-3 col-lg-2 customer-sidebar p-3">
        <div class="customer-profile text-center pb-3 mb-3">
            <div class="customer-avatar rounded-circle d-inline-flex align-items-center justify-content-center"><i class="bi bi-person fs-3"></i></div>
            <h6 class="mt-2 mb-0"><?= e($displayName) ?></h6><small class="text-muted">Customer</small>
        </div>
        <div class="list-group customer-nav">
            <a href="dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a href="menu.php" class="list-group-item list-group-item-action"><i class="bi bi-grid me-2"></i>Browse Menu</a>
            <a href="cart.php" class="list-group-item list-group-item-action"><i class="bi bi-cart3 me-2"></i>My Cart</a>
            <a href="orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>My Orders</a>
            <a href="order-history.php" class="list-group-item list-group-item-action"><i class="bi bi-clock-history me-2"></i>Order History</a>
            <a href="profile.php" class="list-group-item list-group-item-action active"><i class="bi bi-person me-2"></i>My Profile</a>
        </div>
    </aside>
    <main class="col-md-9 col-lg-10 customer-main p-3 p-lg-4">
        <div class="mb-4"><h2 class="fw-bold mb-1">My Profile</h2><p class="text-muted mb-0">Manage your customer information and account password.</p></div>

        <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php foreach ($errors as $error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endforeach; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card customer-card h-100"><div class="card-body text-center p-4">
                    <div class="customer-avatar rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:90px;height:90px;"><i class="bi bi-person fs-1"></i></div>
                    <h4 class="fw-bold mb-1"><?= e($displayName) ?></h4>
                    <p class="text-muted mb-3">Customer</p>
                    <?php if (!empty($user['email'])): ?><div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= e($user['email']) ?></div><?php endif; ?>
                    <div class="border-top mt-4 pt-4 text-start">
                        <a href="orders.php" class="btn btn-outline-danger w-100 mb-2"><i class="bi bi-receipt me-2"></i>My Orders</a>
                        <a href="order-history.php" class="btn btn-outline-secondary w-100"><i class="bi bi-clock-history me-2"></i>Order History</a>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-8">
                <form method="POST" class="card customer-card">
                    <div class="card-header bg-white p-4"><h5 class="fw-bold mb-1">Personal Information</h5><p class="text-muted mb-0">Update the information used for your orders.</p></div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                        <?php foreach ($editableColumns as $column): ?>
                            <div class="<?= in_array($column, ['address']) ? 'col-12' : 'col-md-6' ?>">
                                <label class="form-label fw-semibold"><?= e($editableMap[$column]) ?></label>
                                <?php if ($column === 'address'): ?>
                                    <textarea name="<?= e($column) ?>" class="form-control" rows="3"><?= e($user[$column] ?? '') ?></textarea>
                                <?php else: ?>
                                    <input type="<?= $column === 'email' ? 'email' : 'text' ?>" name="<?= e($column) ?>" class="form-control" value="<?= e($user[$column] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <?php if (isset($userColumns['password'])): ?>
                        <hr class="my-4">
                        <h5 class="fw-bold mb-1">Change Password</h5>
                        <p class="text-muted small mb-3">Leave these fields blank if you do not want to change your password.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">New Password</label><input type="password" name="new_password" class="form-control" minlength="6"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Confirm Password</label><input type="password" name="confirm_password" class="form-control" minlength="6"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white p-4 text-end"><button type="submit" class="btn btn-jollibee"><i class="bi bi-save me-1"></i>Save Changes</button></div>
                </form>
            </div>
        </div>
    </main>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
