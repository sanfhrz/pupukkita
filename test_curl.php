<?php
// Test CURL extension
if (function_exists('curl_version')) {
    echo "CURL tersedia<br>";
    $version = curl_version();
    echo "CURL Version: " . $version['version'] . "<br>";
    echo "SSL Version: " . $version['ssl_version'] . "<br>";
} else {
    echo "CURL TIDAK tersedia - Aktifkan di php.ini";
}

// Test API Key
define('RAJAONGKIR_API_KEY', 'MDim9mft0c5b8f45bd3c789aPEnF1izV');
echo "<br>API Key: " . RAJAONGKIR_API_KEY;

// Test direct API call
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.rajaongkir.com/starter/province",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "key: " . RAJAONGKIR_API_KEY
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "<br><br>HTTP Code: " . $httpCode;
echo "<br>Error: " . ($err ?: 'Tidak ada error');
echo "<br>Response: <pre>" . $response . "</pre>";
?>