<?php
session_start();
include 'db.php';
include 'header.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$products = [];

if (!empty($search_query)) {
    $like_query = "%" . $search_query . "%";
    $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p
                            LEFT JOIN categories c ON p.category_id = c.category_id
                            WHERE p.is_available = 1 AND p.is_deleted = 0 
                            AND (p.name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)
                            ORDER BY p.created_at DESC");
    $stmt->bind_param("sss", $like_query, $like_query, $like_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
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
    <title>Search Results</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="product-section">
    <h2>Search Results for "<?= htmlspecialchars($search_query) ?>"</h2>

    <?php if (!empty($products)): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    
                        <?php $product_img = !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'default.png'; ?>
                        <img src="<?= $product_img ?>" class="custom-card-image" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="product-details">
                            <h5 class="product-name"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                             <?php if (!is_null($product['original_price']) && $product['original_price'] > $product['price']): ?>
                            <p class="product-price">
                                <span class="original-price">R<?= number_format($product['original_price'], 2) ?></span>
                                <span class="price-discounted">Now R<?= number_format($product['price'], 2) ?></span>
                            </p>
                        <?php else: ?>
                            <p class="product-price">Price: R<?= number_format($product['price'], 2) ?></p>
                        <?php endif; ?>
                            <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn">View Product</a>
                        </div>
                    
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="custom-message">No products found matching your search.</p>
    <?php endif; ?>
</div>

</body>
</html>

