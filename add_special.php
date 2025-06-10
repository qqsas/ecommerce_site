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
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Fetch product details
$stmt = $conn->prepare("SELECT name, price, original_price FROM products WHERE product_id = ? AND seller_id = ? AND is_deleted = 0");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "<div class='alert alert-danger'>Invalid product or access denied.</div>";
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_price = floatval($_POST['new_price']);

    if ($new_price < 0) {
        $message = "<div class='alert alert-warning'>Special price cannot be a negative number.</div>";
    } elseif ($new_price >= $product['price']) {
        $message = "<div class='alert alert-warning'>Special price must be lower than the current price (R{$product['price']}).</div>";
    } else {
        if ($product['original_price'] === null) {
            $stmt = $conn->prepare("UPDATE products SET original_price = price, price = ?, is_special = 1 WHERE product_id = ? AND seller_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE products SET price = ?, is_special = 1 WHERE product_id = ? AND seller_id = ?");
        }

        $stmt->bind_param("dii", $new_price, $product_id, $seller_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Special price updated successfully!</div>";
            $product['price'] = $new_price;
        } else {
            $message = "<div class='alert alert-danger'>Failed to update price. Please try again.</div>";
        }
    }
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
    <title>Add Special - <?= htmlspecialchars($product['name']) ?></title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="special-container">
        <h2 class="special-title">Add Special Price for: <strong><?= htmlspecialchars($product['name']) ?></strong></h2>
        
        <div class="price-info">
            <p>Original Price: 
                <strong>
                    <?= $product['original_price'] !== null 
                        ? "R" . number_format($product['original_price'], 2) 
                        : "Not Set" ?>
                </strong>
            </p>
            <p>Current Price: <strong>R<?= number_format($product['price'], 2) ?></strong></p>
        </div>

        <?= $message ?>

        <form method="POST" class="special-form">
            <div class="form-group">
                <label for="new_price">New Special Price (R)</label>
                <input type="number" step="0.01" min="0.01" name="new_price" id="new_price" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="primary-btn">Apply Special</button>
                <a href="seller_dashboard.php" class="secondary-btn">Back to Products</a>
            </div>
        </form>
    </div>
</body>
</html>
