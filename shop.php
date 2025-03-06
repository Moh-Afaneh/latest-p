<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
} else {
   header('location:user_login.php');
   exit();
}

// include 'components/wishlist_cart.php';

if(isset($_POST['add_to_wishlist'])){
   $pid = $_POST['pid'];

   $select_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE id = ?");
   $select_wishlist->execute([$user_id]);
   if($select_wishlist->rowCount() > 0){
      $wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC);
      $wishlist_id = $wishlist['id'];
   } else {
      $insert_wishlist = $conn->prepare("INSERT INTO wishlist (user_id) VALUES (?)");
      $insert_wishlist->execute([$user_id]);
      $wishlist_id = $conn->lastInsertId();
   }


   $check_wishlist_item = $conn->prepare("SELECT * FROM wishlist_item WHERE wishlist_id = ? AND id = ?");
   $check_wishlist_item->execute([$wishlist_id, $pid]);
   if($check_wishlist_item->rowCount() > 0){
      echo 'Product already exists in your wishlist';  
      exit();
   } else {
      $insert_item = $conn->prepare("INSERT INTO wishlist_item (wishlist_id, id) VALUES (?, ?)");
      $insert_item->execute([$wishlist_id, $pid]);
      echo 'success';
      exit();
   }
}

if(isset($_POST['add_to_order'])){
   $pid = $_POST['pid'];


   $select_order = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'pending'");
   $select_order->execute([$user_id]);
   if($select_order->rowCount() > 0){
      $order = $select_order->fetch(PDO::FETCH_ASSOC);
      $order_id = $order['id'];
   } else {
      $insert_order = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'pending')");
      $insert_order->execute([$user_id]);
      $order_id = $conn->lastInsertId();
   }

  
   $check_order_item = $conn->prepare("SELECT * FROM order_item WHERE order_id = ? AND id = ?");
   $check_order_item->execute([$order_id, $pid]);
   if($check_order_item->rowCount() > 0){
      echo 'Product already exists in your order';  
      exit();
   } else {
      $insert_order_item = $conn->prepare("INSERT INTO order_item (order_id, id, quantity) VALUES (?, ?, 1)");
      $insert_order_item->execute([$order_id, $pid]);
      echo 'success';  
      exit();
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/shop.css">
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <style>
      body {
         font-family: 'Arial', sans-serif;
         margin: 0;
         padding: 0;
         background-color: #f4f6f9;
         color: #333;
      }

      header {
         background-color: #007bff;
         color: white;
         padding: 20px 0;
         text-align: center;
         font-size: 2em;
         font-weight: bold;
      }

      .categories, .products {
         margin: 50px auto;
         padding: 0 15px;
         max-width: 1200px;
      }

      .heading {
         font-size: 2.5em;
         text-align: center;
         margin-bottom: 30px;
         color: #007bff;
         font-weight: bold;
      }

      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
         gap: 20px;
         justify-content: center;
         margin-top: 20px;
      }

      .box {
         background-color: #fff;
         border-radius: 10px;
         box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
         overflow: hidden;
         transition: transform 0.3s ease;
      }

      .box:hover {
         transform: translateY(-10px);
         box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
      }

      .box img {
         width: 100%;
         height: 200px;
         object-fit: cover;
         transition: transform 0.3s ease;
      }

      .box:hover img {
         transform: scale(1.05);
      }

      .name {
         padding: 10px;
         background-color: #007bff;
         color: white;
         text-align: center;
         font-size: 1.3em;
         font-weight: bold;
         text-transform: uppercase;
         letter-spacing: 1px;
      }

      .product-box {
         background-color: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
         transition: transform 0.3s ease;
         text-align: center;
      }

      .product-box:hover {
         transform: translateY(-10px);
         box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
      }

      .product-box img {
         width: 100%;
         height: 250px;
         object-fit: cover;
         border-radius: 10px;
         transition: transform 0.3s ease;
      }

      .product-box .name {
         font-size: 1.4em;
         margin-top: 15px;
         color: #333;
      }

      .product-box p {
         font-size: 1.2em;
         color: #007bff;
         margin-bottom: 20px;
      }

      .wishlist-btn, .add-to-order-btn {
         width: 100%;
         padding: 12px;
         font-size: 1.1em;
         background-color: #007bff;
         color: white;
         border: none;
         border-radius: 5px;
         cursor: pointer;
         transition: background-color 0.3s, transform 0.3s;
         margin-top: 10px;
         margin-bottom: 5px;
         font-weight: bold;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

      .wishlist-btn:hover, .add-to-order-btn:hover {
         background-color: #0056b3;
         transform: scale(1.05);
      }

      .wishlist-btn i, .add-to-order-btn i {
         margin-right: 8px;
      }

      footer {
         background-color: #007bff;
         color: white;
         padding: 20px 0;
         text-align: center;
         font-size: 1.2em;
      }
   </style>
</head>
<body>
<?php include 'components/user_header.php'; ?>
<header>
   Welcome to Our Shop
</header>



<section class="categories container">
   <h1 class="heading">Categories</h1>
   <div class="box-container">
      <?php
         $select_categories = $conn->prepare("SELECT * FROM category"); 
         $select_categories->execute();
         while($fetch_category = $select_categories->fetch(PDO::FETCH_ASSOC)){
      ?>
      <a href="#category_<?= $fetch_category['id']; ?>" class="box">
         <img src="uploaded_img/<?= $fetch_category['image'] ?: 'default_category.png'; ?>" alt="Category Image">
         <div class="name"> <?= $fetch_category['name']; ?> </div>
      </a>
      <?php } ?>
   </div>
</section>

<section class="products container">
   <?php
      $select_categories->execute();
      while($fetch_category = $select_categories->fetch(PDO::FETCH_ASSOC)){
   ?>
   <h2 class="heading">Products in <?= $fetch_category['name']; ?> Category</h2>
   <div class="box-container">
      <?php
         $select_products = $conn->prepare("SELECT * FROM product WHERE id = ?");
         $select_products->execute([$fetch_category['id']]);
         while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
      ?>
      <div class="product-box">
         <img src="uploaded_img/<?= $fetch_product['image'] ?: 'default_product.png'; ?>" alt="Product Image">
         <div class="name"> <?= $fetch_product['name']; ?> </div>
         <p>Price: $<?= $fetch_product['price']; ?></p>
         <form action="" method="post">
            <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
            <button type="submit" name="add_to_wishlist" class="wishlist-btn"><i class="fa-regular fa-heart"></i> Add to Wishlist</button>
            <button type="submit" name="add_to_order" class="add-to-order-btn"><i class="fa fa-cart-plus"></i> Add to Order</button>
         </form>
      </div>
      <?php } ?>
   </div>
   <?php } ?>
