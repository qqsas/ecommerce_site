<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$seller_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // Ensure the product belongs to the seller and is soft-deleted
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ? AND is_deleted = 1");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Restore the product
        $update = $conn->prepare("UPDATE products SET is_deleted = 0 WHERE product_id = ?");
        $update->bind_param("i", $product_id);
        $update->execute();

        header("Location: seller_products.php?message=Product restored successfully.");
        exit();
    } else {
        // Product doesn't exist or doesn't belong to seller
        header("Location: seller_products.php?message=Invalid product or unauthorized access.");
        exit();
    }
} else {
    header("Location: seller_products.php?message=No product ID specified.");
    exit();
}
