<?php
// echo "insert page ";
include('../db_connection/conn.php');

if (isset($_POST['add_product'])) {
    // Get the uploaded file details
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_description = $_POST['product_description'];
    $product_category = $_POST['Product_category'];
    $product_image = null;

    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

    if (!empty($_FILES['product_img']['name'])) {
        $img_name = $_FILES['product_img']['name'];
        $img_tmp = $_FILES['product_img']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($img_ext, $allowed_exts)) {
            $new_img_name = 'product' . time() . ".$img_ext";
            if (move_uploaded_file($img_tmp, "../../images/$new_img_name")) {
                $product_image = $new_img_name; // Set the image path
            } else {
                $message[] = 'Sorry, there was an error uploading your file.';
            }
        } else {
            $message[] = 'Invalid file type! Only JPG, PNG, GIF allowed.';
        }
    }

    $query = "INSERT INTO `product`(`name`, `price`, `description`, `category_id`, `image`) VALUES (:name, :price, :description, :category_id, :image)";
    $statment = $conn->prepare($query);
    $data = [
        'name' => $product_name,
        'price' => $product_price,
        'description' => $product_description,
        'category_id' => $product_category,
        'image' => $product_image // Corrected here
    ];

    $statment->execute($data);
    header('Location: ../index.php?message=add successfully');
} else {
    echo "not insert data";
}
?>