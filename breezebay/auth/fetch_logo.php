<?php
// Fetch Logo from tblbranding
$branding_query = mysqli_query($con, "SELECT logo FROM tblbranding LIMIT 1");
$branding_row = mysqli_fetch_assoc($branding_query);
$login_bg = ($branding_row && !empty($branding_row['logo'])) ? '../branding/uploads/' . $branding_row['logo'] : '';
?>