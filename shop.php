<?php
// تضمين الاتصال بقاعدة البيانات
include 'components/connect.php';
// بدء الجلسة
session_start();

// التحقق من وجود المستخدم في الجلسة
if(isset($_SESSION['user_id'])){
   // إذا كان المستخدم موجودًا، قم بتخزين معرفه في المتغير
   $user_id = $_SESSION['user_id'];
} else {
   // إذا لم يكن هناك مستخدم في الجلسة، قم بتوجيهه إلى صفحة تسجيل الدخول
   header('location:user_login.php');
   exit();
}




// إضافة عنصر إلى قائمة الرغبات
if(isset($_POST['add_to_wishlist'])){
   $pid = $_POST['pid']; // الحصول على معرف المنتج المرسل عبر النموذج

   // التحقق إذا كان لدى المستخدم قائمة رغبات بالفعل
   $select_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ?");
   $select_wishlist->execute([$user_id]);
   if($select_wishlist->rowCount() > 0){
      // إذا كان هناك قائمة رغبات، الحصول على معرفها
      $wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC);
      $wishlist_id = $wishlist['id'];
   } else {
      // إذا لم يكن هناك قائمة رغبات، إنشاء واحدة جديدة
      $insert_wishlist = $conn->prepare("INSERT INTO wishlist (user_id) VALUES (?)");
      $insert_wishlist->execute([$user_id]);
      // الحصول على معرف القائمة التي تم إنشاؤها
      $wishlist_id = $conn->lastInsertId();
   }

   // التحقق إذا كان المنتج موجودًا في قائمة الرغبات بالفعل
   $check_wishlist_item = $conn->prepare("SELECT * FROM wishlist_item WHERE wishlist_id = ? AND product_id = ?");
   $check_wishlist_item->execute([$wishlist_id, $pid]);
   if($check_wishlist_item->rowCount() > 0){
      // إذا كان المنتج موجودًا بالفعل في قائمة الرغبات
      echo 'Product already exists in your wishlist';
      exit();
   } else {
      // إذا لم يكن موجودًا، إضافته إلى قائمة الرغبات
      $insert_item = $conn->prepare("INSERT INTO wishlist_item (wishlist_id, product_id) VALUES (?, ?)");
      $insert_item->execute([$wishlist_id, $pid]);
      echo 'success'; // إرجاع رسالة نجاح
      exit();
   }
}

