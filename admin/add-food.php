<?php

require_once "../includes/auth.php";
requireRole("admin");

require_once "../config/database.php";

$errors = [];

$food_name = "";
$description = "";
$price = "";
$category_id = "";
$status = "available";

// Get categories
$categoryStmt = $pdo->query("
    SELECT *
    FROM food_categories
    WHERE status = 'active'
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $food_name = trim($_POST["food_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $status = $_POST["status"] ?? "available";

    // Validate food name
    if ($food_name === "") {
        $errors[] = "Food name is required.";
    }

    // Validate category
    if ($category_id === "" || !is_numeric($category_id)) {
        $errors[] = "Please select a category.";
    }

    // Validate price
    if ($price === "" || !is_numeric($price) || $price < 0) {
        $errors[] = "Please enter a valid price.";
    }

    // Validate status
    if (!in_array($status, ["available", "unavailable"])) {
        $errors[] = "Invalid food status.";
    }

    $imageName = null;

    // Handle image upload
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "There was an error uploading the image.";
        } else {

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $fileType = mime_content_type($_FILES["image"]["tmp_name"]);
            $fileSize = $_FILES["image"]["size"];

            // Maximum 2MB
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Only JPG, PNG, and WEBP images are allowed.";
            }

            if ($fileSize > 2 * 1024 * 1024) {
                $errors[] = "Image size must not exceed 2MB.";
            }

            if (empty($errors)) {

                $uploadDirectory = "../assets/uploads/menu/";

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $extension = strtolower(
                    pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)
                );

                $imageName = uniqid("food_", true) . "." . $extension;

                $uploadPath = $uploadDirectory . $imageName;

                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $uploadPath
                )) {
                    $errors[] = "Failed to save the uploaded image.";
                    $imageName = null;
                }
            }
        }
    }

    // Insert food item
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO food_items
            (
                category_id,
                food_name,
                description,
                price,
                image,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $category_id,
            $food_name,
            $description,
            $price,
            $imageName,
            $status
        ]);

        header("Location: menu.php?success=added");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Food Item | Jollibee Central Plaza</title>
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
                <?= htmlspecialchars(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
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
                <?= htmlspecialchars(($_SESSION["first_name"] ?? "Admin") . " " . ($_SESSION["last_name"] ?? "")) ?>
            </div>
            <small class="text-muted">Administrator</small>
        </div>
        <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a class="nav-link active" href="menu.php">
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


            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="fw-bold mb-1">
                        Add Food Item
                    </h2>

                    <p class="text-muted mb-0">
                        Add a new item to the Jollibee Central Plaza menu.
                    </p>

                </div>

                <a
                    href="menu.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Back to Menu
                </a>

            </div>


            <!-- Errors -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <div class="fw-bold mb-2">
                        Please correct the following:
                    </div>

                    <ul class="mb-0">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars($error); ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0 fw-bold">
                        Food Information
                    </h5>

                </div>


                <div class="card-body">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <div class="row g-3">

                            <!-- Food Name -->

                            <div class="col-md-8">

                                <label class="form-label fw-semibold">
                                    Food Name
                                </label>

                                <input
                                    type="text"
                                    name="food_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars($food_name); ?>"
                                    placeholder="Enter food name"
                                    required
                                >

                            </div>


                            <!-- Category -->

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    Category
                                </label>

                                <select
                                    name="category_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select category
                                    </option>

                                    <?php foreach ($categories as $category): ?>

                                        <option
                                            value="<?= $category['category_id']; ?>"
                                            <?= $category_id == $category['category_id'] ? 'selected' : ''; ?>
                                        >
                                            <?= htmlspecialchars($category['category_name']); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- Description -->

                            <div class="col-12">

                                <label class="form-label fw-semibold">
                                    Description
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter food description"
                                ><?= htmlspecialchars($description); ?></textarea>

                            </div>


                            <!-- Price -->

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    Price
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        ₱
                                    </span>

                                    <input
                                        type="number"
                                        name="price"
                                        class="form-control"
                                        step="0.01"
                                        min="0"
                                        value="<?= htmlspecialchars($price); ?>"
                                        placeholder="0.00"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Status -->

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    Status
                                </label>

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option
                                        value="available"
                                        <?= $status === 'available' ? 'selected' : ''; ?>
                                    >
                                        Available
                                    </option>

                                    <option
                                        value="unavailable"
                                        <?= $status === 'unavailable' ? 'selected' : ''; ?>
                                    >
                                        Unavailable
                                    </option>

                                </select>

                            </div>


                            <!-- Image -->

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    Food Image
                                </label>

                                <input
                                    type="file"
                                    name="image"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >

                                <small class="text-muted">
                                    JPG, PNG, or WEBP. Maximum 2MB.
                                </small>

                            </div>


                            <!-- Buttons -->

                            <div class="col-12 mt-4">

                                <hr>

                                <div class="d-flex gap-2">

                                    <button
                                        type="submit"
                                        class="btn btn-jollibee"
                                    >
                                        <i class="bi bi-plus-circle me-1"></i>
                                        Add Food Item
                                    </button>

                                    <a
                                        href="menu.php"
                                        class="btn btn-outline-secondary"
                                    >
                                        Cancel
                                    </a>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
