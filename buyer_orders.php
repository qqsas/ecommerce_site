<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';
$user_id = $_SESSION['user_id'];

// Determine view: 'current' by default
$view = isset($_GET['view']) && $_GET['view'] === 'past' ? 'past' : 'current';
$is_delivered = $view === 'past' ? 1 : 0;

// Fetch buyer's orders with delivery status filter
$stmt = $conn->prepare("
    SELECT DISTINCT o.order_id, o.order_date, o.total_amount
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ? AND oi.delivered = ?
    ORDER BY o.order_date DESC
");
$stmt->bind_param("ii", $user_id, $is_delivered);
$stmt->execute();
$orders = $stmt->get_result();
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
    <title>My Orders</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="order-header">
        <h1>My Orders</h1>
        <div style="margin-top: 1rem;">
            <a href="?view=current" class="btn" <?= $view === 'current' ? 'style="font-weight:bold;"' : '' ?>>Current Orders</a>
            <a href="?view=past" class="btn" <?= $view === 'past' ? 'style="font-weight:bold;"' : '' ?>>Past Orders</a>
        </div>
    </div>

    <div class="container">
        <?php while ($order = $orders->fetch_assoc()): ?>
            <?php
            // Fetch all items in this order with the specified delivery status
            $items_stmt = $conn->prepare("
                SELECT oi.product_id, p.name, oi.quantity, oi.price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ? AND oi.delivered = ?
            ");
            $items_stmt->bind_param("ii", $order['order_id'], $is_delivered);
            $items_stmt->execute();
            $items = $items_stmt->get_result();

            if ($items->num_rows > 0):
            ?>
            <div class="order-card">
                <div class="order-header">
                    <strong>Order #<?php echo $order['order_id']; ?></strong><br>
                    <span>Date: <?php echo $order['order_date']; ?></span><br>
                    <span>Total: R<?php echo $order['total_amount']; ?></span>
                </div>
                <div class="order-body">
                    <?php while ($item = $items->fetch_assoc()):
                        // Check if buyer has left a review for this product
                        $check_review = $conn->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND product_id = ?");
                        $check_review->bind_param("ii", $user_id, $item['product_id']);
                        $check_review->execute();
                        $check_review->store_result();
                        $has_reviewed = $check_review->num_rows > 0;
                        $check_review->close();
                    ?>
                    <div class="order-item">
                        <p><strong><?= htmlspecialchars($item['name']) ?></strong></p>
                        <p>Quantity: <?= $item['quantity'] ?></p>
                        <p>Price: R<?= $item['price'] ?></p>

                        <?php if ($view === 'past'): ?>
                            <?php if (!$has_reviewed): ?>
                                <a href="leave_review.php?product_id=<?= $item['product_id']; ?>" class="review-btn">Leave Review</a>
                            <?php else: ?>
                                <a href="leave_review.php?product_id=<?= $item['product_id']; ?>" class="edit-btn">Edit Review</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
</body>
</html>
