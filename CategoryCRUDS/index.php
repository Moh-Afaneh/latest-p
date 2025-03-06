<?php
include('./assets/header.php');
include('./db_connection/conn.php');
if(isset($_GET['message'])){
  echo "<div style='background-color:green'> {$_GET['message']}</div>";
}
?>

<main>
    


    <h1>category Information</h1>
    <!-- FIX: Updated data attributes for Bootstrap 5 -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
        Add category
    </button>

    <div class="container mt-5">
        <table class="table table-striped table-hover table-bordered ">
            <thead>
                <tr>
                    <!-- <th scope="col">id</th> -->
                    <th scope="col">id</th>
                    <th scope="col">name</th>
                    <th scope="col">image</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
              <?php
                $query= "SELECT * FROM `category`";
                $categories=$conn->query($query);
                // print_r ($users);
                foreach($categories as $category){
                  echo "<tr>
                   <td>{$category['id']}</td>
                  <td>{$category['name']}</td>
                  <td>
                    <div class='text-center'>
                        <img src='" . (!empty($category['image']) ? 'images/' . $category['image'] : 'images/default.png') . "' width='100' height='60'>
                    </div>
                  </td>
                  <td class='text-center'>
                  <a href='./update_data/update_page.php?id={$category['id']}' class='btn btn-success'>Edit</a>
                  <a href='./delete_data/delete_page.php?id={$category['id']}' class='btn btn-danger'>Delete</a>
                  </td>

                  </tr>";
                }
              ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">New category</h5>
                    <!-- FIX: Bootstrap 5 requires 'btn-close' instead of 'close' -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <form action="insert_data/insert_new.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="user-name" class="col-form-label"> Name:</label>
                            <input type="text" class="form-control" id="category_name" name="category_name">
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="category_image">
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <input type="submit" class="btn btn-success" name="add_category" value="Add">
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>
</main>


<?php
include('./assets/footer.php');

?>