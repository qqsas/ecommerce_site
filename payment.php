<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}
include 'header.php';
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
    <title>Checkout</title>
    <link href="styles.css" rel="stylesheet">
    <link href="mobile.css" rel="stylesheet"  media="(max-width: 768px)">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script>
        function togglePaymentFields() {
            const method = document.getElementById('payment_method').value;
            document.getElementById('eft_section').style.display = (method === 'EFT') ? 'block' : 'none';
            document.getElementById('card_section').style.display = (method === 'Card') ? 'block' : 'none';
        }

        function validateForm(event) {
            const method = document.getElementById('payment_method').value;

            if (method === "Card") {
                const cardNumber = document.getElementById('card_number').value.trim();
                const expiry = document.getElementById('card_expiry').value.trim();
                const cvv = document.getElementById('card_cvv').value.trim();

                const cardPattern = /^\d{16}$/;
                const expiryPattern = /^(0[1-9]|1[0-2])\/\d{2}$/;
                const cvvPattern = /^\d{3}$/;

                if (!cardPattern.test(cardNumber)) {
                    alert("Please enter a valid 16-digit card number.");
                    event.preventDefault();
                    return false;
                }

                if (!expiryPattern.test(expiry)) {
                    alert("Please enter a valid expiry date in MM/YY format.");
                    event.preventDefault();
                    return false;
                }

                if (!cvvPattern.test(cvv)) {
                    alert("Please enter a valid 3-digit CVV.");
                    event.preventDefault();
                    return false;
                }
            }

            if (method === "EFT") {
                const eftRef = document.getElementById('eft_reference').value.trim();
                if (eftRef === "") {
                    alert("Please enter the EFT reference number.");
                    event.preventDefault();
                    return false;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('payment_method').addEventListener('change', togglePaymentFields);
            document.querySelector('form').addEventListener('submit', validateForm);
            togglePaymentFields(); // On load
        });
    </script>
</head>
<body class="checkout-wrapper">
    <h2 class="checkout-title">Checkout</h2>
    <form method="POST" action="place_order.php" class="form-group">
        <!-- Payment Method -->
        <div class="form-section">
            <label for="payment_method" class="form-label">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-group" required>
                <option value="">Select</option>
                <option value="EFT">EFT</option>
                <option value="Card">Card</option>
                <option value="Cash on Delivery">Cash on Delivery</option>
            </select>
        </div>

        <!-- EFT Section -->
        <div id="eft_section" style="display: none;">
            <div class="form-group">
                <label for="eft_reference" class="form-label">EFT Reference Number</label>
                <input type="text" name="eft_reference" id="eft_reference" class="form-input" pattern="\d+" inputmode="numeric" title="Please enter numbers only">
            </div>
        </div>

        <!-- Card Section -->
        <div id="card_section" style="display: none;">
            <div class="form-group">
                <label for="card_number" class="form-label">Card Number</label>
                <input type="text" name="card_number" id="card_number" class="form-input" maxlength="16" pattern="\d{16}" inputmode="numeric" title="16-digit card number">
            </div>
            <div class="form-group">
                <label for="card_expiry" class="form-label">Expiry Date (MM/YY)</label>
                <input type="text" name="card_expiry" id="card_expiry" class="form-input" maxlength="5" pattern="(0[1-9]|1[0-2])\/\d{2}" placeholder="MM/YY" title="Format MM/YY">
            </div>
            <div class="form-group">
                <label for="card_cvv" class="form-label">CVV</label>
                <input type="text" name="card_cvv" id="card_cvv" class="form-input" maxlength="3" pattern="\d{3}" inputmode="numeric" title="3-digit CVV">
            </div>
        </div>

        <!-- Delivery Instructions -->
        <div class="form-group">
            <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
            <textarea name="delivery_instructions" id="delivery_instructions" class="form-textarea" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="delivery_address" class="form-label">Delivery Address</label>
            <textarea name="delivery_address" id="delivery_address" class="form-textarea" rows="3" required></textarea>
        </div>

        <!-- Submit -->
        <div class="form-actions">
            <button type="submit" class="btn">Place Order</button>
            <a href="cart.php" class="back-button">Back to Cart</a>
        </div>
    </form>
</body>
</html>
