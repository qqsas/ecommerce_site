<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $seller_id = $_SESSION['user_id'];

    // Soft delete the product (set is_deleted to 1)
    $stmt = $conn->prepare("UPDATE products SET is_deleted = 1 WHERE product_id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);

    if ($stmt->execute()) {
        header("Location: seller_products.php?message=Product deleted successfully.");
    } else {
        header("Location: seller_products.php?message=Error deleting product.");
    }

    $stmt->close();
} else {
    header("Location: seller_products.php?message=No product specified.");
}

$conn->close();
?>
