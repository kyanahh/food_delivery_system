<?php

require_once "config/database.php";

session_start();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

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
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Create Account | Food Delivery System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card border-0 shadow">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h3 class="fw-bold text-jollibee">
                            Create Account
                        </h3>

                        <p class="text-muted">
                            Online Food Delivery System
                        </p>

                    </div>

                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($success): ?>

                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    First Name
                                </label>

                                <input
                                    type="text"
                                    name="first_name"
                                    class="form-control"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Last Name
                                </label>

                                <input
                                    type="text"
                                    name="last_name"
                                    class="form-control"
                                    required>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                required>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-jollibee w-100">

                            Create Account

                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <span class="text-muted">
                            Already have an account?
                        </span>

                        <a
                            href="login.php"
                            class="text-jollibee text-decoration-none fw-semibold">

                            Log in

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>