<?php
include 'components/connect.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('location:user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->execute([$user_id]);
$user_data = $user_query->fetch(PDO::FETCH_ASSOC);

// Coupon details
$coupon_code = "DISCOUNT10";
$discount_percentage = 10;

// Check if the coupon exists
$check_coupon = $conn->prepare("SELECT id FROM coupon WHERE name = ?");
$check_coupon->execute([$coupon_code]);

if ($check_coupon->rowCount() == 0) {
    $insert_coupon = $conn->prepare("INSERT INTO coupon (name, start_date, exp_date) VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
    $insert_coupon->execute([$coupon_code]);
}

// Handle coupon application
$discount = 0;
$coupon_id = null;
$message = "";

if (isset($_POST['apply_coupon'])) {
    $entered_coupon = trim($_POST['coupon_code']);

    if (!empty($entered_coupon)) {
        $check_coupon = $conn->prepare("SELECT id FROM coupon WHERE name = ? AND exp_date >= CURDATE()");
        $check_coupon->execute([$entered_coupon]);

        if ($check_coupon->rowCount() > 0) {
            $coupon_data = $check_coupon->fetch(PDO::FETCH_ASSOC);
            $coupon_id = $coupon_data['id'];
            $discount = $discount_percentage;
            $message = "🎉 Coupon applied successfully! You saved $discount%.";
        } else {
            $message = "⚠️ Invalid or expired coupon.";
        }
    } else {
        $message = "⚠️ Please enter a coupon code.";
    }
}

// Fetch user's pending orders
$grand_total = 0;
$orders = [];

$select_orders = $conn->prepare("
    SELECT o.id, o.total_price, oi.product_id, oi.price, oi.quantity, p.product_name 
    FROM orders o 
    JOIN order_item oi ON o.id = oi.order_id 
    JOIN product p ON oi.product_id = p.id 
    WHERE o.user_id = ? AND o.status = 'pending'
");
$select_orders->execute([$user_id]);
$orders = $select_orders->fetchAll(PDO::FETCH_ASSOC);

// Calculate grand total
foreach ($orders as $order) {
    $grand_total += floatval($order['price']) * intval($order['quantity']);
}

// Apply discount
if ($discount > 0) {
    $grand_total = max(0, $grand_total - ($grand_total * ($discount / 100)));
}

// Place order
if (isset($_POST['place_order']) && count($orders) > 0) {
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);

    if (!empty($name) && !empty($phone_number) && !empty($address) && !empty($payment_method)) {
        $status = 'pending';
        $insert_order = $conn->prepare("INSERT INTO orders (user_id, total_price, status, coupon_id) VALUES (?, ?, ?, ?)");
        $insert_order->execute([$user_id, $grand_total, $status, $coupon_id]);

        $order_id = $conn->lastInsertId();

        // Insert order items
        foreach ($orders as $order) {
            $insert_order_item = $conn->prepare("INSERT INTO order_item (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
            $insert_order_item->execute([$order_id, $order['product_id'], $order['price'], $order['quantity']]);
        }

        // Update order status
        $update_status = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $update_status->execute([$order_id]);

        $message = "🎉 Your order is confirmed! It's on the way. 🚀";
        echo "<script>document.getElementById('orderModal').style.display = 'flex';</script>";
    } else {
        $message = "⚠️ Please fill in all fields before placing your order.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            width: 300px;
        }
        .checkmark {
            font-size: 50px;
            color: green;
        }
        .modal-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<section class="checkout-container">
    <form method="POST">
        <h3>Your Orders</h3>
        <div class="order-summary">
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <p><?= htmlspecialchars($order['product_name']); ?> x <?= $order['quantity']; ?> - $<?= number_format($order['price'], 2); ?></p>
                <?php endforeach; ?>
                <p class="grand-total">Total: $<?= number_format($grand_total, 2); ?></p>
            <?php else: ?>
                <p class="empty">Your order list is empty!</p>
            <?php endif; ?>
        </div>

        <h3>Apply Coupon</h3>
        <div class="coupon-section">
            <input type="text" name="coupon_code" placeholder="Enter coupon code">
            <button type="submit" name="apply_coupon">Apply</button>
        </div>
        <?php if (!empty($message)): ?>
            <p class="alert"><?= $message; ?></p>
        <?php endif; ?>

        <h3>Enter Your Details</h3>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="phone_number" placeholder="Phone Number" required>
        <input type="text" name="address" placeholder="Shipping Address" required>
        <select name="payment_method">
            <option value="credit_card">Credit Card</option>
            <option value="paypal">PayPal</option>
            <option value="cash_on_delivery">Cash on Delivery</option>
        </select>
        <button type="submit" name="place_order">Place Order</button>
    </form>
</section>

<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="checkmark">✔️</div>
        <p>Your order has been placed successfully. 🚀</p>
        <button class="modal-button" onclick="document.getElementById('orderModal').style.display = 'none';">Close</button>
    </div>
</div>

<script>
    <?php if (isset($message) && strpos($message, "confirmed") !== false): ?>
        document.getElementById("orderModal").style.display = "flex";
    <?php endif; ?>
</script>

</body>
</html>