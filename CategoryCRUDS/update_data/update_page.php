<?php
include('../db_connection/conn.php');
include('../assets/header.php');
// read from student table based on id
// echo "update page";
if(isset($_GET['id'])){
    $id= $_GET['id'];
    // echo $id;
    $query="SELECT * FROM `category` WHERE `id`=:id";
    $statment=$conn->prepare($query);
        $statment->bindParam(':id',$id);
        $statment->execute();
        $category_inf=$statment->fetch(PDO::FETCH_ASSOC);
        // print_r($category_inf);
}
    else{
        echo "id not set";
    }

?>
<!-- update on student table based on id -->
<?php
if(isset($_POST['update_category'])){
    // echo "update data";
    $id = $_GET['id'];
    $category_name = $_POST['Category_name'];
    $category_imgUrl = $_POST['imgUrl'];

    // Prepare the update query
    $query = "UPDATE `category` SET `name`=:category_name, `image`=:category_imgUrl WHERE `id`=:category_id";

    $statment = $conn->prepare($query);
    $statment->bindParam(':category_name', $category_name);
    $statment->bindParam(':category_imgUrl', $category_imgUrl);
    $statment->bindParam(':category_id', $id); // Bind the category_id here

    // Execute the statement
    $statment->execute();
    header('location:../index.php?message=update successful');
}
?>


<form action="" method="post">
    <!-- <input type="hidden" name="category_id" value="<?= $category_inf['category_id']?>"> -->
    <div class="mb-3">
        <label for="user-name" class="col-form-label">Name:</label>
        <input type="text" class="form-control" id="Category_name" name="Category_name" value="<?= $category_inf['name']?>">
    </div>
    <div class="mb-3">
        <label for="user-name" class="col-form-label">image:</label>
        <input type="text" class="form-control" id="imgUrl" name="imgUrl" value="<?= $category_inf['image']?>">
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-success" name="update_category" value="Add">
    </div>
</form>

<?php
include('../assets/footer.php');
?>