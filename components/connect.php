<?php

$db_name = 'mysql:host=localhost;dbname=eleca_db';
$user_name = 'root';
$user_password = '';

try {
    // Create a new PDO instance
    $conn = new PDO($db_name, $user_name, $user_password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Add a success message for debugging
    // echo "Connection successful!";
} catch (PDOException $e) {
    // Handle connection errors
    echo "Connection failed: " . $e->getMessage();
    exit; // Stop further code execution if the connection fails
}

?>
