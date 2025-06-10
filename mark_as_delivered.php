<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Step 1: Mark all items in the order as delivered
$update = $conn->prepare("UPDATE order_items SET delivered = 1 WHERE order_id = ?");
$update->bind_param("i", $order_id);
$update->execute();

// Step 2: Check if all items are marked delivered
$check = $conn->prepare("SELECT COUNT(*) AS undelivered FROM order_items WHERE order_id = ? AND delivered = 0");
$check->bind_param("i", $order_id);
$check->execute();
$check_result = $check->get_result()->fetch_assoc();

if ($check_result['undelivered'] == 0) {
    // Step 3: Get order date
    $order_stmt = $conn->prepare("SELECT order_date FROM orders WHERE order_id = ?");
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_date = $order_stmt->get_result()->fetch_assoc()['order_date'];

    // Step 4: Get all delivered items
    $items_stmt = $conn->prepare("
        SELECT oi.product_id, oi.quantity, oi.price, p.seller_id
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    // Step 5: Insert each delivered item into completed_orders
    $completed_date = date("Y-m-d H:i:s");
    $insert_stmt = $conn->prepare("
        INSERT INTO completed_orders (order_id, product_id, seller_id, buyer_id, quantity, price, order_date, completed_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    while ($item = $items_result->fetch_assoc()) {
        $insert_stmt->bind_param("iiiidsss", $order_id, $item['product_id'], $item['seller_id'], $user_id, $item['quantity'], $item['price'], $order_date, $completed_date);
        $insert_stmt->execute();
    }

    // Step 6: Optionally, update delivery table status
    $conn->prepare("UPDATE delivery SET delivery_status = 'Delivered', delivery_date = ? WHERE order_id = ?")
         ->bind_param("si", $completed_date, $order_id)
         ->execute();
}

header("Location: buyer_orders.php");
exit();
?>