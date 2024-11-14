<?php

// Initialize cURL session
$curl = curl_init();

// Login request
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://1.1.1.1:20604/auth/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => '/tmp/cookieFileName',
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "userName" => "",
        "password" => ""
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);

$response = curl_exec($curl);

if (!$response) {
    die("Login request failed: " . curl_error($curl));
}

// Fetch services data
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://1.1.1.1:20604/api/v2/services.datatables?fd-only',
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "userName" => "username",
        "password" => "password"
    ]),
]);

$json = curl_exec($curl);

if (!$json) {
    die("Data fetch request failed: " . curl_error($curl));
}

curl_close($curl);

$data = json_decode($json, true);

if (!$data) {
    die("Failed to decode JSON response.");
}

// Database credentials
$servername = "";
$username = "";
$password = "";
$dbname = "";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8');

// Delete old records if more than one exists
$totalRecords = $data['recordsTotal'] ?? 0;
if ($totalRecords > 1) {
    $conn->query("DELETE FROM services");
}

// Insert new records
for ($i = 0; $i < $totalRecords; $i++) {
    $record = $data['data'][$i];
    $displayName = $conn->real_escape_string($record['service']['displayName'] ?? '');
    $startupType = $conn->real_escape_string($record['service']['startupType'] ?? '');
    $processStatus = $conn->real_escape_string($record['runtime']['processStatus'] ?? '');
    $status = $conn->real_escape_string($record['controllability']['serviceStatus']['status'] ?? '');
    $pid = $conn->real_escape_string($record['runtime']['pid'] ?? '');

    $sql = "INSERT INTO services (data_id, displayname, pid, processstatus, startuptype, status)
            VALUES ('$i', '$displayName', '$pid', '$processStatus', '$startupType', '$status')";

    if (!$conn->query($sql)) {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();

?>

