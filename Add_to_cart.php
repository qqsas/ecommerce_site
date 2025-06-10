<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    // Not logged in
    header("Location: login.php");
    exit();
}

// Use POST instead of GET to receive product_id and quantity
if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity_requested = max(1, intval($_POST['quantity'])); // Ensure minimum quantity is 1
    $user_id = $_SESSION['user_id'];

    // Check if the product is already in the cart
    $check_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Product already in cart — increase quantity by selected amount
        $update_sql = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $quantity_requested, $user_id, $product_id);
        $stmt->execute();
    } else {
        // Insert with selected quantity
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iii", $user_id, $product_id, $quantity_requested);
        $stmt->execute();
    }

    // Redirect back to the previous page
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: index.php"); // fallback
    }
    exit();
} else {
    echo "No product selected or quantity not specified.";
}
?>
