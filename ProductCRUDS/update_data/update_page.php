<?php
include('../db_connection/conn.php');
include('../assets/header.php');
// read from student table based on id
// echo "update page";
if(isset($_GET['id'])){
    $id= $_GET['id'];
    // echo $id;
    $query="SELECT * FROM `product` WHERE `id`=:id";
    $statment=$conn->prepare($query);
        $statment->bindParam(':id',$id);
        $statment->execute();
        $product_inf=$statment->fetch(PDO::FETCH_ASSOC);
        // print_r($category_inf);
}
    else{
        echo "id not set";
    }

?>
<!-- update on student table based on id -->
<?php
if(isset($_POST['update_product'])){
    // echo "update data";
    $id = $_GET['id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_description = $_POST['product_description'];
    $product_category = $_POST['product_category'];
    $product_imgUrl = $_POST['imgUrl'];

    // Prepare the update query
    $query = "UPDATE `product` SET `name`=:product_name,`price`=:product_price,`description`=:product_description,`category_id`=:product_category, `image`=:product_imgUrl WHERE `id`=:product_id";

    $statment = $conn->prepare($query);
    $statment->bindParam(':product_id', $id); // Bind the category_id here
    $statment->bindParam(':product_name', $product_name);
    $statment->bindParam(':product_price', $product_price);
    $statment->bindParam(':product_description', $product_description);
    $statment->bindParam(':product_category', $product_category);
    $statment->bindParam(':product_imgUrl', $product_imgUrl);

    // Execute the statement
    $statment->execute();
    header('location:../index.php?message=update successful');
}
?>


<form action="" method="post">
    <!-- <input type="hidden" name="category_id" value="<?= $product_inf['id']?>"> -->
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Name:</label>
        <input type="text" class="form-control" id="product_name" name="product_name" value="<?= $product_inf['name']?>">
    </div>
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Price:</label>
        <input type="text" class="form-control" id="product_price" name="product_price" value="<?= $product_inf['price']?>">
    </div>
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Description:</label>
        <input type="text" class="form-control" id="product_description" name="product_description" value="<?= $product_inf['description']?>">
    </div>
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Category:</label>
        <input type="text" class="form-control" id="product_category" name="product_category" value="<?= $product_inf['category_id']?>">
    </div>
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Image:</label>
        <input type="text" class="form-control" id="imgUrl" name="imgUrl" value="<?= $product_inf['image']?>">
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-success" name="update_product" value="Add">
    </div>
</form>

<?php
include('../assets/footer.php');
?>