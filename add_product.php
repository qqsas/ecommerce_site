<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';
include 'header2.php';

$errors = [];
$name = $price = $stock = $description = $selected_category = $new_category_name = "";
$image_path = null;

// Handle product submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $seller_id = $_SESSION['user_id'];
    $selected_category = $_POST['category'];
    $new_category_name = trim($_POST['new_category']);

    // Validate inputs
    if (empty($name)) $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Price must be a positive number.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";
    if (empty($description)) $errors[] = "Description is required.";

    // Validate image
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['product_image']['tmp_name'];
        $fileName = basename($_FILES['product_image']['name']);
        $fileType = mime_content_type($fileTmp);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, PNG, or GIF images are allowed.";
        } elseif ($_FILES['product_image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image must be less than 2MB.";
        } else {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $targetFile = $targetDir . uniqid() . "_" . $fileName;
            if (move_uploaded_file($fileTmp, $targetFile)) {
                $image_path = $targetFile;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }

    // If no errors, proceed
    if (empty($errors)) {
        // Category handling
        if (!empty($new_category_name)) {
            $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $stmt->bind_param("s", $new_category_name);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $category_id = $row['category_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
                $stmt->bind_param("s", $new_category_name);
                $stmt->execute();
                $category_id = $stmt->insert_id;
            }
        } else {
            $category_id = intval($selected_category);
            if ($category_id <= 0) {
                $errors[] = "Please select a valid category or enter a new one.";
            }
        }
    }

    // If still no errors, insert into DB
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (name, original_price, price, stock, description, seller_id, category_id, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddssis", $name, $price, $price, $stock, $description, $seller_id, $category_id, $image_path);
        $stmt->execute();
        header("Location: seller_dashboard.php?message=Product added successfully!");
        exit();
    }
}

// Fetch categories
$categories = [];
$result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <style>
@media (min-width: 769px) {
      .menu-toggle {
        display: none !important;
      }
    }
        </style>
    <title>Add Product</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="form-container">
        <h1 class="form-title">Add Product</h1>

        <!-- Show error messages -->
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price (R)</label>
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($price) ?>" required>
            </div>

            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" name="stock" value="<?= htmlspecialchars($stock) ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Select Category</label>
                <select name="category">
                    <option value="">-- Choose existing category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $selected_category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="new_category">Or Add New Category</label>
                <input type="text" name="new_category" value="<?= htmlspecialchars($new_category_name) ?>" placeholder="e.g. Handmade Crafts">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" rows="3" required><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="form-group">
                <label for="product_image">Product Image</label>
                <input type="file" name="product_image" accept="image/*">
            </div>

            <div class="button-group">
                <button type="submit" class="primary-btn">Add Product</button>
                <a href="seller_dashboard.php" class="secondary-btn">Cancel</a>
            </div>
        </form>
    </div>
    
</body>
</html>
