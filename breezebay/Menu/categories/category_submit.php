<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parent_id = $_POST['parent_category'];
    $category_name = $_POST['category_name'];

    // Basic validation
    if (empty($category_name) || empty($parent_id)) {
        echo "Category name and Parent Category are required.";
        exit;
    }

    // Check for duplicate category name
    $check_stmt = $con->prepare("SELECT ID FROM tblcategory WHERE CategoryName = ?");
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Category name already exists. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("INSERT INTO tblcategory (ParentCategoryID, CategoryName) VALUES (?, ?)");
    $stmt->bind_param("is", $parent_id, $category_name);

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