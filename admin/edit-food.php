<?php

require_once "../includes/auth.php";
requireRole("admin");

require_once "../config/database.php";

$foodId = $_GET["id"] ?? null;

if (!$foodId || !is_numeric($foodId)) {
    header("Location: menu.php");
    exit;
}


// Get food item
$stmt = $pdo->prepare("
    SELECT *
    FROM food_items
    WHERE food_id = ?
    LIMIT 1
");

$stmt->execute([$foodId]);

$food = $stmt->fetch();

if (!$food) {
    header("Location: menu.php");
    exit;
}


// Get categories
$categoryStmt = $pdo->query("
    SELECT *
    FROM food_categories
    WHERE status = 'active'
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();


$errors = [];

$food_name = $food["food_name"];
$description = $food["description"];
$price = $food["price"];
$category_id = $food["category_id"];
$status = $food["status"];
$currentImage = $food["image"];


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


    $newImage = $currentImage;


    // Handle new image
    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

            $errors[] = "There was an error uploading the image.";

        } else {

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $fileType = mime_content_type(
                $_FILES["image"]["tmp_name"]
            );

            $fileSize = $_FILES["image"]["size"];


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
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );


                $newImage = uniqid(
                    "food_",
                    true
                ) . "." . $extension;


                $uploadPath = $uploadDirectory . $newImage;


                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $uploadPath
                )) {

                    $errors[] = "Failed to save the uploaded image.";

                    $newImage = $currentImage;
                }
            }
        }
    }


    // Update database
    if (empty($errors)) {

        $updateStmt = $pdo->prepare("
            UPDATE food_items
            SET
                category_id = ?,
                food_name = ?,
                description = ?,
                price = ?,
                image = ?,
                status = ?
            WHERE food_id = ?
        ");

        $updateStmt->execute([
            $category_id,
            $food_name,
            $description,
            $price,
            $newImage,
            $status,
            $foodId
        ]);


        // Delete old image if a new image was uploaded
        if (
            $newImage !== $currentImage &&
            !empty($currentImage)
        ) {

            $oldImagePath =
                "../assets/uploads/menu/" . $currentImage;

            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }


        header("Location: menu.php?success=updated");
        exit;
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
        Edit Food Item | Jollibee Central Plaza
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="bg-light">


<nav class="navbar navbar-dark bg-jollibee">

    <div class="container-fluid">

        <a
            href="dashboard.php"
            class="navbar-brand fw-bold"
        >
            <i class="bi bi-shop me-2"></i>
            Jollibee Central Plaza
        </a>

        <span class="text-white">
            Admin
        </span>

    </div>

</nav>


<div class="container-fluid">

    <div class="row">

        <!-- Sidebar -->

        <div class="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3">

            <div class="list-group">

                <a
                    href="dashboard.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>

                <a
                    href="menu.php"
                    class="list-group-item list-group-item-action active"
                >
                    <i class="bi bi-menu-button-wide me-2"></i>
                    Menu Management
                </a>

                <a
                    href="orders.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-receipt me-2"></i>
                    Orders
                </a>

                <a
                    href="users.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-people me-2"></i>
                    User Management
                </a>

                <a
                    href="sales.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-graph-up me-2"></i>
                    Sales Reports
                </a>

                <a
                    href="forecast.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Demand Forecasting
                </a>

                <a
                    href="settings.php"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-gear me-2"></i>
                    System Settings
                </a>

            </div>

        </div>


        <!-- Main -->

        <main class="col-md-9 col-lg-10 p-4">

            <div
                class="d-flex justify-content-between align-items-center mb-4"
            >

                <div>

                    <h2 class="fw-bold mb-1">
                        Edit Food Item
                    </h2>

                    <p class="text-muted mb-0">
                        Update the selected menu item.
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

                <div class="card-header bg-white">

                    <h5 class="fw-bold mb-0">
                        Food Information
                    </h5>

                </div>


                <div class="card-body">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <div class="row g-3">

                            <div class="col-md-8">

                                <label class="form-label fw-semibold">
                                    Food Name
                                </label>

                                <input
                                    type="text"
                                    name="food_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars($food_name); ?>"
                                    required
                                >

                            </div>


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
                                            <?= $category_id == $category['category_id']
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            <?= htmlspecialchars(
                                                $category['category_name']
                                            ); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="col-12">

                                <label class="form-label fw-semibold">
                                    Description
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                ><?= htmlspecialchars($description); ?></textarea>

                            </div>


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
                                        required
                                    >

                                </div>

                            </div>


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
                                        <?= $status === 'available'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Available
                                    </option>

                                    <option
                                        value="unavailable"
                                        <?= $status === 'unavailable'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Unavailable
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    Replace Image
                                </label>

                                <input
                                    type="file"
                                    name="image"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >

                                <small class="text-muted">
                                    Leave blank to keep the current image.
                                </small>

                            </div>


                            <!-- Current Image -->

                            <?php if (!empty($currentImage)): ?>

                                <div class="col-12">

                                    <label class="form-label fw-semibold">
                                        Current Image
                                    </label>

                                    <div>

                                        <img
                                            src="../assets/uploads/menu/<?= htmlspecialchars($currentImage); ?>"
                                            alt="<?= htmlspecialchars($food_name); ?>"
                                            class="img-thumbnail"
                                            style="width: 180px; height: 180px; object-fit: cover;"
                                        >

                                    </div>

                                </div>

                            <?php endif; ?>


                            <div class="col-12 mt-4">

                                <hr>

                                <button
                                    type="submit"
                                    class="btn btn-jollibee"
                                >
                                    <i class="bi bi-save me-1"></i>
                                    Save Changes
                                </button>

                                <a
                                    href="menu.php"
                                    class="btn btn-outline-secondary ms-2"
                                >
                                    Cancel
                                </a>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>