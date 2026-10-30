<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Handle Account Status
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = trim($_POST["action"] ?? "");
    $userId = filter_input(
        INPUT_POST,
        "user_id",
        FILTER_VALIDATE_INT
    );

    if (!$userId) {
        header("Location: users.php?error=" . urlencode("Invalid user."));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Edit User Information
    |--------------------------------------------------------------------------
    */

    if ($action === "edit") {

        $firstName = trim($_POST["first_name"] ?? "");
        $lastName  = trim($_POST["last_name"] ?? "");
        $email     = trim($_POST["email"] ?? "");
        $phone     = trim($_POST["phone"] ?? "");
        $newRole   = trim($_POST["role"] ?? "");
        $password  = $_POST["password"] ?? "";

        if ($firstName === "" || $lastName === "" || $email === "") {
            header("Location: users.php?error=" . urlencode(
                "First name, last name, and email are required."
            ));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: users.php?error=" . urlencode(
                "Please enter a valid email address."
            ));
            exit;
        }

        $allowedEditRoles = ["customer", "staff", "rider", "admin"];

        // An admin can edit their own details, but cannot change their own role.
        if ($userId === $adminId) {
            $newRole = "admin";
        } elseif (!in_array($newRole, $allowedEditRoles, true)) {
            header("Location: users.php?error=" . urlencode("Invalid user role."));
            exit;
        }

        if ($password !== "" && strlen($password) < 6) {
            header("Location: users.php?error=" . urlencode(
                "Password must be at least 6 characters."
            ));
            exit;
        }

        // Prevent duplicate email addresses.
        $emailCheck = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = ?
              AND user_id <> ?
            LIMIT 1
        ");
        $emailCheck->execute([$email, $userId]);

        if ($emailCheck->fetch()) {
            header("Location: users.php?error=" . urlencode(
                "That email address is already being used."
            ));
            exit;
        }

        if ($password !== "") {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("
                UPDATE users
                SET
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    role = ?,
                    password = ?
                WHERE user_id = ?
                LIMIT 1
            ");

            $update->execute([
                $firstName,
                $lastName,
                $email,
                $phone !== "" ? $phone : null,
                $newRole,
                $hashedPassword,
                $userId
            ]);
        } else {
            $update = $pdo->prepare("
                UPDATE users
                SET
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    role = ?
                WHERE user_id = ?
                LIMIT 1
            ");

            $update->execute([
                $firstName,
                $lastName,
                $email,
                $phone !== "" ? $phone : null,
                $newRole,
                $userId
            ]);
        }

        header("Location: users.php?message=" . urlencode(
            "User information updated successfully."
        ));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Prevent Admin From Changing Their Own Status
    |--------------------------------------------------------------------------
    */

    if ($userId === $adminId) {
        header(
            "Location: users.php?error=" .
            urlencode("You cannot change your own account status.")
        );
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Activate / Deactivate User
    |--------------------------------------------------------------------------
    */

    if ($action === "activate" || $action === "deactivate") {

        $newStatus = $action === "activate"
            ? "active"
            : "inactive";

        $st = $pdo->prepare("
            UPDATE users
            SET account_status = ?
            WHERE user_id = ?
            LIMIT 1
        ");

        $st->execute([
            $newStatus,
            $userId
        ]);

        $message = $newStatus === "active"
            ? "User account activated successfully."
            : "User account deactivated successfully.";

        header(
            "Location: users.php?message=" .
            urlencode($message)
        );
        exit;
    }

    header(
        "Location: users.php?error=" .
        urlencode("Invalid action.")
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$role = trim($_GET["role"] ?? "");
$status = trim($_GET["status"] ?? "");

$allowedRoles = [
    "customer",
    "staff",
    "rider",
    "admin"
];

$allowedStatuses = [
    "active",
    "inactive"
];

/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        phone,
        role,
        account_status,
        created_at
    FROM users
    WHERE 1=1
";

$params = [];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "
        AND (
            first_name LIKE ?
            OR last_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

if (in_array($role, $allowedRoles, true)) {

    $sql .= " AND role = ?";

    $params[] = $role;
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (in_array($status, $allowedStatuses, true)) {

    $sql .= " AND account_status = ?";

    $params[] = $status;
}

$sql .= "
    ORDER BY created_at DESC
";

$st = $pdo->prepare($sql);
$st->execute($params);

$users = $st->fetchAll();

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = trim($_GET["message"] ?? "");
$error = trim($_GET["error"] ?? "");

/*
|--------------------------------------------------------------------------
| User Counts
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_users,

        SUM(role = 'customer') AS customers,

        SUM(role = 'staff') AS staff,

        SUM(role = 'rider') AS riders,

        SUM(role = 'admin') AS admins,

        SUM(account_status = 'active') AS active_users,

        SUM(account_status = 'inactive') AS inactive_users

    FROM users
");

$counts = $countStmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Jollibee Central Plaza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>

    :root {
        --jollibee-red: #d71920;
        --jollibee-dark-red: #b51218;
    }
    body { background: #f8f9fa; }
    .bg-jollibee { background-color: var(--jollibee-red) !important; }
    .btn-jollibee {
        background-color: var(--jollibee-red);
        border-color: var(--jollibee-red);
        color: #fff;
    }
    .btn-jollibee:hover {
        background-color: var(--jollibee-dark-red);
        border-color: var(--jollibee-dark-red);
        color: #fff;
    }
    .text-jollibee { color: var(--jollibee-red) !important; }
    .sidebar { min-height: calc(100vh - 56px); }
    .sidebar .nav-link {
        color: #343a40;
        border-radius: .5rem;
        margin-bottom: .25rem;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background: var(--jollibee-red);
        color: #fff;
    }
    .summary-card {
        border: 0;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    }
    .summary-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(215, 25, 32, .1);
        color: var(--jollibee-red);
        font-size: 1.35rem;
    }
    .table th { white-space: nowrap; }

    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-jollibee shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            Jollibee Central Plaza <span class="fw-normal">| Admin</span>
        </a>
        <div class="d-flex align-items-center gap-3 text-white">
            <span>
                <i class="bi bi-person-circle me-1"></i>
                <?= e(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </span>
            <a href="../logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
<aside class="col-md-3 col-lg-2 bg-white border-end sidebar p-3">
        <div class="text-center py-3 mb-3 border-bottom">
            <div class="text-jollibee fs-1">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="fw-bold">
                <?= e(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </div>
            <small class="text-muted">Administrator</small>
        </div>
        <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link active" href="users.php">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a class="nav-link" href="menu.php">
                    <i class="bi bi-grid me-2"></i>Menu Management
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-receipt me-2"></i>Orders
                </a>
                <a class="nav-link" href="sales.php">
                    <i class="bi bi-bar-chart-line me-2"></i>Sales Reports
                </a>
                <a class="nav-link" href="forecast.php">
                    <i class="bi bi-graph-up-arrow me-2"></i>Demand Forecasting
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear me-2"></i>System Settings
                </a>
        </nav>
    </aside>
        <main class="col-md-9 col-lg-10 p-4">


            <!-- Header -->

            <div class="mb-4">

                <h1 class="h3 fw-bold mb-1">
                    User Management
                </h1>

                <p class="text-muted mb-0">
                    Manage customer, staff, rider, and administrator accounts.
                </p>

            </div>


            <!-- =================================================
                 ALERTS
            ================================================== -->

            <?php if ($message): ?>

                <div class="alert alert-success">

                    <i class="bi bi-check-circle me-2"></i>

                    <?= e($message) ?>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-circle me-2"></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SUMMARY CARDS
            ================================================== -->

            <div class="row g-3 mb-4">

                <div class="col-6 col-xl-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <div class="text-muted small">
                                Total Users
                            </div>

                            <div class="fs-4 fw-bold">
                                <?= (int) ($counts["total_users"] ?? 0) ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <div class="text-muted small">
                                Customers
                            </div>

                            <div class="fs-4 fw-bold">
                                <?= (int) ($counts["customers"] ?? 0) ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <div class="text-muted small">
                                Staff & Riders
                            </div>

                            <div class="fs-4 fw-bold">

                                <?= (int) ($counts["staff"] ?? 0) +
                                    (int) ($counts["riders"] ?? 0) ?>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-xl-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <div class="text-muted small">
                                Active Accounts
                            </div>

                            <div class="fs-4 fw-bold text-success">
                                <?= (int) ($counts["active_users"] ?? 0) ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SEARCH & FILTER
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <form
                        method="get"
                        class="row g-2"
                    >

                        <div class="col-lg-5">

                            <label class="form-label small fw-semibold">
                                Search Users
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Name, email, or phone"
                                value="<?= e($search) ?>"
                            >

                        </div>


                        <div class="col-lg-3">

                            <label class="form-label small fw-semibold">
                                Role
                            </label>

                            <select
                                name="role"
                                class="form-select"
                            >

                                <option value="">
                                    All Roles
                                </option>

                                <?php foreach ($allowedRoles as $itemRole): ?>

                                    <option
                                        value="<?= e($itemRole) ?>"
                                        <?= $role === $itemRole ? "selected" : "" ?>
                                    >
                                        <?= ucfirst(e($itemRole)) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-lg-2">

                            <label class="form-label small fw-semibold">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="">
                                    All Status
                                </option>

                                <?php foreach ($allowedStatuses as $itemStatus): ?>

                                    <option
                                        value="<?= e($itemStatus) ?>"
                                        <?= $status === $itemStatus ? "selected" : "" ?>
                                    >
                                        <?= ucfirst(e($itemStatus)) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-lg-2 d-flex align-items-end gap-2">

                            <button
                                type="submit"
                                class="btn btn-danger flex-grow-1"
                            >

                                <i class="bi bi-search me-1"></i>

                                Search

                            </button>

                            <a
                                href="users.php"
                                class="btn btn-outline-secondary"
                            >
                                Clear
                            </a>

                        </div>

                    </form>

                </div>

            </div>


            <!-- =================================================
                 USER TABLE
            ================================================== -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h2 class="h5 fw-bold mb-1">
                                User Accounts
                            </h2>

                            <div class="text-muted small">

                                <?= count($users) ?> user(s) displayed

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th class="px-3">
                                        User
                                    </th>

                                    <th>
                                        Contact
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Registered
                                    </th>

                                    <th class="text-end px-3">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (!$users): ?>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center py-5 text-muted"
                                        >

                                            <i
                                                class="bi bi-people fs-2 d-block mb-2"
                                            ></i>

                                            No users found.

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($users as $user): ?>

                                        <?php
                                        $isCurrentAdmin =
                                            (int) $user["user_id"] === $adminId;

                                        $fullName =
                                            trim(
                                                $user["first_name"] .
                                                " " .
                                                $user["last_name"]
                                            );
                                        ?>

                                        <tr>

                                            <!-- User -->

                                            <td class="px-3">

                                                <div class="fw-semibold">
                                                    <?= e($fullName) ?>
                                                </div>

                                                <div class="text-muted small">
                                                    ID #<?= (int) $user["user_id"] ?>
                                                </div>

                                            </td>


                                            <!-- Contact -->

                                            <td>

                                                <div>
                                                    <?= e($user["email"]) ?>
                                                </div>

                                                <div class="text-muted small">

                                                    <?= e(
                                                        $user["phone"] ?: "No phone"
                                                    ) ?>

                                                </div>

                                            </td>


                                            <!-- Role -->

                                            <td>

                                                <?php
                                                $roleClass = match ($user["role"]) {
                                                    "admin" => "text-bg-danger",
                                                    "staff" => "text-bg-primary",
                                                    "rider" => "text-bg-warning",
                                                    default => "text-bg-secondary"
                                                };
                                                ?>

                                                <span
                                                    class="badge <?= $roleClass ?>"
                                                >

                                                    <?= ucfirst(
                                                        e($user["role"])
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- Status -->

                                            <td>

                                                <?php if (
                                                    $user["account_status"] === "active"
                                                ): ?>

                                                    <span
                                                        class="badge text-bg-success"
                                                    >

                                                        Active

                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="badge text-bg-secondary"
                                                    >

                                                        Inactive

                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- Registered -->

                                            <td>

                                                <span class="small">

                                                    <?= date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $user["created_at"]
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- Action -->

                                            <td class="text-end px-3">

                                                <div class="d-flex justify-content-end gap-2">

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editUserModal<?= (int) $user["user_id"] ?>"
                                                    >
                                                        <i class="bi bi-pencil-square me-1"></i>
                                                        Edit
                                                    </button>

                                                    <?php if ($isCurrentAdmin): ?>

                                                        <span class="text-muted small align-self-center">
                                                            Current Account
                                                        </span>

                                                    <?php elseif ($user["account_status"] === "active"): ?>

                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <input type="hidden" name="user_id" value="<?= (int) $user["user_id"] ?>">

                                                            <button
                                                                type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Deactivate this user account?');"
                                                            >
                                                                <i class="bi bi-person-x me-1"></i>
                                                                Deactivate
                                                            </button>
                                                        </form>

                                                    <?php else: ?>

                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="action" value="activate">
                                                            <input type="hidden" name="user_id" value="<?= (int) $user["user_id"] ?>">

                                                            <button
                                                                type="submit"
                                                                class="btn btn-sm btn-outline-success"
                                                            >
                                                                <i class="bi bi-person-check me-1"></i>
                                                                Activate
                                                            </button>
                                                        </form>

                                                    <?php endif; ?>

                                                </div>

                                                <!-- Edit User Modal -->
                                                <div
                                                    class="modal fade text-start"
                                                    id="editUserModal<?= (int) $user["user_id"] ?>"
                                                    tabindex="-1"
                                                    aria-hidden="true"
                                                >
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow">

                                                            <form method="post">

                                                                <div class="modal-header bg-jollibee text-white">
                                                                    <div>
                                                                        <h5 class="modal-title fw-bold mb-1">
                                                                            <i class="bi bi-pencil-square me-2"></i>
                                                                            Edit User Information
                                                                        </h5>
                                                                        <small>
                                                                            User ID #<?= (int) $user["user_id"] ?>
                                                                        </small>
                                                                    </div>

                                                                    <button
                                                                        type="button"
                                                                        class="btn-close btn-close-white"
                                                                        data-bs-dismiss="modal"
                                                                    ></button>
                                                                </div>

                                                                <div class="modal-body">

                                                                    <input type="hidden" name="action" value="edit">
                                                                    <input
                                                                        type="hidden"
                                                                        name="user_id"
                                                                        value="<?= (int) $user["user_id"] ?>"
                                                                    >

                                                                    <div class="row g-3">

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                First Name
                                                                            </label>
                                                                            <input
                                                                                type="text"
                                                                                name="first_name"
                                                                                class="form-control"
                                                                                value="<?= e($user["first_name"]) ?>"
                                                                                required
                                                                            >
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                Last Name
                                                                            </label>
                                                                            <input
                                                                                type="text"
                                                                                name="last_name"
                                                                                class="form-control"
                                                                                value="<?= e($user["last_name"]) ?>"
                                                                                required
                                                                            >
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                Email
                                                                            </label>
                                                                            <input
                                                                                type="email"
                                                                                name="email"
                                                                                class="form-control"
                                                                                value="<?= e($user["email"]) ?>"
                                                                                required
                                                                            >
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                Phone
                                                                            </label>
                                                                            <input
                                                                                type="text"
                                                                                name="phone"
                                                                                class="form-control"
                                                                                value="<?= e($user["phone"] ?? "") ?>"
                                                                            >
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                Role
                                                                            </label>

                                                                            <?php if ($isCurrentAdmin): ?>

                                                                                <input type="hidden" name="role" value="admin">

                                                                                <input
                                                                                    type="text"
                                                                                    class="form-control"
                                                                                    value="Admin"
                                                                                    disabled
                                                                                >

                                                                                <div class="form-text">
                                                                                    You cannot change your own role.
                                                                                </div>

                                                                            <?php else: ?>

                                                                                <select
                                                                                    name="role"
                                                                                    class="form-select"
                                                                                    required
                                                                                >
                                                                                    <?php foreach ($allowedRoles as $editRole): ?>
                                                                                        <option
                                                                                            value="<?= e($editRole) ?>"
                                                                                            <?= $user["role"] === $editRole ? "selected" : "" ?>
                                                                                        >
                                                                                            <?= ucfirst(e($editRole)) ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>

                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label class="form-label fw-semibold">
                                                                                New Password
                                                                            </label>

                                                                            <input
                                                                                type="password"
                                                                                name="password"
                                                                                class="form-control"
                                                                                minlength="6"
                                                                                placeholder="Leave blank to keep current password"
                                                                            >

                                                                            <div class="form-text">
                                                                                Optional. Minimum 6 characters if changing.
                                                                            </div>
                                                                        </div>

                                                                    </div>

                                                                </div>

                                                                <div class="modal-footer">

                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        data-bs-dismiss="modal"
                                                                    >
                                                                        Cancel
                                                                    </button>

                                                                    <button
                                                                        type="submit"
                                                                        class="btn btn-jollibee"
                                                                    >
                                                                        <i class="bi bi-check-circle me-1"></i>
                                                                        Save Changes
                                                                    </button>

                                                                </div>

                                                            </form>

                                                        </div>
                                                    </div>
                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
