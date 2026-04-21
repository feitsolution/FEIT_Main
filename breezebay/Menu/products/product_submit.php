<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parent_cat_id = $_POST['parent_category'];
    $sub_cat_id = $_POST['sub_category'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_countable = $_POST['product_countable']; // This will be 'Countable' or 'Uncountable'
    $product_unit = null;

    // Basic validation
    if (empty($parent_cat_id) || empty($sub_cat_id) || empty($product_name) || empty($product_price)) {
        echo "All fields including categories are required.";
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


    // Check for duplicate product name to avoid confusion
    $check_stmt = $con->prepare("SELECT ID FROM tblproducts WHERE ProductName = ?");
    $check_stmt->bind_param("s", $product_name);
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
    $stmt = $con->prepare("INSERT INTO tblproducts (ParentCategoryID, SubCategoryID, ProductName, Price, Type, Unit) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdss", $parent_cat_id, $sub_cat_id, $product_name, $product_price, $product_countable, $product_unit);

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Something went wrong. Please try again. Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>