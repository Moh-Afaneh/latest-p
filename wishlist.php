<?php
session_start();
include 'components/connect.php';

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// Sample fallback products
$sampleProducts = [
    ['id' => 1, 'product_name' => 'Apple MacBook Pro 16"', 'price' => 2499.99, 'image' => 'images/icon-1.png', 'added_on' => '2025-03-01'],
    ['id' => 2, 'product_name' => 'Sony WH-1000XM5 Headphones', 'price' => 349.99, 'image' => 'images/icon-2.png', 'added_on' => '2025-03-02'],
    ['id' => 3, 'product_name' => 'Samsung Galaxy S23 Ultra', 'price' => 1199.00, 'image' => 'images/icon-3.png', 'added_on' => '2025-03-03']
];

$wishlistItems = [];

// Fetch real wishlist if user is logged in
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT 
            p.id AS id,
            p.name,
            p.price,
            wi.id AS wishlist_item_id
        FROM 
            wishlist w
        JOIN 
            wishlist_item wi ON w.id = wi.wishlist_id
        JOIN 
            product p ON wi.product_id = p.id
        WHERE 
            w.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If wishlist is empty, fallback to sample data (for guests or empty wishlist)
if (empty($wishlistItems)) {
    $wishlistItems = $sampleProducts;
    $isSampleData = true;
} else {
    $isSampleData = false;
}

// Handle item removal (logged-in users only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $product_id = intval($_POST['delete_item']);

    if ($user_id && !$isSampleData) {
        // Remove from real wishlist in database
        $deleteStmt = $conn->prepare("
            DELETE wi FROM wishlist_item wi
            JOIN wishlist w ON wi.wishlist_id = w.id
            WHERE wi.product_id = ? AND w.user_id = ?
        ");
        $deleteStmt->execute([$product_id, $user_id]);
    } else {
        // Remove from session wishlist (fallback case for guests)
        $_SESSION['wishlist'] = array_values(array_filter($wishlistItems, fn($item) => $item['id'] != $product_id));
        $wishlistItems = $_SESSION['wishlist'];
    }

    header('Location: wishlist.php');
    exit;
}

// Calculate total price
$totalPrice = array_sum(array_column($wishlistItems, 'price'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Wishlist</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/wishlist.css">
</head>
<body>

<div class="wishlist-container">
    <div class="wishlist-card">
        <h3 class="text-center mb-4">Your Wishlist</h3>

        <?php if (count($wishlistItems) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered text-center wishlist-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Image</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wishlistItems as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></td>
                                <td>
                                    <img src="<?php echo $item['image'] ?? 'images/placeholder.png'; ?>" class="product-image" alt="Product Image">
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="action-form">
                                        <button type="submit" name="delete_item" value="<?php echo $item['product_id'] ?? $item['id']; ?>" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-box">
                Total Price: <strong>$<?php echo number_format($totalPrice, 2); ?></strong>
            </div>

        <?php else: ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-heart-broken"></i> Your wishlist is empty!
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="shop.php" class="btn btn-outline-primary">
                <i class="fas fa-store"></i> Back to Shop
            </a>
        </div>
    </div>
    
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>