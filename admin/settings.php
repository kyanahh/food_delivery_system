<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| GET CURRENT SETTINGS
|--------------------------------------------------------------------------
*/

$settings = [];

$stmt = $pdo->query("
    SELECT setting_key, setting_value
    FROM system_settings
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $settings[$row["setting_key"]] = $row["setting_value"];

}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$defaults = [

    "business_name" => "Jollibee Plaza Central",

    "business_address" =>
        "GF, Plaza, Central Bldg, Katihan Rd, Brgy Poblacion, Muntinlupa, Metro Manila",

    "business_contact" =>
        "(02) 8851 6362",

    "business_email" =>
        "",

    "system_name" =>
        "Online Food Delivery System",

    "system_version" =>
        "1.0.0",

    "currency" =>
        "PHP",

    "delivery_enabled" =>
        "1",

    "pickup_enabled" =>
        "1",

    "minimum_order_amount" =>
        "0.00",

    "delivery_fee" =>
        "0.00",

    "new_order_notification" =>
        "1",

    "payment_notification" =>
        "1",

    "order_status_notification" =>
        "1"

];


foreach ($defaults as $key => $value) {

    if (!isset($settings[$key])) {

        $settings[$key] = $value;

    }

}


/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/

$message = $_GET["message"] ?? "";
$error = $_GET["error"] ?? "";


