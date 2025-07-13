
<?php
// Database configuration - using your exact local SQL Server parameters
$serverName = "DESKTOP-7DQE9A2\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "MTB_SchoolSystem1",
    "Uid" => "",
    "PWD" => "",
    "TrustServerCertificate" => true,
    "Encrypt" => false,
    "ConnectionPooling" => false,
    "MultipleActiveResultSets" => false,
    "LoginTimeout" => 30,
    "ConnectRetryCount" => 3,
    "ConnectRetryInterval" => 10
);

// Attempt connection with proper error handling
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check connection and handle errors securely
if ($conn === false) {
    // Log the actual error for debugging (never expose to users)
    error_log("Database connection failed: " . print_r(sqlsrv_errors(), true));
    
    // Show generic error to users
    die("Database connection failed. Please contact administrator.");
}

// Set connection options for security
sqlsrv_configure("WarningsReturnAsErrors", 0);
?>
