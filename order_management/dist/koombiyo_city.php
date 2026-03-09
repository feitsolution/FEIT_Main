<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://application.koombiyodelivery.lk/api/Cities/users");
curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, "apikey='muABqMKZgkaZDAnbBWev'& district_id=1");
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
// receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch); curl_close ($ch); echo
$server_output;
?>