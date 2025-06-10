<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$order_id = $_GET['order_id'] ?? null;
$product_id = $_GET['product_id'] ?? null;
$seller_id = $_SESSION['user_id'];

if (!$order_id || !$product_id) {
    echo "Invalid request.";
    exit();
}

// Check if this seller owns the product
$check_stmt = $conn->prepare("
    SELECT 1 FROM products 
    WHERE product_id = ? AND seller_id = ?
");
$check_stmt->bind_param("ii", $product_id, $seller_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows === 0) {
    echo "Unauthorized: You do not own this product.";
    exit();
}
$check_stmt->close();

// Mark as delivered
$update_stmt = $conn->prepare("
    UPDATE order_items 
    SET delivered = 1 
    WHERE order_id = ? AND product_id = ?
");
$update_stmt->bind_param("ii", $order_id, $product_id);
$update_stmt->execute();

header("Location: seller_orders.php?message=Delivery+marked+complete");
exit();
?>