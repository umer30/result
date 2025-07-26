<?php
session_start();

// Security headers
header('Content-Type: application/json');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Prevent output before headers
ob_start();

// Include database and security classes
require_once 'config/database.php';
require_once 'classes/Security.php';

// Set security headers
Security::setSecurityHeaders();

// Rate limiting check using Security class
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = "rate_limit_" . $ip;
$current_time = time();

if (!Security::rateLimitCheck($ip, 5, 900)) {
    $response['message'] = 'Too many login attempts. Please try again later.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION[$rate_limit_key]['count']++;
    $_SESSION[$rate_limit_key]['last_attempt'] = $current_time;

    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate JSON input
    if (!$input || !is_array($input)) {
        $response['message'] = 'Invalid request format';
        echo json_encode($response);
        exit;
    }

    $student_id = isset($input['student_id']) ? trim($input['student_id']) : '';

    // Enhanced CSRF validation
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf_token) || strlen($csrf_token) < 10) {
        $response['message'] = 'Invalid security token';
        echo json_encode($response);
        exit;
    }

    // Input validation
    if (empty($student_id)) {
        $response['message'] = 'Please enter your Student ID';
    } elseif (!is_numeric($student_id) || strlen($student_id) > 20) {
        $response['message'] = 'Invalid Student ID format';
    } else {
        // Sanitize input
        try {
            $student_id = Security::validateStudentId($student_id);
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            echo json_encode($response);
            exit;
        }

        // Get database connection
        $db = DatabaseConfig::getInstance();
        $conn = $db->getConnection();

        // Query to fetch student data with proper error handling
        $sql = "SELECT StudentID, Name, IsActive FROM tbl0_02StudentInfo WHERE StudentID = ? AND IsActive = 1";
        $params = array((int)$student_id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = "Something went wrong. Please try again or contact admin.";
            if (is_array($errors) && !empty($errors)) {
                error_log("Login query error: " . print_r($errors, true));
                if (strpos($errors[0]['message'], 'Invalid object name') !== false) {
                    $error_msg = "Database table not found. Please contact administrator.";
                } elseif (strpos($errors[0]['message'], 'connection') !== false) {
                    $error_msg = "Database connection lost. Please try again.";
                }
            }
            $response['message'] = $error_msg;
        } else {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row) {
                // Check if account is active
                if (!$row['IsActive']) {
                    $response['message'] = 'Your account is currently inactive. Please contact administrator.';
                } else {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['student_id'] = $row['StudentID'];
                    $_SESSION['student_name'] = $row['Name'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];

                    // Clear rate limiting for successful login
                    unset($_SESSION[$rate_limit_key]);

                    $response['success'] = true;
                    $response['message'] = 'Login successful';
                    $response['redirect'] = 'dashboard.php';
                }
            } else {
                $response['message'] = 'Invalid Student ID. Please check your ID and try again.';
            }

            sqlsrv_free_stmt($stmt);
        }
        // No need to close connection explicitly, as DatabaseConfig handles it
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
ob_end_flush();
?>