<?php
// Fetch Parent Categories
$parent_categories = mysqli_query($con, "SELECT * FROM tblparentcategory");

// Fetch Categories for Tabs
$categories = mysqli_query($con, "SELECT * FROM tblcategory WHERE Status = 1");

// Fetch Products
$products_query = mysqli_query($con, "SELECT * FROM tblproducts WHERE Status = 1");
$products_data = [];
while ($row = mysqli_fetch_assoc($products_query)) {
    // Group products by SubCategoryID (which corresponds to tblcategory ID)
    $products_data[$row['SubCategoryID']][] = $row;
}
?>