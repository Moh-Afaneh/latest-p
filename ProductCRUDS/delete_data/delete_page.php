<?php
// echo "delete page";
include('../db_connection/conn.php');
if(isset($_GET['id'])){
   $id= $_GET['id'];
    try{
        $query="DELETE FROM `product` WHERE `id`=:product_id";
        $statment=$conn->prepare($query);
        $statment->bindParam(':product_id',$id);
        $statment->execute();
        header('location:../index.php?message=delete sucesssfuly');



    }catch(PDOEception $err){
        echo $err->getMessage();
    }
}
?>