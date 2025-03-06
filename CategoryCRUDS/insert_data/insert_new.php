<?php
// echo "inser page ";
include('../db_connection/conn.php');

if(isset($_POST['add_category'])){
    // echo " insert data";
    $category_name=$_POST['category_name'];
    $category_img = null;

    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

    if (!empty($_FILES['category_image']['name'])) {
        $img_name = $_FILES['category_image']['name'];
        $img_tmp = $_FILES['category_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($img_ext, $allowed_exts)) {
            $new_img_name = 'category' . time() . ".$img_ext";
            if (move_uploaded_file($img_tmp, "../images/$new_img_name")) {
                $category_img = $new_img_name; // Set the image path
            } else {
                $message[] = 'Sorry, there was an error uploading your file.';
            }
        } else {
            $message[] = 'Invalid file type! Only JPG, PNG, GIF allowed.';
        }
    }

    $query="INSERT INTO `category`(`name`, `image`) VALUES (:name,:image)";
    $statment=$conn->prepare($query);
    $data=[
        'name'=>$category_name,
        'image'=>$category_img
    ];
    $statment->execute($data);
    header('location:../index.php?message=add sucesssfuly');



}else{
    echo "not insert data";
}
?>