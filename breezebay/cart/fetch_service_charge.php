<?php
// Fetch Service Charge
$sc_query = mysqli_query($con, "SELECT service_charge FROM tblbranding LIMIT 1");
$sc_row = mysqli_fetch_assoc($sc_query);
$service_charge_percentage = isset($sc_row['service_charge']) ? floatval($sc_row['service_charge']) : 0;