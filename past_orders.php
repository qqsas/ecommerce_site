<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$buyer_id = $_SESSION['user_id'];

$sql = "
    SELECT co.order_id, co.order_date, co.completed_date,
           p.name AS product_name, co.quantity, co.price,
           u.username AS seller_name, co.product_id
    FROM completed_orders co
    JOIN products p ON co.product_id = p.product_id
    JOIN users u ON co.seller_id = u.user_id
    WHERE co.buyer_id = ?
    ORDER BY co.completed_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
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
    <title>Past Orders</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Past Orders</h1>
        <a href="buyer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Order Date</th>
                        <th>Completed Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['seller_name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>R<?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['order_date']; ?></td>
                            <td><?php echo $row['completed_date']; ?></td>
                            <td>
                                <!-- Prepare for future Buy Again button -->
                                <form method="get" action="product_details.php">
                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Buy Again</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No past orders found.</p>
    <?php endif; ?>
</body>
</html>