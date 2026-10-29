<?php

require_once "config/database.php";
require_once "includes/auth.php";

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT * FROM users
             WHERE email = ?
             AND account_status = 'active'
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            redirectByRole($user["role"]);

        } else {

            $error = "Invalid email or password.";

        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Jollibee Plaza Central</title>

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
            <span class="jp-brand-mark d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-shop"></i>
            </span>
            Jollibee Plaza Central
        </a>

        <a href="register.php" class="btn jp-order-btn">
            Create Account
        </a>

    </div>
</nav>

<div class="jp-yellow-line"></div>

<main class="jp-auth-page">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">

                <div class="card jp-auth-card">
                    <div class="row g-0">

                        <div class="col-lg-5 d-none d-lg-flex">
                            <div class="jp-auth-side p-5 w-100 d-flex flex-column justify-content-center">

                                <div class="jp-auth-badge rounded-circle d-flex align-items-center justify-content-center mb-4">
                                    <i class="bi bi-bag-heart-fill fs-1"></i>
                                </div>

                                <div class="text-uppercase small fw-bold mb-2">
                                    Jollibee Plaza Central
                                </div>

                                <h2 class="fw-bold mb-3">
                                    Welcome back!
                                </h2>

                                <p class="small-line mb-4">
                                    Log in to order your favorites, check your active orders,
                                    and manage your customer account.
                                </p>

                                <div class="d-flex gap-2 align-items-center small-line mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Easy online ordering
                                </div>

                                <div class="d-flex gap-2 align-items-center small-line mb-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Delivery or pickup
                                </div>

                                <div class="d-flex gap-2 align-items-center small-line">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Order tracking
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="jp-auth-form p-4 p-md-5">

                                <div class="mb-4">
                                    <div class="jp-kicker">
                                        Customer Login
                                    </div>

                                    <h1 class="h3 fw-bold mt-2 mb-2">
                                        Sign in to your account
                                    </h1>

                                    <p class="text-muted mb-0">
                                        Continue your order at Jollibee Plaza Central.
                                    </p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger jp-alert d-flex gap-2 align-items-start">
                                        <i class="bi bi-exclamation-circle-fill mt-1"></i>
                                        <span><?= htmlspecialchars($error) ?></span>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">

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
                                                placeholder="Enter your email"
                                                required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
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
                                                placeholder="Enter your password"
                                                required>

                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn jp-primary-btn w-100">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>
                                        Login
                                    </button>

                                </form>

                                <div class="text-center border-top mt-4 pt-4">
                                    <span class="text-muted">
                                        Don't have an account?
                                    </span>

                                    <a href="register.php" class="text-decoration-none fw-bold" style="color:#e4002b;">
                                        Create Account
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
document.getElementById("togglePassword").addEventListener("click", function () {
    const input = document.getElementById("password");
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
</script>

</body>
</html>
