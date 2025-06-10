<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$result = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buyer Dashboard</title>
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h1 class="mb-4">Welcome, Buyer!</h1>
    <a href="logout.php" class="btn btn-danger mb-4">Logout</a>
    <a href="buyer_orders.php" class="btn btn-info mb-3">My Orders</a>

    <h2>Available Products</h2>

    <div class="row">
        <?php while($row = $result->fetch_assoc()): ?>
            <?php
            // Fetch one random review for this product
            $product_id = $row['product_id'];
            $reviewQuery = $conn->prepare("SELECT r.rating, r.comment, u.username 
                                           FROM reviews r 
                                           JOIN users u ON r.user_id = u.user_id 
                                           WHERE r.product_id = ? 
                                           ORDER BY RAND() LIMIT 1");
            $reviewQuery->bind_param("i", $product_id);
            $reviewQuery->execute();
            $reviewResult = $reviewQuery->get_result();
            $review = $reviewResult->fetch_assoc();
            $reviewQuery->close();
            ?>

            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                        <p class="card-text">Price: R<?php echo number_format($row['price'], 2); ?></p>
                        <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>

                        <?php if ($review): ?>
                            <hr>
                            <p><strong>Random Review:</strong></p>
                            <p>⭐ <?php echo str_repeat("★", (int)$review['rating']); ?></p>
                            <p><em>"<?php echo htmlspecialchars($review['comment']); ?>"</em></p>
                            <small>- <?php echo htmlspecialchars($review['username']); ?></small>
                        <?php else: ?>
                            <hr>
                            <p class="text-muted"><em>No reviews yet.</em></p>
                        <?php endif; ?>

                        <a href="add_to_cart.php?product_id=<?php echo $product_id; ?>" class="btn btn-primary mt-2">Add to Cart</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>