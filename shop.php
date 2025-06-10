<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

// Fetch products (only available, not deleted, and from sellers)
$sql = "
    SELECT p.product_id, p.name, p.description, p.price, u.username AS seller_name
    FROM products p
    JOIN users u ON p.seller_id = u.user_id
    WHERE p.stock > 0 AND p.is_deleted = 0
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

    <h1 class="mb-4">Shop Products</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                            <p><strong>Price:</strong> R<?php echo number_format($row['price'], 2); ?></p>
                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_name']); ?></p>
                        </div>
                        <div class="card-footer">
                            <a href="add_to_cart.php?product_id=<?php echo $row['product_id']; ?>" class="btn btn-primary w-100">
                                Add to Cart
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No products available at the moment.</p>
    <?php endif; ?>

</body>
</html>