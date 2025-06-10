<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="custom-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="seller_dashboard.php">Dashboard</a>

        <!-- ✅ Mobile toggle button -->
        <button class="menu-toggle" onclick="document.querySelector('.seller-nav-list').classList.toggle('active')">
            ☰
        </button>

        <!-- ✅ Seller-specific nav links -->
        <ul class="seller-nav-list">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seller'): ?>
                <li class="nav-item"><a class="nav-link" href="add_product.php">Add Product</a></li>
                <li class="nav-item"><a class="nav-link" href="seller_products.php">My Products</a></li>
                <li class="nav-item"><a class="nav-link" href="deleted_products.php">Deleted Products</a></li>
                <li class="nav-item"><a class="nav-link" href="seller_orders.php">Orders</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
