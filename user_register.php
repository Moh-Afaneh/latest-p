<?php
include 'components/connect.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

$message = []; // Ensure $message is always an array

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']); // Capture phone number
    $pass = trim($_POST['pass']);
    $cpass = trim($_POST['cpass']);

    // Validate Name
    if (empty($name)) {
        $message[] = '<p style="color: red;">Username is required!</p>';
    } elseif (strlen($name) < 3) {
        $message[] = '<p style="color: red;">Username must be at least 3 characters!</p>';
    } elseif (!preg_match("/^[a-zA-Z0-9_ ]+$/", $name)) {
        $message[] = '<p style="color: red;">Username can only contain letters, numbers, and spaces!</p>';
    }

    // Validate Email
    if (empty($email)) {
        $message[] = '<p style="color: red;">Email is required!</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = '<p style="color: red;">Invalid email format!</p>';
    }

    // Validate Phone Number
    if (empty($phone_number)) {
        $message[] = '<p style="color: red;">Phone number is required!</p>';
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone_number)) {
        $message[] = '<p style="color: red;">Invalid phone number format! (Only numbers allowed, length 10-15)</p>';
    }

    // Validate Password
    if (empty($pass)) {
        $message[] = '<p style="color: red;">Password is required!</p>';
    } elseif (strlen($pass) < 8) {
        $message[] = '<p style="color: red;">Password must be at least 8 characters!</p>';
    } elseif (!preg_match("/[A-Za-z]/", $pass) || !preg_match("/[0-9]/", $pass)) {
        $message[] = ' <p style="color: red;">Password must contain both letters and numbers!</p>';
    }

    // Validate Confirm Password
    if (empty($cpass)) {
        $message[] = '<p style="color: red;">Please confirm your password!</p>';
    } elseif ($pass !== $cpass) {
        $message[] = '<p style="color: red;">Passwords do not match!</p>';
    }

    // Only proceed if there are no validation errors
    if (empty($message)) {
        // Check if email already exists
        $select_user = $conn->prepare("SELECT * FROM `user` WHERE email = ?");
        $select_user->execute([$email]);

        if ($select_user->rowCount() > 0) {
            $message[] = '<p style="color: red;">Email already exists!</p>';
        } else {
            // Hash the password
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

            // Insert user into the database (phone_number column is used here)
            $insert_user = $conn->prepare("INSERT INTO `user` (name, email, phone_number, password, role) VALUES (?, ?, ?, ?, 'user')");
            $insert_user->execute([$name, $email, $phone_number, $hashed_pass]);

            $message[] = '<p style="color: green;">Registered successfully, login now!</p>';

            // Redirect to login page after successful registration
            header('Location: user_login.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="form-container">
    <form action="" method="post">
        <h3>Register Now</h3>

        <?php
        // Ensure that $message is always an array and not a string
        if (is_array($message) && !empty($message)) {
            foreach ($message as $msg) {
                echo '<p class="error-msg">' . htmlspecialchars($msg) . '</p>';
            }
        }
        ?>

        <input type="text" name="name" required placeholder="Enter your username" maxlength="20" class="box" value="<?= htmlspecialchars($name ?? '') ?>">
        <input type="email" name="email" required placeholder="Enter your email" maxlength="50" class="box" value="<?= htmlspecialchars($email ?? '') ?>" oninput="this.value = this.value.replace(/\s/g, '')">
        <input type="text" name="phone_number" required placeholder="Enter your phone number" maxlength="15" class="box" value="<?= htmlspecialchars($phone_number ?? '') ?>">
        <input type="password" name="pass" required placeholder="Enter your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
        <input type="password" name="cpass" required placeholder="Confirm your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
        <input type="submit" style="color:white;" value="Register Now" class="btn" name="submit">
        
        <p>Already have an account?</p>
        <a href="user_login.php" style="color:white;" class="option-btn">Login Now</a>
    </form>
</section>
<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>

</body>
</html>
