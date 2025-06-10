<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$order_id = $_POST['order_id'];
$product_id = $_POST['product_id'];
$payment_status = $_POST['payment_status'] ?? null;
$delivery_status = $_POST['delivery_status'] ?? null;

// ✅ Update delivery status (delivery table only has order_id, no product_id)
$update_query = "UPDATE delivery SET delivery_status = ? WHERE order_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("si", $delivery_status, $order_id);
$update_stmt->execute();

// ✅ Update payment status
if ($payment_status !== null) {
    $pay_stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE order_id = ?");
    $pay_stmt->bind_param("si", $payment_status, $order_id);
    $pay_stmt->execute();
}

// ✅ Insert into completed_orders if marked as Delivered
if ($delivery_status === 'Delivered') {
    $fetch_stmt = $conn->prepare("
        SELECT o.order_date, oi.quantity, oi.price, o.user_id AS buyer_id, p.seller_id
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id AND oi.product_id = ?
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.order_id = ?
    ");
    $fetch_stmt->bind_param("ii", $product_id, $order_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        $insert_stmt = $conn->prepare("
            INSERT INTO completed_orders (order_id, product_id, seller_id, buyer_id, quantity, price, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->bind_param(
            "iiiiids",
            $order_id,
            $product_id,
            $data['seller_id'],
            $data['buyer_id'],
            $data['quantity'],
            $data['price'],
            $data['order_date']
        );
        $insert_stmt->execute();
    }
}

header("Location: seller_orders.php?message=Order updated successfully");
exit();
