<?php
session_start();
include 'db.php';
include 'header.php';

// Validate and fetch category
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: homepage.php');
    exit;
}


$category_id = (int)$_GET['id'];

// Fetch category name
$stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if (!$category) {
    header('Location: homepage.php');
    exit;
}

// Fetch products in this category
$stmt = $conn->prepare("SELECT * FROM products 
                        WHERE category_id = ? AND is_available = 1 AND is_deleted = 0
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
@media (min-width: 769px) {
      .menu-toggle {
        display: none !important;
      }
    }
        </style>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($category['category_name']) ?> - Products</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<!-- Category Products -->
<div class="container">
    <h2><?= htmlspecialchars($category['category_name']) ?> Products</h2>
    <div class="product-grid">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php $product_img = !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'default.png'; ?>
                    <img src="<?= $product_img ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($product['name']) ?></h5>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <p><strong>Price: R<?= number_format($product['price'], 2) ?></strong></p>
                        <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn">View Product</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products found in this category.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

