<?php
/**
 * Test Meta WhatsApp API Connection
 * This script does the exact same thing as the curl command provided by Meta.
 */

// 1. Fill in your details (I copied your access token from the webhook)
$access_token = "EAAgeT2q3kHMBRIDLevqZBDofxpMfOqM3J9KEHzlAzXxdR85Vr8SPAhY6LosZCvvUu2DArbttk8iUxqauenTflWOPCMOrM4i2CRdU2To1J6ITTP4ZCNbCIpxpZBsevZBG1Id6SFt3HyeEbsZBQB6SokecAeZAmOluATHcgoySHS2Bsds943SG0nmS64K3oPnvx1pDETGaSUezmuRjNShdAr2aJgNvv3kB88OkoGX8WRdUhhMLUOOu6mjAyCWttJJZB0AZBafDZBvGceKNDJwmdGK7ul8UIF6QZDZ";
$phone_number_id = "974652939074897";

$url = "https://graph.facebook.com/v22.0/$phone_number_id/messages";

$data = [
    "messaging_product" => "whatsapp",
    "type" => "template",
    "template" => [
        "name" => "hello_world",
        "language" => [
            "code" => "en_US"
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // useful for local XAMPP testing

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(curl_errno($ch)){
    echo '<p style="color:red;">cURL Error: ' . curl_error($ch) . '</p>';
}

curl_close($ch);

echo "<h4>Status Code: $http_code</h4>";
echo "<pre>Response: \n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "</pre>";

if ($http_code == 200) {
    echo "<p style='color:green; font-weight:bold;'>Success! The hello_world message should have arrived on your phone.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>Failed! Read the response above to see what Meta complained about.</p>";
}
?>