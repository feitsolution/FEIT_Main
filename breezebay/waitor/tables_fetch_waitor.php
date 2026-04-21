<?php
// Fetch tables for the SweetAlert
$table_buttons = '';
$today = date('Y-m-d');
$sql = "SELECT t.ID, t.TableName, t.Status 
        FROM tbltables t 
        WHERE (t.Status = '0' OR t.Status = 'Available') 
        AND NOT EXISTS (
            SELECT 1 FROM tblreservation r 
            WHERE r.TableID = t.ID 
            AND r.ReservationDate = '$today' 
            AND r.Status = 'Confirmed'
        )";
$result = mysqli_query($con, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $statusColor = 'btn-success';
        $table_buttons .= '<button class="btn ' . $statusColor . ' m-2 table-btn" data-id="' . $row['ID'] . '" data-name="' . htmlspecialchars($row['TableName'], ENT_QUOTES) . '">' . htmlspecialchars($row['TableName']) . '</button>';
    }
}
?>