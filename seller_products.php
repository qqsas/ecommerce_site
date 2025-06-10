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
$message = "";

// Update stock or price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    if ($price < 0 || $stock < 0) {
        $message = "<div class='alert alert-warning'>Price and stock cannot be negative.</div>";
    } else {
        $stmt = $conn->prepare("UPDATE products SET price = ?, stock = ? WHERE product_id = ? AND seller_id = ?");
        $stmt->bind_param("ddii", $price, $stock, $product_id, $seller_id);
        $stmt->execute();
    }
}

// Toggle product availability
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $current = intval($_GET['toggle']);
    $new = $current === 1 ? 0 : 1;

    $stmt = $conn->prepare("UPDATE products SET is_available = ? WHERE product_id = ? AND seller_id = ?");
    $stmt->bind_param("iii", $new, $product_id, $seller_id);
    $stmt->execute();
}

// Fetch seller products that are not soft deleted
$stmt = $conn->prepare("
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.seller_id = ? AND p.is_deleted = 0
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
    <title>My Products</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="product-page">
    <h1>My Products</h1>
    <a href="add_product.php" class="back-button">+ Add New Product</a>

    <?= $message ?>

    <?php if (count($products) === 0): ?>
        <div class="info-box">You haven't listed any products yet.</div>
    <?php else: ?>
        <table class="product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (R)</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <form method="POST" action="seller_products.php">
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>
                                <input type="number" name="price" value="<?= $p['price'] ?>" step="0.01" min="0" class="form-control" required>
                            </td>
                            <td>
                                <input type="number" name="stock" value="<?= $p['stock'] ?>" min="0" class="form-control" required>
                            </td>
                            <td><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></td>
                            <td>
                                <a href="seller_products.php?toggle=<?= $p['is_available'] ?>&id=<?= $p['product_id'] ?>" class="btn btn-sm <?= $p['is_available'] ? 'btn-success' : 'btn-secondary' ?>">
                                    <?= $p['is_available'] ? 'Active' : 'Inactive' ?>
                                </a>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                    <button type="submit" name="update_product" class="action-button btn-primary">Save</button>
                                    <a href="edit_product.php?product_id=<?= $p['product_id'] ?>" class="action-button btn-warning">Edit</a>
                                    <a href="delete_product.php?product_id=<?= $p['product_id'] ?>" class="action-button btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="add_special.php?product_id=<?= $p['product_id'] ?>" class="action-button btn-info">Add Special</a>
                                </div>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
