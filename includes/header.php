<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $_SESSION['first_name'] ?? $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jollibee Central Plaza</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Custom CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="bg-light">

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-jollibee shadow-sm">

    <div class="container-fluid px-3">

        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >
            <i class="fa-solid fa-store me-2"></i>
            Jollibee Central Plaza
        </a>

        <div class="d-flex align-items-center gap-3">

            <a
                href="cart.php"
                class="btn btn-light"
            >
                <i class="fa-solid fa-cart-shopping me-1"></i>
                Cart
            </a>

            <span class="text-white">

                <i class="fa-solid fa-circle-user me-1"></i>

                <?= htmlspecialchars($userName) ?>

            </span>

        </div>

    </div>

</nav>