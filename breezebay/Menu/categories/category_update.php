<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST['category_id'];
    $category_name = $_POST['category_name'];
    $parent_category_id = $_POST['parent_category'];

    // Basic validation
    if (empty($category_id) || empty($category_name) || empty($parent_category_id)) {
        echo "Parent Category and Sub Category Name are required.";
        exit;
    }

    // Check for duplicate category name, excluding the current category
    $check_stmt = $con->prepare("SELECT ID FROM tblcategory WHERE CategoryName = ? AND ParentCategoryID = ? AND ID != ?");
    $check_stmt->bind_param("sii", $category_name, $parent_category_id, $category_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Sub Category name already exists under this Parent Category. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Prepare the update query
    $stmt = $con->prepare("UPDATE tblcategory SET CategoryName=?, ParentCategoryID=? WHERE ID=?");
    $stmt->bind_param("sii", $category_name, $parent_category_id, $category_id);

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