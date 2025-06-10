<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$seller_id = $_SESSION['user_id'];

$sql = "
    SELECT co.order_id, co.order_date, co.completed_date,
           p.name AS product_name, co.quantity, co.price,
           u.username AS buyer_name
    FROM completed_orders co
    JOIN products p ON co.product_id = p.product_id
    JOIN users u ON co.buyer_id = u.user_id
    WHERE co.seller_id = ?
    ORDER BY co.completed_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Completed Orders</title>
    <link href="styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h1>Completed Orders</h1>
    <a href="seller_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Buyer</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Order Date</th>
                    <th>Completed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>R<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td><?php echo $row['completed_date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No completed orders yet.</p>
    <?php endif; ?>
</body>
</html>
