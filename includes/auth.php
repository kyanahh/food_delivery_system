<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Require Specific Role
|--------------------------------------------------------------------------
*/

function requireRole($role)
{
    requireLogin();

    if ($_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Redirect User Based on Role
|--------------------------------------------------------------------------
*/

function redirectByRole($role)
{
    switch ($role) {

        case 'admin':
            header("Location: admin/dashboard.php");
            break;

        case 'staff':
            header("Location: staff/dashboard.php");
            break;

        case 'rider':
            header("Location: rider/dashboard.php");
            break;

        case 'customer':
            header("Location: customer/dashboard.php");
            break;

        default:
            header("Location: index.php");
            break;
    }

    exit;
}