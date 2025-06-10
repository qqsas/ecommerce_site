<?php
session_start();
include 'db.php';
include 'header.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $complaint_text = trim($_POST['complaint']);

    if (!empty($complaint_text)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, complaint, submitted_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $user_id, $complaint_text);
        if ($stmt->execute()) {
            $message = "Your complaint has been submitted. Thank you!";
        } else {
            $message = "Error submitting your complaint. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "Complaint cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
@media (min-width: 769px) {
      .menu-toggle {
        display: none !important;
      }
    }
        </style>
    <meta charset="UTF-8">
    <title>Support</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
<div class="container">
    <h2 class="support-title">Support</h2>
    <p>If you are experiencing an issue or have a concern, please describe it below:</p>

    <?php if ($message): ?>
        <div class="info-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="support.php" class="support-form">
        <div class="form-group">
            <label for="complaint" class="form-label">Your Suggestion/Issue</label>
            <textarea id="complaint" name="complaint" rows="5" required class="form-textarea"></textarea>
        </div>
        <button type="submit" class="submit-button">Submit Complaint</button>
    </form>

    <p class="contact-info">Or contact: 0795109580</p>
</div>
</body>
</html>
