<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['stock_qty']);

    if (empty($product_id)) {
        echo "Product ID is missing.";
        exit;
    }

    // Update details in tblproducts
    $stmt_prod = $con->prepare("UPDATE tblproducts SET Quantity = Quantity + ? WHERE ID = ?");
    $stmt_prod->bind_param("ii", $quantity, $product_id);
    
    if ($stmt_prod->execute()) {
        $stmt_prod->close();
        echo 'yes';
    } else {
        echo "Error updating product quantity: " . $stmt_prod->error;
        $stmt_prod->close();
    }
    $con->close();
}
?>