/*
|--------------------------------------------------------------------------
| SAVE SETTINGS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    if ($action === "save_settings") {

        $businessName =
            trim($_POST["business_name"] ?? "");

        $businessAddress =
            trim($_POST["business_address"] ?? "");

        $businessContact =
            trim($_POST["business_contact"] ?? "");

        $businessEmail =
            trim($_POST["business_email"] ?? "");

        $systemName =
            trim($_POST["system_name"] ?? "");

        $systemVersion =
            trim($_POST["system_version"] ?? "");

        $currency =
            trim($_POST["currency"] ?? "PHP");


        $deliveryEnabled =
            isset($_POST["delivery_enabled"])
                ? "1"
                : "0";

        $pickupEnabled =
            isset($_POST["pickup_enabled"])
                ? "1"
                : "0";


        $minimumOrderAmount =
            trim($_POST["minimum_order_amount"] ?? "0");

        $deliveryFee =
            trim($_POST["delivery_fee"] ?? "0");


        $newOrderNotification =
            isset($_POST["new_order_notification"])
                ? "1"
                : "0";

        $paymentNotification =
            isset($_POST["payment_notification"])
                ? "1"
                : "0";

        $orderStatusNotification =
            isset($_POST["order_status_notification"])
                ? "1"
                : "0";


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            $businessName === "" ||
            $businessAddress === "" ||
            $businessContact === "" ||
            $systemName === ""
        ) {

            header(
                "Location: settings.php?error=" .
                urlencode(
                    "Please complete all required business and system information."
                )
            );

            exit;

        }


        if (
            $businessEmail !== "" &&
            !filter_var(
                $businessEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            header(
                "Location: settings.php?error=" .
                urlencode(
                    "Please enter a valid business email address."
                )
            );

            exit;

        }


        if (
            !is_numeric($minimumOrderAmount) ||
            (float) $minimumOrderAmount < 0
        ) {

            header(
                "Location: settings.php?error=" .
                urlencode(
                    "Minimum order amount must be a valid amount."
                )
            );

            exit;

        }


        if (
            !is_numeric($deliveryFee) ||
            (float) $deliveryFee < 0
        ) {

            header(
                "Location: settings.php?error=" .
                urlencode(
                    "Delivery fee must be a valid amount."
                )
            );

            exit;

        }


        /*
        |--------------------------------------------------------------------------
        | SETTINGS TO SAVE
        |--------------------------------------------------------------------------
        */

        $newSettings = [

            "business_name" =>
                $businessName,

            "business_address" =>
                $businessAddress,

            "business_contact" =>
                $businessContact,

            "business_email" =>
                $businessEmail,

            "system_name" =>
                $systemName,

            "system_version" =>
                $systemVersion,

            "currency" =>
                $currency,

            "delivery_enabled" =>
                $deliveryEnabled,

            "pickup_enabled" =>
                $pickupEnabled,

            "minimum_order_amount" =>
                number_format(
                    (float) $minimumOrderAmount,
                    2,
                    ".",
                    ""
                ),

            "delivery_fee" =>
                number_format(
                    (float) $deliveryFee,
                    2,
                    ".",
                    ""
                ),

            "new_order_notification" =>
                $newOrderNotification,

            "payment_notification" =>
                $paymentNotification,

            "order_status_notification" =>
                $orderStatusNotification

        ];


        /*
        |--------------------------------------------------------------------------
        | UPDATE / INSERT SETTINGS
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        try {

            $update = $pdo->prepare("
                INSERT INTO system_settings
                    (
                        setting_key,
                        setting_value
                    )
                VALUES
                    (
                        ?,
                        ?
                    )
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value)
            ");


            foreach (
                $newSettings
                as $key => $value
            ) {

                $update->execute([
                    $key,
                    $value
                ]);

            }


            $pdo->commit();


            header(
                "Location: settings.php?message=" .
                urlencode(
                    "System settings updated successfully."
                )
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            header(
                "Location: settings.php?error=" .
                urlencode(
                    "Unable to save the system settings."
                )
            );

            exit;

        }

    }

}


/*
|--------------------------------------------------------------------------
| REFRESH SETTINGS AFTER SAVE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT setting_key, setting_value
    FROM system_settings
");

$settings = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $settings[$row["setting_key"]] =
        $row["setting_value"];

}


foreach ($defaults as $key => $value) {

    if (!isset($settings[$key])) {

        $settings[$key] = $value;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        System Settings | Jollibee Central Plaza Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        :root {

            --jollibee-red: #d71920;

            --jollibee-dark-red: #b51218;

        }


        body {

            background: #f8f9fa;

        }


        .bg-jollibee {

            background-color:
                var(--jollibee-red) !important;

        }


        .btn-jollibee {

            background-color:
                var(--jollibee-red);

            border-color:
                var(--jollibee-red);

            color: #fff;

        }


        .btn-jollibee:hover {

            background-color:
                var(--jollibee-dark-red);

            border-color:
                var(--jollibee-dark-red);

            color: #fff;

        }


        .text-jollibee {

            color:
                var(--jollibee-red) !important;

        }


        .sidebar {

            min-height:
                calc(100vh - 56px);

        }


        .sidebar .nav-link {

            color: #343a40;

            border-radius: .5rem;

            margin-bottom: .25rem;

        }


        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {

            background:
                var(--jollibee-red);

            color: #fff;

        }


        .settings-card {

            border: 0;

            box-shadow:
                0 .125rem .25rem
                rgba(0, 0, 0, .075);

        }


        .section-title {

            font-weight: 700;

            color: #212529;

        }


        .section-icon {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                rgba(215, 25, 32, .1);

            color:
                var(--jollibee-red);

            font-size: 1.2rem;

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-dark bg-jollibee shadow-sm">

    <div class="container-fluid">


        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >

            Jollibee Central Plaza | Admin

        </a>


        <div
            class="d-flex align-items-center gap-3 text-white"
        >

            <span>

                <i
                    class="bi bi-person-circle me-1"
                ></i>

                <?= e(
                    ($_SESSION["first_name"] ?? "") .
                    " " .
                    ($_SESSION["last_name"] ?? "")
                ) ?>

            </span>


            <a
                href="../logout.php"
                class="btn btn-light btn-sm"
            >

                Logout

            </a>

        </div>

    </div>

</nav>


<div class="container-fluid">

    <div class="row">


        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <aside
            class="col-md-3 col-lg-2 bg-white border-end sidebar p-3"
        >


            <div
                class="text-center py-3 mb-3 border-bottom"
            >

                <div class="text-jollibee fs-1">

                    <i
                        class="bi bi-shield-lock-fill"
                    ></i>

                </div>


                <div class="fw-bold">

                    <?= e(
                        ($_SESSION["first_name"] ?? "") .
                        " " .
                        ($_SESSION["last_name"] ?? "")
                    ) ?>

                </div>


                <small class="text-muted">

                    Administrator

                </small>

            </div>


            <nav class="nav flex-column">


                <a
                    class="nav-link"
                    href="dashboard.php"
                >

                    <i
                        class="bi bi-speedometer2 me-2"
                    ></i>

                    Dashboard

                </a>


                <a
                    class="nav-link"
                    href="users.php"
                >

                    <i
                        class="bi bi-people me-2"
                    ></i>

                    User Management

                </a>


                <a
                    class="nav-link"
                    href="menu.php"
                >

                    <i
                        class="bi bi-grid me-2"
                    ></i>

                    Menu Management

                </a>


                <a
                    class="nav-link"
                    href="orders.php"
                >

                    <i
                        class="bi bi-receipt me-2"
                    ></i>

                    Orders

                </a>


                <a
                    class="nav-link"
                    href="sales.php"
                >

                    <i
                        class="bi bi-bar-chart-line me-2"
                    ></i>

                    Sales Reports

                </a>


                <a
                    class="nav-link"
                    href="forecast.php"
                >

                    <i
                        class="bi bi-graph-up-arrow me-2"
                    ></i>

                    Demand Forecasting

                </a>


                <a
                    class="nav-link active"
                    href="settings.php"
                >

                    <i
                        class="bi bi-gear me-2"
                    ></i>

                    System Settings

                </a>


            </nav>

        </aside>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <main
            class="col-md-9 col-lg-10 p-4"
        >


            <!-- HEADER -->

            <div class="mb-4">

                <h3 class="fw-bold mb-1">

                    System Settings

                </h3>


                <p class="text-muted mb-0">

                    Manage business, ordering, notification,
                    and system configuration.

                </p>

            </div>


            <!-- =================================================
                 ALERTS
            ================================================== -->

            <?php if ($message): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i
                        class="bi bi-check-circle me-2"
                    ></i>

                    <?= e($message) ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <i
                        class="bi bi-exclamation-triangle me-2"
                    ></i>

                    <?= e($error) ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SETTINGS FORM
            ================================================== -->

            <form
                method="POST"
                action="settings.php"
            >

                <input
                    type="hidden"
                    name="action"
                    value="save_settings"
                >


                <!-- =================================================
                     BUSINESS INFORMATION
                ================================================== -->

                <div
                    class="card settings-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <div
                            class="d-flex align-items-center gap-3"
                        >

                            <div class="section-icon">

                                <i
                                    class="bi bi-building"
                                ></i>

                            </div>


                            <div>

                                <h5
                                    class="section-title mb-0"
                                >

                                    Business Information

                                </h5>


                                <small class="text-muted">

                                    Information displayed
                                    throughout the system.

                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="card-body">

                        <div class="row g-3">


                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Business Name
                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="business_name"
                                    class="form-control"
                                    value="<?= e(
                                        $settings["business_name"]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Contact Number
                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="business_contact"
                                    class="form-control"
                                    value="<?= e(
                                        $settings["business_contact"]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-12">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Business Address
                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <textarea
                                    name="business_address"
                                    class="form-control"
                                    rows="2"
                                    required
                                ><?= e(
                                    $settings["business_address"]
                                ) ?></textarea>

                            </div>


                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Business Email

                                </label>


                                <input
                                    type="email"
                                    name="business_email"
                                    class="form-control"
                                    value="<?= e(
                                        $settings["business_email"]
                                    ) ?>"
                                    placeholder="business@example.com"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SYSTEM INFORMATION
                ================================================== -->

                <div
                    class="card settings-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <div
                            class="d-flex align-items-center gap-3"
                        >

                            <div class="section-icon">

                                <i
                                    class="bi bi-info-circle"
                                ></i>

                            </div>


                            <div>

                                <h5
                                    class="section-title mb-0"
                                >

                                    System Information

                                </h5>


                                <small class="text-muted">

                                    Basic information about
                                    the application.

                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="card-body">

                        <div class="row g-3">


                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    System Name

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="system_name"
                                    class="form-control"
                                    value="<?= e(
                                        $settings["system_name"]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-3">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    System Version

                                </label>


                                <input
                                    type="text"
                                    name="system_version"
                                    class="form-control"
                                    value="<?= e(
                                        $settings["system_version"]
                                    ) ?>"
                                >

                            </div>


                            <div class="col-md-3">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Currency

                                </label>


                                <select
                                    name="currency"
                                    class="form-select"
                                >

                                    <option
                                        value="PHP"
                                        <?= $settings["currency"] === "PHP"
                                            ? "selected"
                                            : "" ?>
                                    >
                                        PHP
                                    </option>


                                    <option
                                        value="USD"
                                        <?= $settings["currency"] === "USD"
                                            ? "selected"
                                            : "" ?>
                                    >
                                        USD
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     ORDER SETTINGS
                ================================================== -->

                <div
                    class="card settings-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <div
                            class="d-flex align-items-center gap-3"
                        >

                            <div class="section-icon">

                                <i
                                    class="bi bi-cart-check"
                                ></i>

                            </div>


                            <div>

                                <h5
                                    class="section-title mb-0"
                                >

                                    Order Settings

                                </h5>


                                <small class="text-muted">

                                    Control available order
                                    options and charges.

                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="card-body">


                        <div class="row g-4">


                            <!-- DELIVERY -->

                            <div class="col-md-6">

                                <div
                                    class="border rounded p-3 h-100"
                                >

                                    <div
                                        class="form-check form-switch"
                                    >

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="delivery_enabled"
                                            name="delivery_enabled"
                                            <?= $settings["delivery_enabled"] === "1"
                                                ? "checked"
                                                : "" ?>
                                        >


                                        <label
                                            class="form-check-label fw-semibold"
                                            for="delivery_enabled"
                                        >

                                            Enable Delivery

                                        </label>

                                    </div>


                                    <small
                                        class="text-muted d-block mt-2"
                                    >

                                        Customers can place
                                        delivery orders.

                                    </small>

                                </div>

                            </div>


                            <!-- PICKUP -->

                            <div class="col-md-6">

                                <div
                                    class="border rounded p-3 h-100"
                                >

                                    <div
                                        class="form-check form-switch"
                                    >

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="pickup_enabled"
                                            name="pickup_enabled"
                                            <?= $settings["pickup_enabled"] === "1"
                                                ? "checked"
                                                : "" ?>
                                        >


                                        <label
                                            class="form-check-label fw-semibold"
                                            for="pickup_enabled"
                                        >

                                            Enable Pickup

                                        </label>

                                    </div>


                                    <small
                                        class="text-muted d-block mt-2"
                                    >

                                        Customers can place
                                        pickup orders.

                                    </small>

                                </div>

                            </div>


                            <!-- MINIMUM ORDER -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Minimum Order Amount

                                </label>


                                <div class="input-group">

                                    <span
                                        class="input-group-text"
                                    >
                                        ₱
                                    </span>


                                    <input
                                        type="number"
                                        name="minimum_order_amount"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        value="<?= e(
                                            $settings[
                                                "minimum_order_amount"
                                            ]
                                        ) ?>"
                                    >

                                </div>


                                <small class="text-muted">

                                    Set to 0.00 if there is
                                    no minimum order.

                                </small>

                            </div>


                            <!-- DELIVERY FEE -->

                            <div class="col-md-6">

                                <label
                                    class="form-label fw-semibold"
                                >

                                    Delivery Fee

                                </label>


                                <div class="input-group">

                                    <span
                                        class="input-group-text"
                                    >
                                        ₱
                                    </span>


                                    <input
                                        type="number"
                                        name="delivery_fee"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        value="<?= e(
                                            $settings[
                                                "delivery_fee"
                                            ]
                                        ) ?>"
                                    >

                                </div>


                                <small class="text-muted">

                                    Set the standard delivery
                                    charge.

                                </small>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     NOTIFICATIONS
                ================================================== -->

                <div
                    class="card settings-card mb-4"
                >

                    <div
                        class="card-header bg-white py-3"
                    >

                        <div
                            class="d-flex align-items-center gap-3"
                        >

                            <div class="section-icon">

                                <i
                                    class="bi bi-bell"
                                ></i>

                            </div>


                            <div>

                                <h5
                                    class="section-title mb-0"
                                >

                                    Notification Settings

                                </h5>


                                <small class="text-muted">

                                    Configure system notification
                                    preferences.

                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="card-body">


                        <div class="row g-3">


                            <!-- NEW ORDER -->

                            <div class="col-md-4">

                                <div
                                    class="border rounded p-3 h-100"
                                >

                                    <div
                                        class="form-check form-switch"
                                    >

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="new_order_notification"
                                            name="new_order_notification"
                                            <?= $settings[
                                                "new_order_notification"
                                            ] === "1"
                                                ? "checked"
                                                : "" ?>
                                        >


                                        <label
                                            class="form-check-label fw-semibold"
                                            for="new_order_notification"
                                        >

                                            New Order

                                        </label>

                                    </div>


                                    <small
                                        class="text-muted d-block mt-2"
                                    >

                                        Notify staff when a new
                                        order is received.

                                    </small>

                                </div>

                            </div>


                            <!-- PAYMENT -->

                            <div class="col-md-4">

                                <div
                                    class="border rounded p-3 h-100"
                                >

                                    <div
                                        class="form-check form-switch"
                                    >

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="payment_notification"
                                            name="payment_notification"
                                            <?= $settings[
                                                "payment_notification"
                                            ] === "1"
                                                ? "checked"
                                                : "" ?>
                                        >


                                        <label
                                            class="form-check-label fw-semibold"
                                            for="payment_notification"
                                        >

                                            Payment

                                        </label>

                                    </div>


                                    <small
                                        class="text-muted d-block mt-2"
                                    >

                                        Notify staff when a payment
                                        is recorded or verified.

                                    </small>

                                </div>

                            </div>


                            <!-- ORDER STATUS -->

                            <div class="col-md-4">

                                <div
                                    class="border rounded p-3 h-100"
                                >

                                    <div
                                        class="form-check form-switch"
                                    >

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="order_status_notification"
                                            name="order_status_notification"
                                            <?= $settings[
                                                "order_status_notification"
                                            ] === "1"
                                                ? "checked"
                                                : "" ?>
                                        >


                                        <label
                                            class="form-check-label fw-semibold"
                                            for="order_status_notification"
                                        >

                                            Order Status

                                        </label>

                                    </div>


                                    <small
                                        class="text-muted d-block mt-2"
                                    >

                                        Enable order status
                                        notifications.

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SAVE
                ================================================== -->

                <div
                    class="card settings-card"
                >

                    <div
                        class="card-body"
                    >

                        <div
                            class="d-flex flex-wrap justify-content-between align-items-center gap-3"
                        >

                            <div>

                                <div class="fw-bold">

                                    Save System Settings

                                </div>


                                <small class="text-muted">

                                    Changes will take effect
                                    after saving.

                                </small>

                            </div>


                            <button
                                type="button"
                                class="btn btn-jollibee"
                                data-bs-toggle="modal"
                                data-bs-target="#saveSettingsModal"
                            >

                                <i
                                    class="bi bi-check-lg me-1"
                                ></i>

                                Save Changes

                            </button>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     CONFIRMATION MODAL
                ================================================== -->

                <div
                    class="modal fade"
                    id="saveSettingsModal"
                    tabindex="-1"
                    aria-hidden="true"
                >

                    <div
                        class="modal-dialog modal-dialog-centered"
                    >

                        <div class="modal-content">


                            <div class="modal-header">

                                <h5
                                    class="modal-title fw-bold"
                                >

                                    Confirm Changes

                                </h5>


                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                ></button>

                            </div>


                            <div class="modal-body">

                                <div
                                    class="text-center py-2"
                                >

                                    <div
                                        class="text-jollibee fs-1 mb-2"
                                    >

                                        <i
                                            class="bi bi-gear-wide-connected"
                                        ></i>

                                    </div>


                                    <p class="mb-0">

                                        Are you sure you want to
                                        save these system settings?

                                    </p>

                                </div>

                            </div>


                            <div class="modal-footer">

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal"
                                >

                                    Cancel

                                </button>


                                <button
                                    type="submit"
                                    class="btn btn-jollibee"
                                >

                                    <i
                                        class="bi bi-check-lg me-1"
                                    ></i>

                                    Yes, Save Changes

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

        </main>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>