</section>

<footer>
   &copy; 2025 Shop | All Rights Reserved
</footer>

<script>
   $(document).ready(function() {
    
      $(".wishlist-btn").click(function(e) {
         e.preventDefault();  
         
         var pid = $(this).closest('form').find('input[name="pid"]').val(); 

         $.ajax({
            url: 'shop.php',  
            method: 'POST',
            data: {
               add_to_wishlist: true,  
               pid: pid
            },
            success: function(response) {
               if (response === 'Product already exists in your wishlist') {
                  Swal.fire({
                     icon: 'warning',
                     title: 'The product is already in the options!',
                     text: 'The product is already in your favorites list.',
                     confirmButtonText: 'complete',
                     timer: 2000  
                  });
               } else {
                  Swal.fire({
                     icon: 'success',
                     title: 'The product has been added to your favorites list!',
                     text: 'You are great for choosing this product!',
                     confirmButtonText: 'Awesome!',
                     confirmButtonColor: '#007bff',
                     background: '#f4f6f9',
                     timer: 2000  
                  });
               }
            }
         });
      });

      $(".add-to-order-btn").click(function(e) {
         e.preventDefault();  
         
         var pid = $(this).closest('form').find('input[name="pid"]').val(); 

         $.ajax({
            url: 'shop.php',  
            method: 'POST',
            data: {
               add_to_order: true, 
               pid: pid
            },
            success: function(response) {
               if (response === 'Product already exists in your order') {
                  Swal.fire({
                     icon: 'warning',
                     title: 'The product is already in order!',
                     text: 'The product is already in your order.',
                     confirmButtonText: 'completed',
                     timer: 2000  
                  });
               } else {
                  Swal.fire({
                     icon: 'success',
                     title: 'The product has been added to the order!',
                     text: 'The product has been added to your order.',
                     confirmButtonText: 'Done',
                     confirmButtonColor: '#007bff',
                     background: '#f4f6f9',
                     timer: 2000 
                  });
               }
            }
         });
      });
   });
</script>


</body>
</html>
