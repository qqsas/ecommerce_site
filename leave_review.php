<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
        // Redirect to orders page after success
    header("Location: buyer_orders.php");
    exit();

}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Check if the user purchased this product
$check = $conn->prepare("
    SELECT oi.product_id 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE o.user_id = ? AND oi.product_id = ?
");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$check->store_result();

if ($check->num_rows == 0) {
    die("You can't review a product you haven't purchased.");
}
$check->close();

// Fetch existing review if any
$existing_review = null;
$check_review = $conn->prepare("SELECT review_id, rating, comment FROM reviews WHERE user_id = ? AND product_id = ?");
$check_review->bind_param("ii", $user_id, $product_id);
$check_review->execute();
$check_review->store_result();

if ($check_review->num_rows > 0) {
    $check_review->bind_result($review_id, $existing_rating, $existing_comment);
    $check_review->fetch();
    $existing_review = [
        'id' => $review_id,
        'rating' => $existing_rating,
        'comment' => $existing_comment
    ];
}
$check_review->close();

$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($existing_review) {
        // Update review
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ?");
        $stmt->bind_param("isi", $rating, $comment, $existing_review['id']);
        $stmt->execute();
    } else {
        // Insert new review
        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
        $stmt->execute();
    }

    $stmt->close();

    // Redirect to orders page after success
    header("Location: buyer_orders.php");
    exit();
}


// If redirected after successful submission
if (isset($_GET['success'])) {
    $success_message = $existing_review ? "Review updated successfully!" : "Review submitted successfully!";
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
    <title><?= $existing_review ? 'Edit Review' : 'Leave a Review' ?></title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="container">
    <h2><?= $existing_review ? 'Edit Your Review' : 'Leave a Review' ?></h2>

    <?php if ($success_message): ?>
        <div class="alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <form method="POST" class="form-group">
        <div class="form-groupc">
            <label for="rating">Rating (1–5 Stars)</label>
            <select name="rating" id="rating" required>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= (isset($existing_review['rating']) && $existing_review['rating'] == $i) ? 'selected' : '' ?>>
                        <?= $i ?> Star<?= $i > 1 ? 's' : '' ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-groupc">
            <label for="comment">Comment</label>
            <textarea name="comment" id="comment" required><?= htmlspecialchars($existing_review['comment'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="submit-button"><?= $existing_review ? 'Update Review' : 'Submit Review' ?></button>
    </form>
</body>
</html>
