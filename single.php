<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Database connection
include 'components/connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id']; // Retrieve user ID from session

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM product WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch product images
$stmt_images = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$stmt_images->execute([$product_id]);
$images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);

if (!$product) {
    die("Product not found.");
}

if (!$images) {
    $images = ['https://placehold.co/600x600'];  // Placeholder image
}

// Add product to order
if (isset($_POST['add_to_cart'])) {
    $quantity = (int)$_POST['quantity'];

    // Check if the user has a pending order
    $stmt = $conn->prepare("SELECT id FROM `order` WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        // Create a new order
        $stmt = $conn->prepare("INSERT INTO `order` (user_id, total_price, status, created_at) VALUES (?, 0, 'pending', NOW())");
        $stmt->execute([$user_id]);
        $order_id = $conn->lastInsertId();
    } else {
        $order_id = $order['id'];
    }

    // Check if product is already in order
    $stmt = $conn->prepare("SELECT id, quantity FROM order_item WHERE order_id = ? AND product_id = ?");
    $stmt->execute([$order_id, $product_id]);
    $order_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order_item) {
        // Update quantity
        $new_quantity = $order_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE order_item SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $order_item['id']]);
    } else {
        // Insert new order item
        $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $product['price'], $quantity]);
    }

    // Update total order price
    $stmt = $conn->prepare("UPDATE `order` SET total_price = (SELECT SUM(price * quantity) FROM order_item WHERE order_id = ?) WHERE id = ?");
    $stmt->execute([$order_id, $order_id]);

    echo "Product added to order!";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>

    <style>
        :root {
            --background-color: #f5f7fa;
            --text-color: #1a2b5d;
            --accent-color: #3f7ccf;
            --border-color: #d1d9e6;
            --card-bg-color: #ffffff;
            --input-bg-color: #f0f4f9;
            --button-bg-color: #3f7ccf;
            --button-hover-bg-color: #305ea8;
            --danger-color: #e74c3c;
            --danger-hover-color: #c0392b;
        }

        * {
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--background-color);
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .product-container {
            display: flex;
            gap: 40px;
            padding: 30px;
            max-width: 1200px;
            background: var(--card-bg-color);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Image Gallery */
        .image-gallery {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .main-image {
            width: 450px;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .thumbnail-container {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .thumbnail-container img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            cursor: pointer;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: transform 0.2s, border 0.2s;
        }

        .thumbnail-container img:hover {
            transform: scale(1.1);
            border: 2px solid var(--accent-color);
        }

        /* Product Info */
        .product-info {
            flex: 1;
            padding: 20px;
        }

        .product-info h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .product-price {
            font-size: 24px;
            color: var(--accent-color);
            font-weight: bold;
            margin-bottom: 15px;
        }

        .product-info p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .quantity-input {
            width: 60px;
            padding: 8px;
            font-size: 16px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            text-align: center;
            background: var(--input-bg-color);
        }

        .add-to-cart-btn {
            background-color: var(--button-bg-color);
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .add-to-cart-btn:hover {
            background-color: var(--button-hover-bg-color);
        }

        @media (max-width: 768px) {
            .product-container {
                flex-direction: column;
                align-items: center;
                padding: 20px;
            }

            .main-image {
                width: 100%;
                height: auto;
            }

            .thumbnail-container {
                justify-content: center;
                flex-wrap: wrap;
            }

            .product-info {
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <div class="product-container">
        <div class="image-gallery">
            <div class="main-image">
                <img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="Product Image">
            </div>
            <div class="thumbnail-container">
                <?php foreach ($images as $image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Thumbnail" onclick="changeMainImage(this)">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-info">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
            <form method="POST">
                <input class="quantity-input" type="number" name="quantity" value="1" min="1" required>
                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Order</button>
            </form>
        </div>
    </div>

    <script>
        function changeMainImage(thumbnail) {
            document.getElementById('mainImage').src = thumbnail.src;
        }
    </script>

</body>
</html>
