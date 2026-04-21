<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_countable = $_POST['product_countable']; // 'Countable' or 'Uncountable'
    $product_unit = null;

    if (empty($id) || empty($product_name) || empty($product_price)) {
        echo "Product name and price are required.";
        exit;
    }

    if ($product_countable == 'Countable') {
        if (isset($_POST['product_unit']) && !empty($_POST['product_unit'])) {
            $product_unit = $_POST['product_unit'];
        } else {
            echo "Product unit is required for countable products.";
            exit;
        }
    }

    // Check for duplicate product name (excluding the current product)
    $check_stmt = $con->prepare("SELECT ID FROM tblproducts WHERE ProductName = ? AND ID != ?");
    $check_stmt->bind_param("si", $product_name, $id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "A product with this name already exists.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("UPDATE tblproducts SET ProductName=?, Price=?, Type=?, Unit=? WHERE ID=?");
    $stmt->bind_param("sdssi", $product_name, $product_price, $product_countable, $product_unit, $id);

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>