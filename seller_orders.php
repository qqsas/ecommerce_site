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
$show_all = isset($_GET['show']) && $_GET['show'] === 'all';

// Adjust SQL based on toggle
$sql = "
    SELECT o.order_id, o.order_date, u.username AS buyer_name,
           oi.product_id, oi.quantity, oi.price,
           p.name AS product_name, pay.payment_status, d.delivery_status,
           o.payment_method
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN payments pay ON o.order_id = pay.order_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    WHERE p.seller_id = ?
";

// Filter out delivered orders unless "show all" is active
if (!$show_all) {
    $sql .= " AND (d.delivery_status IS NULL OR d.delivery_status != 'Delivered')";
}

$sql .= " ORDER BY o.order_date DESC, o.order_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[$row['order_id']]['order_info'] = $row;
    $orders[$row['order_id']]['items'][] = $row;
}
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
    <title>Seller Orders</title>
    <link href="styles2.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="container">
    <h1 class="seller-orders-title">My Orders</h1>

    <div style="margin-bottom: 20px;">
        <?php if ($show_all): ?>
            <a href="seller_orders.php" class="btn">Show Undelivered Only</a>
        <?php else: ?>
            <a href="seller_orders.php?show=all" class="btn">Show All Orders</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="seller-orders-message success-message">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
        <table class="seller-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Products</th>
                    <th>Payment</th>
                    <th>Delivery</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order_id => $data): ?>
                <?php $info = $data['order_info']; ?>
                <tr>
                    <td><?= $order_id; ?></td>
                    <td><?= $info['order_date']; ?></td>
                    <td><?= htmlspecialchars($info['buyer_name']); ?></td>
                    <td>
                        <ul class="seller-orders-product-list">
                            <?php foreach ($data['items'] as $item): ?>
                                <li>
                                    <?= htmlspecialchars($item['product_name']); ?> - 
                                    Qty: <?= $item['quantity']; ?> - 
                                    R<?= number_format($item['price'], 2); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td><?= htmlspecialchars($info['payment_status'] ?? 'Pending'); ?></td>
                    <td><?= htmlspecialchars($info['delivery_status'] ?? 'Pending'); ?></td>
                    <td>
                        <form action="update_order_status.php" method="POST" class="seller-orders-form">
                            <input type="hidden" name="order_id" value="<?= $order_id; ?>">

                            <?php if (in_array(strtolower($info['payment_method']), ['eft', 'card'])): ?>
                                <select name="payment_status" class="seller-orders-select">
                                    <option value="Pending" <?= $info['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Paid" <?= $info['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Failed" <?= $info['payment_status'] === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            <?php else: ?>
                                <span class="seller-orders-note">Cash - update on delivery</span>
                            <?php endif; ?>

                            <select name="delivery_status" class="seller-orders-select">
                                <option value="Pending" <?= $info['delivery_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Waiting on Payment" <?= $info['delivery_status'] === 'Waiting on Payment' ? 'selected' : ''; ?>>Waiting on Payment</option>
                                <option value="Out for Delivery" <?= $info['delivery_status'] === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="Delivered" <?= $info['delivery_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>

                            <button type="submit" class="btn">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="seller-orders-empty">No orders found<?= $show_all ? '.' : ' (excluding delivered).' ?></p>
    <?php endif; ?>
</div>
</body>
</html>
