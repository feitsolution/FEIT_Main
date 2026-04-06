<?php
/**
 * Test Meta WhatsApp API Connection
 * This script does the exact same thing as the curl command provided by Meta.
 */

// 1. Fill in your details (I copied your access token from the webhook)
$access_token = "EAAgeT2q3kHMBROcZC3GGsAfSCLBlFzZA2hlwp6ilJxWqQSf8IJzH8Jxvx9QnVJjCHoqGEocRTnZCaMDUXNlmU6GG4wnl0IKfMuZA5c4uGSbeukiareZAo5MdzWqkqQbwImzhSeR9HgCtB18tMD4mWgZC8vNeLltUgTtEgTH50R969wK3F93fTZC96oaoWDalG9k0zRscXYJNfyMJCwpIVUl4xpmtZC0NQtoECRRYXa7Lw2oLofziImZBxIHUghF6zpBefwsi7RysMJvKYDzftKt7KcZByF";
$phone_number_id = "974652939074897";

// 2. PUT THE PHONE NUMBER YOU WANT TO SEND THE TEST MESSAGE TO HERE (must include country code, NO plus sign)
// E.g., for Sri Lanka: 94771234567
$to_number = "94778363592"; 

echo "<h3>Sending test message to $to_number...</h3>";

$url = "https://graph.facebook.com/v22.0/$phone_number_id/messages";

$data = [
    "messaging_product" => "whatsapp",
    "to" => $to_number,
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