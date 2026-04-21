<?php
include('../../include/dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id']) && isset($_POST['status'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];

        // Validate status
        if ($status != 0 && $status != 1) {
            echo 'Invalid status value.';
            exit;
        }

        // Using prepared statements to prevent SQL injection
        $stmt = $con->prepare("UPDATE tblcategory SET Status = ? WHERE ID = ?");
        $stmt->bind_param("ii", $status, $id);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Error: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
        $con->close();
    } else {
        echo 'Invalid parameters provided.';
    }
} else {
    echo 'Invalid request method.';
}
?>