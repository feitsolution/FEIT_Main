<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parent_category_name = trim($_POST['parent_category_name']);

    if (empty($parent_category_name)) {
        echo "Parent Category Name is required.";
        exit;
    }

    // Check for duplicate
    $check_stmt = $con->prepare("SELECT ID FROM tblparentcategory WHERE ParentCategoryName = ?");
    $check_stmt->bind_param("s", $parent_category_name);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo "Parent Category already exists.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Insert
    $stmt = $con->prepare("INSERT INTO tblparentcategory (ParentCategoryName) VALUES (?)");
    $stmt->bind_param("s", $parent_category_name);

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Error: ' . $stmt->error;
    }
    
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>