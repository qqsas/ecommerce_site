<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['payment_method']) || empty($_POST['delivery_instructions']) || empty($_POST['delivery_address'])) {
    echo "<p>Missing details. <a href='checkout.php'>Try again</a></p>";
    exit();
}

$payment_method = $_POST['payment_method'];
$delivery_instructions = $_POST['delivery_instructions'];
$delivery_address = $_POST['delivery_address'];
$order_date = date('Y-m-d H:i:s');
$delivery_date = date('Y-m-d', strtotime('+3 days')); // example delivery in 3 days

$sql = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Your cart is empty. <a href='homepage.php'>Go back</a></p>";
    exit();
}

$grand_total = 0;
$cart_items = [];

while ($row = $result->fetch_assoc()) {
    $product_id = $row['product_id'];
    $quantity = $row['quantity'];

    $price_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
    $price_stmt->bind_param("i", $product_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    $price_row = $price_result->fetch_assoc();
    $price = $price_row['price'];

    $line_total = $price * $quantity;
    $grand_total += $line_total;

    $cart_items[] = ['product_id' => $product_id, 'quantity' => $quantity, 'price' => $price];
}

// Insert into orders (add order_date)
$order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, order_date, payment_method, delivery_instructions) VALUES (?, ?, ?, ?, ?)");
$order_stmt->bind_param("idsss", $user_id, $grand_total, $order_date, $payment_method, $delivery_instructions);
$order_stmt->execute();
$order_id = $order_stmt->insert_id;

// Insert into order_items (include `delivered` field as 0 = false)
$item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, delivered) VALUES (?, ?, ?, ?, 0)");
foreach ($cart_items as $item) {
    $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
    $item_stmt->execute();
}

// Insert into payments
$payment_status = ($payment_method === 'Cash on Delivery') ? 'Pending (Cash)' : 'Awaiting Confirmation';
$payment_date = date('Y-m-d H:i:s');

$payment_stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?)");
$payment_stmt->bind_param("isss", $order_id, $payment_method, $payment_status, $payment_date);
$payment_stmt->execute();

// Insert into delivery
$delivery_status = 'Preparing';
$tracking_number = 'TRACK' . strtoupper(substr(md5(uniqid()), 0, 8));

$delivery_stmt = $conn->prepare("INSERT INTO delivery (order_id, delivery_status, tracking_number, delivery_date, delivery_address) VALUES (?, ?, ?, ?, ?)");
$delivery_stmt->bind_param("issss", $order_id, $delivery_status, $tracking_number, $delivery_date, $delivery_address);
$delivery_stmt->execute();

// Clear cart
$clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$clear_stmt->bind_param("i", $user_id);
$clear_stmt->execute();
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
    <title>Order Placed</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet" media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="order-page">
    <div class="order-confirmation-box">
        <h4 class="order-confirmation-heading">Order Confirmed!</h4>
        <p>Your order ID is <strong>#<?php echo $order_id; ?></strong>.</p>
        <p>Payment Method: <strong><?php echo htmlspecialchars($payment_method); ?></strong></p>
        <p>Payment Status: <strong><?php echo htmlspecialchars($payment_status); ?></strong></p>
        <p>Delivery Address: <?php echo htmlspecialchars($delivery_address); ?></p>
        <p>Tracking Number: <?php echo htmlspecialchars($tracking_number); ?></p>
        <p>Estimated Delivery Date: <?php echo htmlspecialchars($delivery_date); ?></p>
        <a href="homepage.php" class="back-button">Return to Homepage</a>
        <a href="buyer_orders.php" class="btn">View My Orders</a>
    </div>
</body>
</html>
