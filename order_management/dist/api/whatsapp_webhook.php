<?php
/**
 * WhatsApp Cloud API Test Script (Fixed)
 */

// 🔐 1. PUT YOUR NEW ACCESS TOKEN HERE (IMPORTANT: regenerate it!)
$access_token = "EAAgeT2q3kHMBRIDLevqZBDofxpMfOqM3J9KEHzlAzXxdR85Vr8SPAhY6LosZCvvUu2DArbttk8iUxqauenTflWOPCMOrM4i2CRdU2To1J6ITTP4ZCNbCIpxpZBsevZBG1Id6SFt3HyeEbsZBQB6SokecAeZAmOluATHcgoySHS2Bsds943SG0nmS64K3oPnvx1pDETGaSUezmuRjNShdAr2aJgNvv3kB88OkoGX8WRdUhhMLUOOu6mjAyCWttJJZB0AZBafDZBvGceKNDJwmdGK7ul8UIF6QZDZ";

// 📱 2. Your Phone Number ID from Meta
$phone_number_id = "974652939074897";

// 📞 3. Receiver number (with country code, NO + sign)
$to_number = "94778363592";

// 🌐 4. API URL
$url = "https://graph.facebook.com/v22.0/$phone_number_id/messages";

// 📦 5. Request payload
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

// 🔄 Convert to JSON
$json_data = json_encode($data);

// 🧪 Debug output
echo "<h3>Sending test message to: $to_number</h3>";
echo "<pre>JSON Payload:\n$json_data</pre>";

// 🚀 6. Initialize cURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// ✅ Important headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json",
    "Content-Length: " . strlen($json_data)
]);

// ❌ DO NOT disable SSL unless absolutely needed
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// ▶️ Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// ❗ Error handling
if (curl_errno($ch)) {
    echo "<p style='color:red;'>cURL Error: " . curl_error($ch) . "</p>";
}

curl_close($ch);

// 📊 Show response
echo "<h4>Status Code: $http_code</h4>";
echo "<pre>";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "</pre>";

// ✅ Success / Fail message
if ($http_code == 200) {
    echo "<p style='color:green; font-weight:bold;'>✅ Message sent successfully!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Failed. Check error above.</p>";
}
?>