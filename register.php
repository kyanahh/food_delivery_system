<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$success = "";

$first_name = "";
$last_name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($password)
    ) {

        $error = "Please complete all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } else {

        $check = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $error = "An account with this email already exists.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (first_name, last_name, email, phone, password, role)
                VALUES (?, ?, ?, ?, ?, 'customer')"
            );

            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $phone,
                $hashed_password
            ]);

            $success = "Registration successful. You can now log in.";

            $first_name = "";
            $last_name = "";
            $email = "";
            $phone = "";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Create Account | Jollibee Plaza Central</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/public.css">
</head>

<body class="jp-page">

<nav class="navbar jp-navbar">
    <div class="container">

        <a href="index.php" class="jp-brand d-flex align-items-center gap-2">
            <div style="
                    width: 70px;
                    height: 70px;
                    background: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <img src="assets/images/logo.png" alt="Logo" style="height: 45px; width: 50px; object-fit: contain;">
            </div>
            Jollibee Plaza Central
        </a>

        <a href="login.php" class="btn jp-order-btn">
            Login
        </a>

    </div>
</nav>

<div class="jp-yellow-line"></div>

<main class="jp-auth-page">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <div class="card jp-auth-card">
                    <div class="row g-0">

                        <div class="col-lg-5 d-none d-lg-flex">
                            <div class="jp-auth-side p-5 w-100 d-flex flex-column justify-content-center">

                                <div class="jp-auth-badge rounded-circle d-flex align-items-center justify-content-center mb-4">
                                    <i class="bi bi-person-plus-fill fs-1"></i>
                                </div>

                                <div class="text-uppercase small fw-bold mb-2">
                                    Jollibee Plaza Central
                                </div>

                                <h2 class="fw-bold mb-3">
                                    Start your order.
                                </h2>

                                <p class="small-line mb-4">
                                    Create your customer account to browse the menu,
                                    place orders, and keep track of your purchases.
                                </p>

                                <div class="d-flex gap-2 align-items-center small-line mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Quick registration
                                </div>

                                <div class="d-flex gap-2 align-items-center small-line mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Manage your profile
                                </div>

                                <div class="d-flex gap-2 align-items-center small-line">
                                    <i class="bi bi-check-circle-fill"></i>
                                    View your orders
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="jp-auth-form p-4 p-md-5">

                                <div class="mb-4">
                                    <div class="jp-kicker">
                                        Customer Registration
                                    </div>

                                    <h1 class="h3 fw-bold mt-2 mb-2">
                                        Create your account
                                    </h1>

                                    <p class="text-muted mb-0">
                                        Register to order from Jollibee Plaza Central.
                                    </p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger jp-alert d-flex gap-2 align-items-start">
                                        <i class="bi bi-exclamation-circle-fill mt-1"></i>
                                        <span><?= htmlspecialchars($error) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success jp-alert d-flex gap-2 align-items-start">
                                        <i class="bi bi-check-circle-fill mt-1"></i>

                                        <div>
                                            <div><?= htmlspecialchars($success) ?></div>

                                            <a href="login.php" class="btn btn-sm btn-success mt-2">
                                                Login Now
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">

                                    <div class="row">

                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label jp-form-label">
                                                First Name
                                            </label>

                                            <input
                                                type="text"
                                                id="first_name"
                                                name="first_name"
                                                class="form-control"
                                                value="<?= htmlspecialchars($first_name) ?>"
                                                placeholder="First name"
                                                required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label jp-form-label">
                                                Last Name
                                            </label>

                                            <input
                                                type="text"
                                                id="last_name"
                                                name="last_name"
                                                class="form-control"
                                                value="<?= htmlspecialchars($last_name) ?>"
                                                placeholder="Last name"
                                                required>
                                        </div>

                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label jp-form-label">
                                            Email Address
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-envelope"></i>
                                            </span>

                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                class="form-control"
                                                value="<?= htmlspecialchars($email) ?>"
                                                placeholder="Enter email address"
                                                required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label jp-form-label">
                                            Phone Number
                                            <span class="text-muted fw-normal">(Optional)</span>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-telephone"></i>
                                            </span>

                                            <input
                                                type="text"
                                                id="phone"
                                                name="phone"
                                                class="form-control"
                                                value="<?= htmlspecialchars($phone) ?>"
                                                placeholder="Enter phone number">
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label jp-form-label">
                                                Password
                                            </label>

                                            <div class="input-group">
                                                <span class="input-group-text bg-white">
                                                    <i class="bi bi-lock"></i>
                                                </span>

                                                <input
                                                    type="password"
                                                    id="password"
                                                    name="password"
                                                    class="form-control"
                                                    placeholder="Minimum 6 characters"
                                                    minlength="6"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    id="togglePassword">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <label for="confirm_password" class="form-label jp-form-label">
                                                Confirm Password
                                            </label>

                                            <div class="input-group">
                                                <span class="input-group-text bg-white">
                                                    <i class="bi bi-shield-lock"></i>
                                                </span>

                                                <input
                                                    type="password"
                                                    id="confirm_password"
                                                    name="confirm_password"
                                                    class="form-control"
                                                    placeholder="Re-enter password"
                                                    minlength="6"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    id="toggleConfirmPassword">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                    </div>

                                    <button type="submit" class="btn jp-primary-btn w-100">
                                        <i class="bi bi-person-plus-fill me-2"></i>
                                        Create Account
                                    </button>

                                </form>

                                <div class="text-center border-top mt-4 pt-4">
                                    <span class="text-muted">
                                        Already have an account?
                                    </span>

                                    <a href="login.php" class="text-decoration-none fw-bold" style="color:#e4002b;">
                                        Log in
                                    </a>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="index.php" class="jp-back-link small">
                        <i class="bi bi-arrow-left me-1"></i>
                        Back to Jollibee Plaza Central
                    </a>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
function setupPasswordToggle(buttonId, inputId) {
    const button = document.getElementById(buttonId);
    const input = document.getElementById(inputId);

    button.addEventListener("click", function () {
        const icon = this.querySelector("i");

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    });
}

setupPasswordToggle("togglePassword", "password");
setupPasswordToggle("toggleConfirmPassword", "confirm_password");
</script>

</body>
</html>
