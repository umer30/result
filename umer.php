<?php

print_r(PDO::getAvailableDrivers());


$server = "162.120.188.204,1433"; // Your public IP and port
$database = "MTB_SchoolSystem1";
$username = "webuser";
$password = "]kxB8.M_#V8n"; // Replace with your actual password

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to local SQL Server!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
