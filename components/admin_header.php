<?php
include '../components/connect.php';

// Check if a session is not already active before starting one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id'] ?? null;

if (!$admin_id) {
    header('location:../admin/admin_login.php');
    exit();
}

// Fetch admin details from the user table where role = 'admin'
$select_profile = $conn->prepare("SELECT * FROM `user` WHERE id = ? AND role = 'admin'");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

if (!$fetch_profile) {
    session_destroy();
    header('location:../admin/admin_login.php');
    exit();
}

?>

<header class="header">
    <section class="flex">
        <a href="../admin/dashboard.php" class="logo">Admin<span>Panel</span></a>

        <nav class="navbar">
            <a href="../admin/dashboard.php">Home</a>
            <a href="../admin/products.php">Products</a>
            <a href="../admin/placed_orders.php">Orders</a>
            <a href="../admin/admin_accounts.php">Admins</a>
            <a href="../admin/users_accounts.php">Users</a>
            <a href="../admin/messages.php">Messages</a>
            <a href="../admin/change_user_role.php">Change Role</a> <!-- New Link for Change Role Page -->
        </nav>

        <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <div id="user-btn" class="fas fa-user"></div>
        </div>

        <div class="profile">
            <p><?= htmlspecialchars($fetch_profile['user_name']); ?></p>
            <a href="../admin/update_profile.php" class="btn">Update Profile</a>
            <div class="flex-btn">
                <a href="../admin/register_admin.php" class="option-btn">Register</a>
                <a href="../admin/admin_login.php" class="option-btn">Login</a>
            </div>
            <a href="../components/admin_logout.php" class="delete-btn" onclick="return confirm('Logout from the website?');">Logout</a>
        </div>
    </section>
</header>
