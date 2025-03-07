<?php
include 'components/connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:user_login.php');
    exit();
}

// Check for order success message
$order_success_message = '';
if (isset($_SESSION['order_success'])) {
    $order_success_message = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = $conn->prepare("SELECT * FROM user WHERE id = ?");
$user_query->execute([$user_id]);
$user_data = $user_query->fetch(PDO::FETCH_ASSOC);

// Coupon configuration
$coupon_code = "DISCOUNT10";
$discount_percentage = 10;

// Ensure coupon exists
$check_coupon = $conn->prepare("SELECT id FROM coupon WHERE name = ?");
$check_coupon->execute([$coupon_code]);

if ($check_coupon->rowCount() == 0) {
    $insert_coupon = $conn->prepare("INSERT INTO coupon (name, start_date, exp_date) VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
    $insert_coupon->execute([$coupon_code]);
}

// Initialize variables
$discount = 0;
$coupon_id = null;
$message = "";

// Handle coupon application
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

// Fetch and calculate orders
$grand_total = 0;
$orders = [];
$order_ids = [];

// Get current product prices
$select_orders = $conn->prepare("
    SELECT o.id, oi.product_id, p.price AS product_price, 
           oi.quantity, p.name 
    FROM `order` o 
    JOIN order_item oi ON o.id = oi.order_id 
    JOIN product p ON oi.product_id = p.id 
    WHERE o.user_id = ? AND o.status = 'pending'
");
$select_orders->execute([$user_id]);
$orders = $select_orders->fetchAll(PDO::FETCH_ASSOC);

// Calculate grand total with current prices
foreach ($orders as $order) {
    if (!in_array($order['id'], $order_ids)) {
        $order_ids[] = $order['id'];
    }
    $grand_total += floatval($order['product_price']) * intval($order['quantity']);
}

// Apply discount
if ($discount > 0) {
    $discount_amount = $grand_total * ($discount / 100);
    $grand_total = max(0, $grand_total - $discount_amount);
}

// Handle order placement
if (isset($_POST['place_order'])) {
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);

    if (!empty($name) && !empty($phone_number) && !empty($address) && !empty($payment_method)) {
        try {
            $conn->beginTransaction();

            // Insert new confirmed order
            $insert_order = $conn->prepare("INSERT INTO `order` 
                (user_id, total_price, status, coupon_id) 
                VALUES (?, ?, 'confirmed', ?)");
            $insert_order->execute([$user_id, $grand_total, $coupon_id]);
            $order_id = $conn->lastInsertId();

            // Insert order items with current prices
            foreach ($orders as $order) {
                $insert_order_item = $conn->prepare("INSERT INTO order_item 
                    (order_id, product_id, price, quantity) 
                    VALUES (?, ?, ?, ?)");
                $insert_order_item->execute([
                    $order_id,
                    $order['product_id'],
                    $order['product_price'],
                    $order['quantity']
                ]);
            }

            // Update user details
            $update_user = $conn->prepare("UPDATE user 
                SET name = ?, phone_number = ?, address = ? 
                WHERE id = ?");
            $update_user->execute([$name, $phone_number, $address, $user_id]);

            // Mark old pending orders as processed
            if (!empty($order_ids)) {
                $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
                $update_orders = $conn->prepare("UPDATE `order` 
                    SET status = 'processed' 
                    WHERE id IN ($placeholders)");
                $update_orders->execute($order_ids);
            }

            $conn->commit();
            
            // Set success message and redirect
            $_SESSION['order_success'] = "🎉 Order #$order_id confirmed! Your items are on the way!";
            header('Location: checkout.php');
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "⚠️ Error processing order: " . $e->getMessage();
        }
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
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
        }
        .checkmark-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        .modal-button {
            background: #4CAF50;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 1rem;
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
                    <p><?= htmlspecialchars($order['name']) ?> x <?= $order['quantity'] ?> - $<?= number_format($order['product_price'], 2) ?></p>
                <?php endforeach; ?>
                <p class="grand-total">Total: $<?= number_format($grand_total, 2) ?></p>
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
            <p class="alert"><?= $message ?></p>
        <?php endif; ?>

        <h3>Enter Your Details</h3>
        <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required>
        <input type="text" name="phone_number" placeholder="Phone Number" value="<?= htmlspecialchars($user_data['phone_number'] ?? '') ?>" required>
        <input type="text" name="address" placeholder="Shipping Address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>" required>
        <select name="payment_method">
            <option value="credit_card">Credit Card</option>
            <option value="paypal">PayPal</option>
            <option value="cash_on_delivery">Cash on Delivery</option>
        </select>
        <button type="submit" name="place_order" class="btn">Place Order</button>
    </form>
</section>

<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="checkmark-icon">✔️</div>
        <p><?= htmlspecialchars($order_success_message) ?></p>
        <button class="modal-button" onclick="document.getElementById('orderModal').style.display = 'none';">Continue Shopping</button>
    </div>
</div>

<script>
    <?php if (!empty($order_success_message)): ?>
        document.getElementById("orderModal").style.display = "flex";
    <?php endif; ?>
</script>

</body>
</html>