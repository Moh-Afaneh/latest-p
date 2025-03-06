<?php

if(isset($_POST['add_to_wishlist'])){

   if(empty($user_id)){
      header('location:user_login.php');
      exit();
   }else{

      $pid = $_POST['pid'];
      $pid = filter_var($pid, FILTER_SANITIZE_STRING);
      $name = $_POST['name'];
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $price = $_POST['price'];
      $price = filter_var($price, FILTER_SANITIZE_STRING);
      $image = $_POST['image'];
      $image = filter_var($image, FILTER_SANITIZE_STRING);

      // Check if the product is already in the wishlist
      $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE pid = ? AND user_id = ?");
      $check_wishlist_numbers->execute([$pid, $user_id]);

      // Check if the product is already in the cart
      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ?");
      $check_cart_numbers->execute([$pid, $user_id]);

      if($check_wishlist_numbers->rowCount() > 0){
         $message[] = 'already added to wishlist!';
      }elseif($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{
         // Insert product into wishlist
         $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
         $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
         $message[] = 'added to wishlist!';
      }

   }

}

if(isset($_POST['add_to_cart'])){

   if(empty($user_id)){
      header('location:user_login.php');
      exit();
   }else{

      $pid = $_POST['pid'];
      $pid = filter_var($pid, FILTER_SANITIZE_STRING);
      $name = $_POST['name'];
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $price = $_POST['price'];
      $price = filter_var($price, FILTER_SANITIZE_STRING);
      $image = $_POST['image'];
      $image = filter_var($image, FILTER_SANITIZE_STRING);
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_STRING);

      // Check if the product is already in the cart
      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ?");
      $check_cart_numbers->execute([$pid, $user_id]);

      if($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{

         // Check if the product is in the wishlist
         $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE pid = ? AND user_id = ?");
         $check_wishlist_numbers->execute([$pid, $user_id]);

         if($check_wishlist_numbers->rowCount() > 0){
            // Remove the product from wishlist if it is already there
            $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ? AND user_id = ?");
            $delete_wishlist->execute([$pid, $user_id]);
         }

         // Insert product into cart
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'added to cart!';
         
      }

   }

}

?>