// إضافة عنصر إلى الطلب
if(isset($_POST['add_to_order'])){
    $pid = $_POST['pid']; // الحصول على معرف المنتج المرسل عبر النموذج
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        die("User ID is not set");
    }

    // التحقق إذا كان لدى المستخدم طلب قيد التنفيذ (حالة "معلق")
    $select_order = $conn->prepare("SELECT * FROM `order` WHERE user_id = ? AND status = 'pending'");
    $select_order->execute([$user_id]);

    if($select_order->rowCount() > 0){
        $order = $select_order->fetch(PDO::FETCH_ASSOC);
        $order_id = $order['id'];
    } else {
        // إنشاء طلب جديد
        $insert_order = $conn->prepare("INSERT INTO `order` (user_id, status) VALUES (?, 'pending')");
        $insert_order->execute([$user_id]);
        $order_id = $conn->lastInsertId();
    }

    // التحقق إذا كان المنتج موجودًا في الطلب بالفعل
    $check_order_item = $conn->prepare("SELECT * FROM order_item WHERE order_id = ? AND product_id = ?");
    $check_order_item->execute([$order_id, $pid]);

    if($check_order_item->rowCount() > 0){
        // تحديث الكمية بدلاً من التوقف
        $update_quantity = $conn->prepare("UPDATE order_item SET quantity = quantity + 1 WHERE order_id = ? AND product_id = ?");
        $update_quantity->execute([$order_id, $pid]);
        echo 'Quantity updated';
        exit();
    } else {
        // إضافة المنتج إلى الطلب
        $insert_order_item = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity) VALUES (?, ?, 1)");
        $insert_order_item->execute([$order_id, $pid]);
        echo 'success'; // إرجاع رسالة نجاح
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
   <!-- تضمين مكتبة أيقونات FontAwesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- تضمين ملفات التنسيق الخاصة بالمتجر -->
   <link rel="stylesheet" href="css/style.css">
   <!-- تضمين مكتبة JQuery -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <!-- تضمين مكتبة SweetAlert لإظهار رسائل تنبيهية -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <style>
      /* تخصيص التنسيق الخاص بالصفحة */
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

      /* تخصيص قسم الفئات */
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

      /* تخصيص الأزرار الخاصة بالفئات */
      .category-buttons {
         display: flex;
         justify-content: center;
         gap: 20px;
         flex-wrap: wrap;
         margin-top: 20px;
      }

      .category-btn {
         padding: 10px 20px;
         font-size: 1.2em;
         background-color: #007bff;
         color: white;
         border-radius: 5px;
         text-decoration: none;
         text-transform: uppercase;
         font-weight: bold;
         transition: background-color 0.3s, transform 0.3s;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

      .category-btn:hover, .category-btn.active {
         background-color: #0056b3;
         transform: scale(1.05);
      }

      .category-btn.active {
         background-color: #0056b3;
         box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
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

      /* تخصيص أزرار إضافة المنتج إلى قائمة الرغبات والطلب */
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

      /* تخصيص قسم التذييل */
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




<?php include 'components/header_section.php';?>


<!-- قسم تصنيف المنتجات حسب الفئة -->
<section class="categories container">
   <h1 class="heading">Filter Products by Category</h1>
   <div class="category-buttons">
      <!-- زر عرض جميع المنتجات -->
      <a href="shop.php" class="category-btn <?= (!isset($_GET['category_id'])) ? 'active' : ''; ?>">All Items</a>
      <?php
         // استعلام للحصول على جميع الفئات
         $select_categories = $conn->prepare("SELECT * FROM category");
         $select_categories->execute();
         while ($fetch_category = $select_categories->fetch(PDO::FETCH_ASSOC)) {
      ?>
      <!-- عرض زر لكل فئة من الفئات -->
      <a href="shop.php?category_id=<?= $fetch_category['id']; ?>" class="category-btn <?= (isset($_GET['category_id']) && $_GET['category_id'] == $fetch_category['id']) ? 'active' : ''; ?>">
         <?= $fetch_category['name']; ?>
      </a>
      <?php } ?>
   </div>
</section>

<!-- قسم عرض المنتجات -->
<section class="products container">
   <h2 class="heading">Products</h2>
   <div class="box-container">
      <?php
         // التحقق من فئة المنتجات المختارة
         $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

         // إذا كانت هناك فئة مختارة، عرض المنتجات ضمن هذه الفئة فقط
         if ($category_id) {
            $select_products = $conn->prepare("SELECT * FROM product WHERE category_id = ?");
            $select_products->execute([$category_id]);
         } else {
            // عرض جميع المنتجات
            $select_products = $conn->prepare("SELECT * FROM product");
            $select_products->execute();
         }

         // عرض كل منتج
         while ($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)) {
      ?>
      <div class="product-box">
         <img src="images/<?= $fetch_product['image'] ?: 'default_product.png'; ?>" alt="Product Image">
         <div class="name"> <?= $fetch_product['name']; ?> </div>
         <p>Price: $<?= $fetch_product['price']; ?></p>
         <!-- نموذج لإضافة المنتج إلى قائمة الرغبات أو الطلب -->
         <form action="" method="post">
            <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
            <button type="submit" name="add_to_wishlist" class="wishlist-btn"><i class="fa-regular fa-heart"></i> Add to Wishlist</button>
            <button type="submit" name="add_to_order" class="add-to-order-btn"><i class="fa fa-cart-plus"></i> Add to Order</button>
         </form>
      </div>
      <?php } ?>
   </div>
</section>

<!-- تذييل الصفحة -->
<footer>
   &copy; 2025 All Rights Reserved.
</footer>

<!-- سكربت التعامل مع زر إضافة المنتج إلى قائمة الرغبات والطلب -->
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
               // إذا تم إضافة المنتج بنجاح إلى قائمة الرغبات
               if (response === 'Product already exists in your wishlist') {
                  Swal.fire({
                     icon: 'warning',
                     title: 'The product is already in your wishlist!',
                     text: 'This product is already in your wishlist.',
                     confirmButtonText: 'Complete',
                     timer: 2000  
                  });
               } else {
                  Swal.fire({
                     icon: 'success',
                     title: 'The product has been added to your wishlist!',
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
               // إذا تم إضافة المنتج بنجاح إلى الطلب
               if (response === 'Product already exists in your order') {
                  Swal.fire({
                     icon: 'warning',
                     title: 'The product is already in your order!',
                     text: 'This product is already in your order.',
                     confirmButtonText: 'Complete',
                     timer: 2000  
                  });
               } else {
                  Swal.fire({
                     icon: 'success',
                     title: 'The product has been added to your order!',
                     text: 'This is one step closer to getting it!',
                     confirmButtonText: 'Awesome!',
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
<?php
include'./components/footer.php';
?>