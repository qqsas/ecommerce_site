<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';
include 'header2.php';
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
    <title>Seller Dashboard</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="page-container">
    <div class="product-card">
        <h2 class="dashboard-title">Welcome, Seller!</h2>
        <p class="dashboard-subtitle">Manage your products and view recent orders from your dashboard.</p>

        <div class="dashboard-links">
            <a href="add_product.php" class="btn">Add New Product</a>
            <a href="seller_products.php" class="btn">Manage Products</a>
            <a href="seller_orders.php" class="btn">View Orders</a>
        </div>
    </div>
</body>
</html>
