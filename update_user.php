<?php
include 'components/connect.php';

session_start();

$user_id = $_SESSION['user_id'] ?? '';

if (!$user_id) {
    header('Location: user_login.php');
    exit();
}

// Alter the database to add bio, location, and profile_image if not already present
$alter_table = "
    ALTER TABLE `user`
    ADD IF NOT EXISTS `bio` TEXT NULL AFTER `email`,
    ADD IF NOT EXISTS `location` VARCHAR(255) NULL AFTER `bio`,
    ADD IF NOT EXISTS `profile_image` VARCHAR(255) DEFAULT 'default.png' AFTER `location`
";

$conn->exec($alter_table);

// Fetch user profile
$fetch_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
$fetch_user->execute([$user_id]);
$fetch_profile = $fetch_user->fetch(PDO::FETCH_ASSOC) ?: [];

// Profile update message
$message = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $bio = filter_var($_POST['bio'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($name) && !empty($email)) {
        $update_profile = $conn->prepare("UPDATE `user` SET name = ?, email = ?, bio = ?, location = ? WHERE id = ?");
        $update_profile->execute([$name, $email, $bio, $location, $user_id]);
        $message[] = 'Profile updated successfully!';
    } else {
        $message[] = 'Name and email cannot be empty!';
    }

    // Handle password change
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_password = $conn->prepare("UPDATE `user` SET password = ? WHERE id = ?");
            $update_password->execute([$hashed_password, $user_id]);
            $message[] = 'Password updated successfully!';
        } else {
            $message[] = 'Passwords do not match!';
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image'])) {
        $image = $_FILES['profile_image'];
        $imageName = time() . '_' . $image['name'];
        $imagePath = 'uploads/' . $imageName;

        if (move_uploaded_file($image['tmp_name'], $imagePath)) {
            // Update the profile image path in the database
            $update_image = $conn->prepare("UPDATE `user` SET profile_image = ? WHERE id = ?");
            $update_image->execute([$imageName, $user_id]);

            // Update the profile immediately after saving the new image
            $fetch_profile['profile_image'] = $imageName;
            $message[] = 'Profile picture updated successfully!';
        } else {
            $message[] = 'Failed to upload image!';
        }
    }

    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        .profile-img img {
            border-radius: 50%;
            transition: all 0.3s ease;
            border: 4px solid #ddd;
        }

        .profile-img img:hover {
            transform: scale(1.1);
            border-color: #007bff;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="profile-container">
        <h2><i class="fa-solid fa-user"></i> My Profile</h2>

        <?php if (!empty($message)): ?>
            <div class="toast-message <?= !empty($message) ? 'show' : ''; ?>">
                <p class="mb-0"><?= htmlspecialchars($message[0]); ?></p>
            </div>
        <?php endif; ?>

        <div class="text-center profile-img">
            <img id="profile-img-preview" src="<?= !empty($fetch_profile['profile_image']) ? 'uploads/' . $fetch_profile['profile_image'] : 'uploads/default.png'; ?>" width="120" height="120" alt="Profile Picture">
        </div>
        <p><strong>Username:</strong> <?= htmlspecialchars($fetch_profile["name"] ?? 'N/A'); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($fetch_profile["email"] ?? 'N/A'); ?></p>
        <p><strong>Bio:</strong> <?= htmlspecialchars($fetch_profile["bio"] ?? 'No bio available'); ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($fetch_profile["location"] ?? 'Not set'); ?></p>

        <h3>Update Profile</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Username</label>
                <input type="text" name="name" value="<?= htmlspecialchars($fetch_profile['name'] ?? ''); ?>" required class="form-control">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($fetch_profile['email'] ?? ''); ?>" required class="form-control">
            </div>

            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea name="bio" class="form-control"><?= htmlspecialchars($fetch_profile['bio'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($fetch_profile['location'] ?? ''); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="profile_image" class="form-label">Profile Picture</label>
                <input type="file" name="profile_image" accept="image/*" class="form-control" id="profile_image">
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control">
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
        </form>
    </div>
  
</div>
   <?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
