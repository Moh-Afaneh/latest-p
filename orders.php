<?php
include 'components/connect.php';
session_start();

if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
   echo $user_id; // Just for debugging, can be removed later
} else {
   header('location:user_login.php');
   exit();
}

// Delete single order item
if (isset($_POST['delete_order_item'])) {
   $order_item_id = $_POST['order_item_id'];
   $delete_item = $conn->prepare("DELETE FROM order_item WHERE id = ?");
   $delete_item->execute([$order_item_id]);
   header('Location: orders.php');
   exit();
}

// Delete all items in the user's orders
if (isset($_POST['delete_all_items'])) {
   $delete_all_items = $conn->prepare("DELETE oi FROM order_item oi 
                                       JOIN `order` o ON oi.order_id = o.id 
                                       WHERE o.user_id = ?");
   $delete_all_items->execute([$user_id]);
   header('Location: orders.php');
   exit();
}

// Update item quantity
if (isset($_POST['update_quantity'])) {
   $order_item_id = $_POST['order_item_id'];
   $new_quantity = $_POST['quantity'];
   $update_quantity = $conn->prepare("UPDATE order_item SET quantity = ? WHERE id = ?");
   $update_quantity->execute([$new_quantity, $order_item_id]);
   header('Location: orders.php');
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Your Orders</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   
   <style>
      .orders-container {
         max-width: 1200px;
         margin: 20px auto;
         padding: 20px;
         background: #f9f9f9;
         border-radius: 10px;
         box-shadow: 0 0 10px rgba(0,0,0,0.1);
      }
      .order-items-table {
         width: 100%;
         border-collapse: collapse;
         margin-top: 10px;
      }
      .order-items-table th, .order-items-table td {
         border: 1px solid #ddd;
         padding: 10px;
         text-align: center;
         font-size: 16px;
      }
      .order-items-table th {
         background-color: #f4f4f4;
      }
      .empty {
         text-align: center;
         font-size: 20px;
         color: #666;
      }
      .delete-btn {
         background-color: red;
         color: white;
         border: none;
         padding: 8px 12px;
         cursor: pointer;
         border-radius: 5px;
         transition: all 0.3s ease;
      }
      .delete-btn:hover {
         background-color: #e60000;
         transform: scale(1.05);
      }
      .delete-all-btn {
         background: linear-gradient(135deg, #e74c3c, #c0392b);
         color: white;
         border: none;
         padding: 12px 25px;
         cursor: pointer;
         border-radius: 30px;
         margin-bottom: 20px;
         float: right;
         font-size: 16px;
         transition: background 0.3s ease, transform 0.3s ease;
      }
      .delete-all-btn:hover {
         background: linear-gradient(135deg, #c0392b, #e74c3c);
         transform: scale(1.05);
      }
      .update-btn {
         background-color: green;
         color: white;
         border: none;
         padding: 8px 12px;
         cursor: pointer;
         border-radius: 5px;
         transition: background 0.3s ease, transform 0.3s ease;
      }
      .update-btn:hover {
         background-color: darkgreen;
         transform: scale(1.05);
      }
      .order-items-table input[type="number"] {
         width: 60px;
         padding: 5px;
         text-align: center;
      }
      .grand-total-row td {
         font-size: 18px;
         font-weight: bold;
         background-color: #3498db;
         color: white;
         text-align: center;
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="orders">
   <h3 class="heading">Your Orders</h3>
   <div class="orders-container">
      <form id="deleteAllForm" action="" method="POST">
         <button type="submit" name="delete_all_items" class="delete-all-btn">Delete All Items</button>
      </form>

      <?php
         // Fetch orders for the logged-in user
         $select_orders = $conn->prepare("SELECT * FROM `order` WHERE user_id = ? AND status = 'pending'");
         $select_orders->execute([$user_id]);

         if ($select_orders->rowCount() > 0) {
            echo '<table class="order-items-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Product</th>';
            echo '<th>Price</th>';
            echo '<th>Quantity</th>';
            echo '<th>Total Price</th>';
            echo '<th>Status</th>';
            echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $grand_total_price = 0;

            // Loop through the orders
            while ($order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
               $order_id = $order['id'];

               // Fetch items for each order
               $select_items = $conn->prepare("SELECT oi.*, p.name, p.price 
                                               FROM order_item oi 
                                               JOIN product p ON oi.product_id = p.id 
                                               WHERE oi.order_id = ?");
               $select_items->execute([$order_id]);

               if ($select_items->rowCount() > 0) {
                  // Loop through the items
                  while ($item = $select_items->fetch(PDO::FETCH_ASSOC)) {
                     $item_total_price = $item['quantity'] * $item['price'];
                     $grand_total_price += $item_total_price;
                     
                     echo '<tr>';
                     echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                     echo '<td>$' . htmlspecialchars($item['price']) . '</td>';
                     echo '<td>
                              <form action="" method="post">
                                 <input type="number" name="quantity" value="' . $item['quantity'] . '" min="1" required>
                                 <input type="hidden" name="order_item_id" value="' . $item['id'] . '">
                                 <button type="submit" name="update_quantity" class="update-btn">Update</button>
                              </form>
                           </td>';
                     echo '<td>$' . number_format($item_total_price, 2) . '</td>';
                     echo '<td>' . ($order['status'] == 'pending' ? 'Pending' : 'Completed') . '</td>';
                     echo '<td>
                              <form action="" method="post">
                                 <input type="hidden" name="order_item_id" value="' . $item['id'] . '">
                                 <button type="submit" name="delete_order_item" class="delete-btn">Delete</button>
                              </form>
                           </td>';
                     echo '</tr>';
                  }
               }
            }
            echo '<tr class="grand-total-row">';
            echo '<td colspan="5" style="text-align:right;">Grand Total</td>';
            echo '<td>$' . number_format($grand_total_price, 2) . '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
         } else {
            echo '<p class="empty">No orders found.</p>';
         }
      ?>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
   $(document).ready(function(){
      $('.delete-all-btn').on('click', function(e){
         e.preventDefault(); 
         Swal.fire({
            title: 'Are you sure?',
            text: "This action will delete all your pending orders!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete all!'
         }).then((result) => {
            if (result.isConfirmed) {
               $('#deleteAllForm').submit();
            }
         });
      });
   });
</script>

<script src="js/script.js"></script>

</body>
</html>
