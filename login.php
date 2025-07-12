<?php
session_start();

// Prevent output before headers
ob_start();

header('Content-Type: application/json');

// Include connection file
require_once 'conn.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $student_id = isset($input['student_id']) ? trim($input['student_id']) : '';

    // Validate CSRF token (implement a proper CSRF check in production)
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf_token)) {
        $response['message'] = 'Invalid request';
        echo json_encode($response);
        exit;
    }

    if (empty($student_id)) {
        $response['message'] = 'Please enter your Student ID';
    } else {
        // Query to fetch student data
        $sql = "SELECT * FROM tbl0_02StudentInfo WHERE StudentID = ?";
        $params = array($student_id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $response['message'] = 'Database error';
            error_log('SQL Error: ' . print_r(sqlsrv_errors(), true));
        } else {
            $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($student) {
                $_SESSION['student_id'] = $student_id;
                $response['success'] = true;
            } else {
                $response['message'] = 'Invalid Student ID. Please try again.';
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
ob_end_flush();
?>