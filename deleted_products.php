<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';
include 'header2.php';

$seller_id = $_SESSION['user_id'];

// Fetch soft-deleted products
$stmt = $conn->prepare("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.seller_id = ? AND p.is_deleted = 1
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Deleted Products</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="main-container">
    <h1>Deleted Products</h1>
    <a href="seller_products.php" class="back-button">← Back to My Products</a>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert info">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
    <?php endif; ?>

    <?php if (count($products) === 0): ?>
        <div class="alert warning">No deleted products found.</div>
    <?php else: ?>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price (R)</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Restore</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['description']) ?></td>
                        <td><?= number_format($p['price'], 2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
                        <td>
                            <a href="restore_product.php?id=<?= $p['product_id'] ?>" class="restore-button" onclick="return confirm('Restore this product?')">Restore</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
