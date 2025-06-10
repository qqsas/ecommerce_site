<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';
include 'header2.php';

$product_id = $_GET['product_id'] ?? null;
$seller_id = $_SESSION['user_id'];

// Fetch product to ensure it belongs to this seller
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "Product not found or access denied.";
    exit();
}

// Fetch all categories
$categories_result = $conn->query("SELECT category_id, category_name FROM categories");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Handle category (existing or new)
    if (!empty($_POST['new_category'])) {
        $new_category = trim($_POST['new_category']);
        $check_stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check_stmt->bind_param("s", $new_category);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $insert_stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $insert_stmt->bind_param("s", $new_category);
            $insert_stmt->execute();
            $category_id = $insert_stmt->insert_id;
        } else {
            $category_id = $check_result->fetch_assoc()['category_id'];
        }
    } else {
        $category_id = $_POST['category_id'];
    }

    // Handle optional new image upload
    $image_path = $product['image_path'];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['product_image']['tmp_name'];
        $fileName = basename($_FILES['product_image']['name']);
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetFile = $targetDir . uniqid() . "_" . $fileName;
        if (move_uploaded_file($fileTmp, $targetFile)) {
            $image_path = $targetFile;
        }
    }

    // Update product (including image path)
    $update_stmt = $conn->prepare("
        UPDATE products 
        SET name = ?, description = ?, original_price = ?, stock = ?, category_id = ?, is_available = ?, image_path = ? 
        WHERE product_id = ? AND seller_id = ?
    ");
    $update_stmt->bind_param("ssdiiissi", $name, $desc, $price, $stock, $category_id, $is_available, $image_path, $product_id, $seller_id);
    $update_stmt->execute();

    header("Location: seller_products.php?message=Product updated successfully");
    exit();
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
    <title>Edit Product</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="main-container">
    <h2>Edit Product</h2>
    <form method="POST" class="form-section" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" class="input-field" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="input-field" required><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (R)</label>
            <input type="number" name="price" id="price" class="input-field" step="0.01" value="<?= $product['original_price'] ?>" required>
        </div>

        <div class="form-group">
            <label for="stock">Stock</label>
            <input type="number" name="stock" id="stock" class="input-field" value="<?= $product['stock'] ?>" required>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="input-select">
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= $product['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="form-note">Or add new category below</small>
        </div>

        <div class="form-group">
            <label for="new_category">New Category</label>
            <input type="text" name="new_category" id="new_category" class="input-field">
        </div>

        <div class="form-group form-checkbox">
            <input type="checkbox" name="is_available" id="is_available" <?= $product['is_available'] ? 'checked' : '' ?>>
            <label for="is_available">Available for sale</label>
        </div>

        <div class="form-group">
            <label>Current Image</label><br>
            <?php if (!empty($product['image_path'])): ?>
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" style="max-width: 200px; height: auto;"><br>
            <?php else: ?>
                <em>No image uploaded.</em><br>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="product_image">Upload New Image</label>
            <input type="file" name="product_image" id="product_image" class="input-field" accept="image/*">
            <small class="form-note">Leave empty to keep the current image.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Product</button>
            <a href="seller_products.php" class="back-button">Cancel</a>
        </div>
    </form>
</body>
</html>
