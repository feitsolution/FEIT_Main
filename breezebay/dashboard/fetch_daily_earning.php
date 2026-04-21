<?php
$today = date('Y-m-d');
$daily_query = mysqli_query($con, "SELECT 
    COUNT(*) as order_count, 
    SUM(TotalAmount) as total, 
    SUM(ServiceCharge) as sc_total,
    SUM(CASE WHEN OrderType LIKE '%Dine%' THEN 1 ELSE 0 END) as dine_in_count,
    SUM(CASE WHEN OrderType LIKE '%Take%' THEN 1 ELSE 0 END) as take_away_count
    FROM tblorder WHERE DATE(OrderDate) = '$today' AND Status = 'Paid'");
$daily_row = mysqli_fetch_assoc($daily_query);
$daily_income = $daily_row['total'] ? $daily_row['total'] : 0;
$daily_sc = $daily_row['sc_total'] ? $daily_row['sc_total'] : 0;
$daily_orders = $daily_row['order_count'];
$daily_dine_in = $daily_row['dine_in_count'] ? $daily_row['dine_in_count'] : 0;
$daily_take_away = $daily_row['take_away_count'] ? $daily_row['take_away_count'] : 0;

// Fetch Daily Reservations
$reservations_query = mysqli_query($con, "SELECT COUNT(ID) as total FROM tblreservation WHERE ReservationDate = '$today' AND Status = 'Confirmed'");
$reservations_row = mysqli_fetch_assoc($reservations_query);
$daily_reservations = $reservations_row['total'] ? $reservations_row['total'] : 0;

// Fetch Table Status Counts
$tables_query = mysqli_query($con, "SELECT SUM(CASE WHEN Status = '0' OR Status = 'Available' THEN 1 ELSE 0 END) as available, SUM(CASE WHEN Status = '2' OR Status = 'Seated' THEN 1 ELSE 0 END) as seated FROM tbltables");
$tables_row = mysqli_fetch_assoc($tables_query);
$available_tables = $tables_row['available'] ? $tables_row['available'] : 0;
$seated_tables = $tables_row['seated'] ? $tables_row['seated'] : 0;
?>