<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['payment_status'];
    $seller_id = $_SESSION['user_id'];

    // Step 1: Check if this payment belongs to a product sold by the seller
    $check_stmt = $conn->prepare("
        SELECT p.payment_method
        FROM payments p
        JOIN orders o ON p.order_id = o.order_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products pr ON oi.product_id = pr.product_id
        WHERE p.payment_id = ? AND pr.seller_id = ?
        LIMIT 1
    ");
    $check_stmt->bind_param("ii", $payment_id, $seller_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (in_array($row['payment_method'], ['EFT', 'Card'])) {
            // Valid seller & payment method
            $update_stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
            $update_stmt->bind_param("si", $new_status, $payment_id);
            $update_stmt->execute();
        }
    }

    header("Location: seller_orders.php");
    exit();
}
?>
