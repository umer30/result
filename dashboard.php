<?php
require_once 'config/database.php';
require_once 'classes/Student.php';
require_once 'classes/Security.php';

// Set security headers
Security::setSecurityHeaders();

// Validate session and get student ID
$student_id = Security::validateSession();

// Get database connection
$db = DatabaseConfig::getInstance();
$conn = $db->getConnection();

// Create student object
$student = new Student($conn, $student_id);

// Initialize variables with empty arrays
$student_data = null;
$attendance_data = [];
$class_student_id = null;
$exam_results = [];
$subject_results = [];
$fee_results = [];
$error_message = null;

// Get student data with comprehensive error handling
try {
    // Check if database connection is available
    if (!$conn || $conn === false) {
        throw new Exception("Database connection is not available. Please check your database server.");
    }

    // Get student information
    $student_data = $student->getStudentInfo();
    if (!$student_data) {
        throw new Exception("Student information not found. Please check your Student ID.");
    }

    // Get attendance data
    try {
        $attendance_data = $student->getAttendanceData();
    } catch (Exception $e) {
        error_log("Attendance data error: " . $e->getMessage());
        $attendance_data = [];
    }

    // Get class student ID
    $class_student_id = $student->getLatestClassStudentId();
    if (!$class_student_id) {
        error_log("No enrollment found for student ID: " . $student_id);
        // Continue with basic student info, but show warning
        $error_message = "Student enrollment information is incomplete. Some features may not be available.";
    }

    // Get exam results with fallback
    if ($class_student_id) {
        try {
            $exam_results = $student->getExamResults($class_student_id);
        } catch (Exception $e) {
            error_log("Exam results error: " . $e->getMessage());
            $exam_results = [];
        }

        // Get subject results with fallback
        try {
            $subject_results = $student->getSubjectWiseResults($class_student_id);
        } catch (Exception $e) {
            error_log("Subject results error: " . $e->getMessage());
            $subject_results = [];
        }

        // Get fee details with fallback
        try {
            $fee_results = $student->getFeeDetails($class_student_id);
        } catch (Exception $e) {
            error_log("Fee details error: " . $e->getMessage());
            $fee_results = [];
        }
    }

    // Ensure variables are arrays to prevent count() errors
    $subject_results = is_array($subject_results) ? $subject_results : [];
    $exam_results = is_array($exam_results) ? $exam_results : [];
    $fee_results = is_array($fee_results) ? $fee_results : [];

} catch (Exception $e) {
    error_log("Dashboard critical error: " . $e->getMessage());

    // Set appropriate error message based on error type
    if (strpos($e->getMessage(), 'Database connection') !== false) {
        $error_message = "Database server is not responding. Please ensure your SQL Server is running and accessible.";
    } elseif (strpos($e->getMessage(), 'Student information not found') !== false) {
        $error_message = "Student record not found. Please verify your Student ID and try again.";
    } else {
        $error_message = "Unable to load dashboard data. Please check your database connection and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTB - Student Academic Dashboard</title>		
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">	
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --secondary: #10B981;
            --accent: #F59E0B;
            --dark: #1F2937;
            --light: #F3F4F6;
        }

        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .bg-secondary { background-color: var(--secondary); }
        .text-secondary { color: var(--secondary); }
        .bg-accent { background-color: var(--accent); }
        .text-accent { color: var(--accent); }
        .bg-dark { background-color: var(--dark); }
        .text-dark { color: var(--dark); }

        .attendance-chart {
            height: 300px;
        }

        .animate-gradient {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 6s ease infinite;
        }

        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .progress-ring__circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal-hidden {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
        }

        .circle-bg {
            position: relative;
            overflow: hidden;
        }

        .circle-bg::before {
            content: "";
            position: absolute;
            top: 0;
            left: 50%;
            width: 1000px;
            height: 1000px;
            background-image: repeating-radial-gradient(
                circle,
                rgba(255, 255, 255, 0.03) 1px,
                transparent 10px
            );
            transform: translateX(-50%) translateY(-40%);
            mask-image: linear-gradient(to bottom, transparent 0%, white 50%);
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, white 50%);
            pointer-events: none;
            z-index: 0;
        }

        .circle-bg > * {
            position: relative;
            z-index: 10;
        }
        @media (max-width: 1023px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .overflow-x-auto table {
                font-size: 0.875rem;
            }
            
            .overflow-x-auto th,
            .overflow-x-auto td {
                padding: 0.5rem 0.25rem;
            }
            
            .student-details {
                display: none;
            }
            
            .show-details-btn {
                display: block;
            }
        }
        
        @media (max-width: 640px) {
            main {
                padding: 1rem;
            }
            
            .overflow-x-auto table {
                font-size: 0.75rem;
            }
            
            .overflow-x-auto th,
            .overflow-x-auto td {
                padding: 0.375rem 0.125rem;
                min-width: 60px;
            }
            
            header h1 {
                font-size: 1rem;
            }
            
            header .flex {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        @media (min-width: 769px) {
            .student-details {
                display: block !important;
            }
            
            .show-details-btn {
                display: none !important;
            }
        }
        
        .modal {
            z-index: 9999 !important;
        }
        
        .modal .bg-gradient-to-br {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen">
        <?php if (isset($error_message)) { ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Error:</strong>
                </div>
                <p class="mt-2"><?php echo htmlspecialchars($error_message); ?></p>
                <div class="mt-3">
                    <button onclick="location.reload()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                        <i class="fas fa-refresh mr-1"></i> Retry
                    </button>
                    <a href="logout.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm ml-2">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        <?php } ?>

        <?php if (isset($student_data) && $student_data) { ?>
            <!-- Header -->
            <header class="bg-primary text-white shadow-lg">
                <div class="w-full px-4 py-4 flex flex-col sm:flex-row justify-between items-center bg-gradient-to-r from-pink-600 via-blue-600 to-purple-600 bg-[length:400%_400%] animate-gradient">
                    <div class="flex items-center space-x-2 mb-2 sm:mb-0">
                        <img src="images/mtb-logo.png" alt="MTB Logo" class="h-12 w-auto">
                        <h1 class="text-lg sm:text-xl font-bold">MTB Student Portal</h1>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <span class="hidden sm:block text-sm">Welcome, <?php echo htmlspecialchars($student_data['Name']); ?></span>
                        <a href="logout.php" class="inline-flex items-center justify-center px-3 py-2 text-white bg-white/20 border border-white/30 hover:bg-red-500 focus:outline-none rounded-md shadow-md transition duration-300 ease-in-out text-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i> 
                            <span class="hidden sm:inline">Logout</span>
                            <span class="sm:hidden">Exit</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="container mx-auto px-4 py-4 sm:py-8">
                <!-- Student Profile Section -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 relative">
                    <div class="md:flex">
                        <div class="md:w-1/4 p-6 bg-gradient-to-br from-primary to-indigo-700 text-white flex flex-col items-center circle-bg rounded-xl shadow-md relative">
                            <button id="showDetailsBtn" class="show-details-btn md:hidden absolute top-4 right-4 bg-white text-primary p-2 rounded-full hover:bg-gray-100 transition-all z-20">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <?php 
                            $photo = isset($student_data['Student_Snap']) && !empty($student_data['Student_Snap']) 
                                ? 'data:image/jpeg;base64,' . base64_encode($student_data['Student_Snap']) 
                                : 'https://via.placeholder.com/150';
                            ?>
                            <div class="w-32 h-32 rounded-full border-4 border-white mb-4 overflow-hidden">
                                <img src="<?php echo $photo; ?>" alt="Student" class="w-full h-full object-cover">
                            </div>
                            <h2 class="text-base font-bold"><?php echo htmlspecialchars($student_data['Name']); ?></h2>
                            <p class="text-indigo-200">
                                <?php echo htmlspecialchars($student_data['ClassTitle'] ?? 'N/A'); ?> - 
                                <?php echo htmlspecialchars($student_data['SectionTitle'] ?? 'N/A'); ?> - 
                                <?php echo htmlspecialchars($student_data['GroupTitle'] ?? 'N/A'); ?>
                            </p>
                            <div class="mt-4 w-full student-details">
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Student ID:</span>
                                    <span class="font-semibold"><?php echo htmlspecialchars($student_data['StudentID']); ?></span>
                                </div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Registration No:</span>
                                    <span class="font-semibold"><?php echo htmlspecialchars($student_data['RegistrationNumber'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-3/4 p-6 student-details hidden md:block">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Personal Information</h3>
                                    <div class="space-y-2">
                                        <p><span class="text-gray-500">Father's Name:</span> <?php echo htmlspecialchars($student_data['FatherName'] ?? 'N/A'); ?></p>
                                        <p><span class="text-gray-500">Mother's Name:</span> <?php echo htmlspecialchars($student_data['MotherName'] ?? 'N/A'); ?></p>
                                        <p><span class="text-gray-500">Date of Birth:</span> <?php echo isset($student_data['DOB']) ? $student_data['DOB']->format('d-M-Y') : 'N/A'; ?></p>
                                        <p><span class="text-gray-500">Address:</span> <?php echo htmlspecialchars($student_data['Address'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Additional Details</h3>
                                    <div class="space-y-2">
                                        <p><span class="text-gray-500">CNIC:</span> <?php echo htmlspecialchars($student_data['CNIC'] ?? 'N/A'); ?></p>
                                        <p><span class="text-gray-500">Gender:</span> <?php echo htmlspecialchars($student_data['Gender'] ?? 'N/A'); ?></p>
                                        <p><span class="text-gray-500">Level:</span> <?php echo htmlspecialchars($student_data['ClassLevel'] ?? 'N/A'); ?></p> 
                                        <p><span class="text-gray-500">Session:</span> <?php echo htmlspecialchars($student_data['SessionTitle'] ?? 'N/A'); ?></p>                                                                            
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Guardian Information</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="bg-blue-100 p-3 rounded-lg">
                                            <p class="text-sm text-blue-600">Guardian Name: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['GuardianName'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="bg-green-100 p-3 rounded-lg">
                                            <p class="text-sm text-green-600">Contact: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['Mobile'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="bg-purple-100 p-3 rounded-lg">
                                            <p class="text-sm text-purple-600">Father's CNIC: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['FatherCNIC'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Details Modal -->
                    <div id="studentModal" class="modal modal-hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
                        <div class="bg-white rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                            <div class="bg-gradient-to-br from-primary to-indigo-700 text-white p-4 rounded-t-xl flex justify-between items-center">
                                <h3 class="text-lg font-bold">Student Details</h3>
                                <button id="closeModalBtn" class="text-white hover:text-gray-200 p-2 rounded-full hover:bg-white/20 transition-all">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>
                            
                            <div class="p-6">
                                <!-- Personal Information Section -->
                                <div class="mb-6">
                                    <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-user text-primary mr-2"></i>
                                        Personal Information
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Father's Name</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['FatherName'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Mother's Name</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['MotherName'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Date of Birth</span>
                                            <span class="font-semibold text-gray-800"><?php echo isset($student_data['DOB']) ? $student_data['DOB']->format('d-M-Y') : 'N/A'; ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Address</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['Address'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Details Section -->
                                <div class="mb-6">
                                    <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-info-circle text-primary mr-2"></i>
                                        Additional Details
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">CNIC</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['CNIC'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Gender</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['Gender'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Level</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['ClassLevel'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="text-gray-600 text-sm block">Session</span>
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($student_data['SessionTitle'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian Information Section -->
                                <div class="mb-6">
                                    <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-users text-primary mr-2"></i>
                                        Guardian Information
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="bg-blue-100 p-3 rounded-lg">
                                            <p class="text-sm text-blue-600">Guardian Name: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['GuardianName'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="bg-green-100 p-3 rounded-lg">
                                            <p class="text-sm text-green-600">Contact: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['Mobile'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="bg-purple-100 p-3 rounded-lg">
                                            <p class="text-sm text-purple-600">Father's CNIC: </p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student_data['FatherCNIC'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-200">
                                    <button id="closeModalBtn2" class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:from-primary-dark hover:to-indigo-700 text-white py-3 rounded-lg font-medium transition-all shadow-md">
                                        <i class="fas fa-times mr-2"></i>Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="responsive-grid grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Attendance Summary -->
                    <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-1">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Attendance Summary</h2>
                            <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full">Current Year</span>
                        </div>

                        <?php
                        $total_present = 0;
                        $total_absent = 0;
                        $total_leave = 0;
                        $total_days = 0;

                        foreach ($attendance_data as $row) {
                            $total_present += $row['PresentDays'];
                            $total_absent += $row['AbsentDays'];
                            $total_leave += $row['LeaveDays'];
                            $total_days += $row['TotalDays'];
                        }
                        ?>

                        <div class="flex justify-center mb-6">
                            <div class="relative w-40 h-40">
                                <svg class="w-full h-full" viewBox="0 0 100 100">
                                    <circle class="text-gray-200" stroke-width="8" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" />
                                    <circle class="progress-ring__circle text-secondary" stroke-width="8" stroke-linecap="round" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" 
                                            stroke-dasharray="251.2" stroke-dashoffset="20.096" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center flex-col">
                                    <span class="text-3xl font-bold text-gray-800">
                                        <?php 
                                        if ($total_days > 0) {
                                            echo 100 - round(($total_absent / $total_days) * 100, 0) . "%";
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </span>
                                    <span class="text-xs text-gray-500">Attendance</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-secondary mr-2"></div>
                                    <span class="text-sm">Present</span>
                                </div>
                                <span class="text-sm font-medium"><?php echo $total_present; ?> days</span>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                                    <span class="text-sm">Absent</span>
                                </div>
                                <span class="text-sm font-medium"><?php echo $total_absent; ?> days</span>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-accent mr-2"></div>
                                    <span class="text-sm">Leaves</span>
                                </div>
                                <span class="text-sm font-medium"><?php echo $total_leave; ?> days</span>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Attendance Chart -->
                    <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-2">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">Monthly Attendance Record</h2>
                        </div>
                        <div class="attendance-chart">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Academic Performance -->
                <div id="results-table" class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Academic Performance</h2>
                        <div class="flex items-center space-x-4">
                            <button id="viewAllBtn" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                                <i class="fas fa-eye mr-2"></i>View All
                            </button>
                            <div class="text-sm text-gray-500">
                                <span id="results-count">0</span> of <span id="total-results"><?php echo count($exam_results); ?></span> results
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($exam_results)) { ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Sr No</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Test</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Subject</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Date</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Obtained</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Total</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Percentage</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="results-tbody" class="bg-white divide-y divide-gray-200">
                                    <!-- Results will be loaded here dynamically -->
                                </tbody>
                            </table>
                            <div id="loading-indicator" class="text-center py-4 hidden">
                                <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading more results...
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="text-red-500 font-bold text-center mt-4 py-4">No exam results found for this student.</div>
                    <?php } ?>

                    <!-- View All Modal -->
                    <div id="viewAllModal" class="modal modal-hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
                        <div class="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-bold text-gray-800">Complete Academic Performance Report</h3>
                                    <div class="flex space-x-2">
                                        <button id="printReportBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                            <i class="fas fa-print mr-2"></i>Print
                                        </button>
                                        <button id="downloadPdfBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                                            <i class="fas fa-download mr-2"></i>Download PDF
                                        </button>
                                        <button id="closeViewAllBtn" class="text-gray-500 hover:text-gray-700 p-2">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="printableContent" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                                <div class="mb-6 text-center">
                                    <h1 class="text-2xl font-bold text-gray-800 mb-2">MTB Schools & Colleges</h1>
                                    <h2 class="text-lg font-semibold text-gray-600 mb-4">Academic Performance Report</h2>
                                    <div class="text-sm text-gray-500">
                                        <p><strong>Student:</strong> <?php echo htmlspecialchars($student_data['Name'] ?? 'N/A'); ?></p>
                                        <p><strong>Class:</strong> <?php echo htmlspecialchars($student_data['ClassTitle'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($student_data['SectionTitle'] ?? 'N/A'); ?></p>
                                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_data['StudentID'] ?? 'N/A'); ?></p>
                                        <p><strong>Report Generated:</strong> <?php echo date('d-M-Y H:i'); ?></p>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table id="allResultsTable" class="min-w-full divide-y divide-gray-200 border">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Sr No</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Test</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Subject</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Date</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Obtained</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Total</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Percentage</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="allResultsTbody" class="bg-white divide-y divide-gray-200">
                                            <!-- All results will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subject-wise Performance Analysis -->
                <?php 
                // Get subject-wise analysis data using direct SQL query
                $subject_analysis = [];
                if ($class_student_id) {
                    try {
                        $sql_subject_analysis = "
                            SELECT 
                                s.SubjectID,
                                s.Title AS SubjectName,
                                SUM(CASE WHEN esd.ObtainedMarks IS NOT NULL THEN esd.ObtainedMarks ELSE 0 END) AS TotalObtained,
                                SUM(esd.TotalMarks) AS TotalMarks,
                                MAX(CASE WHEN lt.rn = 1 THEN esd.ObtainedMarks END) AS LastObtainedMarks,
                                MAX(CASE WHEN lt.rn = 1 THEN esd.TotalMarks END) AS LastTotalMarks
                            FROM tbl1_10ExamStudentDetail e
                            JOIN tbl1_05ClassStudent cs ON e.ClassStudentID = cs.ClassStudentID
                            JOIN tbl1_11ExamSubjectsDetail esd ON e.ExamDetailID = esd.ExamDetailID
                            JOIN tbl0_08SubjectInfo s ON esd.SubjectID = s.SubjectID
                            JOIN tbl1_09ExamMaster em ON e.ExamID = em.ExamID
                            JOIN (
                                SELECT 
                                    esd2.ExamDetailID,
                                    esd2.SubjectID,
                                    ROW_NUMBER() OVER (PARTITION BY esd2.SubjectID ORDER BY esd2.PaperDate DESC, em2.ExamDate DESC) AS rn
                                FROM tbl1_11ExamSubjectsDetail esd2
                                JOIN tbl1_10ExamStudentDetail e2 ON esd2.ExamDetailID = e2.ExamDetailID
                                JOIN tbl1_09ExamMaster em2 ON e2.ExamID = em2.ExamID
                                WHERE e2.ClassStudentID = ? AND esd2.PaperDate IS NOT NULL
                            ) lt ON esd.ExamDetailID = lt.ExamDetailID AND esd.SubjectID = lt.SubjectID
                            WHERE cs.ClassStudentID = ?
                            GROUP BY s.SubjectID, s.Title
                            ORDER BY s.Title";
                        
                        $params_subject_analysis = array($class_student_id, $class_student_id);
                        $stmt_subject_analysis = sqlsrv_query($conn, $sql_subject_analysis, $params_subject_analysis);

                        if ($stmt_subject_analysis !== false) {
                            while ($row = sqlsrv_fetch_array($stmt_subject_analysis, SQLSRV_FETCH_ASSOC)) {
                                $subject_analysis[] = $row;
                            }
                            sqlsrv_free_stmt($stmt_subject_analysis);
                        }

                    } catch (Exception $e) {
                        error_log("Subject analysis error: " . $e->getMessage());
                        $subject_analysis = [];
                    }
                }
                ?>

                <!-- Subject styles for icons and colors -->
                <?php
                $subject_styles = [
                    'Physics' => ['icon' => 'fas fa-atom', 'text' => 'text-green-600', 'bg' => 'bg-green-100', 'bar' => 'bg-green-600'],
                    'Chemistry' => ['icon' => 'fas fa-flask', 'text' => 'text-purple-600', 'bg' => 'bg-purple-100', 'bar' => 'bg-purple-600'],
                    'English' => ['icon' => 'fas fa-language', 'text' => 'text-red-600', 'bg' => 'bg-red-100', 'bar' => 'bg-red-600'],
                    'Mathematics' => ['icon' => 'fas fa-square-root-alt', 'text' => 'text-blue-600', 'bg' => 'bg-blue-100', 'bar' => 'bg-blue-600'],
                    'Biology' => ['icon' => 'fas fa-dna', 'text' => 'text-teal-600', 'bg' => 'bg-teal-100', 'bar' => 'bg-teal-600'],
                    'Urdu' => ['icon' => 'fas fa-pen-nib', 'text' => 'text-yellow-600', 'bg' => 'bg-yellow-100', 'bar' => 'bg-yellow-600'],
                    'Drawing' => ['icon' => 'fas fa-paint-brush', 'text' => 'text-pink-600', 'bg' => 'bg-pink-100', 'bar' => 'bg-pink-600'],
                    'Islamiat' => ['icon' => 'fas fa-mosque', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'bar' => 'bg-emerald-600'],
                    'Pakistan Studies' => ['icon' => 'fas fa-map-marked', 'text' => 'text-lime-600', 'bg' => 'bg-lime-100', 'bar' => 'bg-lime-600'],
                    'General Science' => ['icon' => 'fas fa-microscope', 'text' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'bar' => 'bg-cyan-600'],
                    'General Knowledge' => ['icon' => 'fas fa-globe', 'text' => 'text-amber-600', 'bg' => 'bg-amber-100', 'bar' => 'bg-amber-600'],
                    'Rhymes' => ['icon' => 'fas fa-music', 'text' => 'text-rose-600', 'bg' => 'bg-rose-100', 'bar' => 'bg-rose-600'],
                    'Al Quran' => ['icon' => 'fas fa-book-open', 'text' => 'text-green-700', 'bg' => 'bg-green-200', 'bar' => 'bg-green-700'],
                    'Statistics' => ['icon' => 'fas fa-chart-bar', 'text' => 'text-blue-700', 'bg' => 'bg-blue-200', 'bar' => 'bg-blue-700'],
                    'English A' => ['icon' => 'fas fa-language', 'text' => 'text-red-700', 'bg' => 'bg-red-200', 'bar' => 'bg-red-700'],
                    'English B' => ['icon' => 'fas fa-language', 'text' => 'text-red-500', 'bg' => 'bg-red-100', 'bar' => 'bg-red-500'],
                    'Urdu-A' => ['icon' => 'fas fa-pen-fancy', 'text' => 'text-yellow-700', 'bg' => 'bg-yellow-200', 'bar' => 'bg-yellow-700'],
                    'Urdu-B' => ['icon' => 'fas fa-pen-fancy', 'text' => 'text-yellow-500', 'bg' => 'bg-yellow-100', 'bar' => 'bg-yellow-500'],
                    'Computer Science' => ['icon' => 'fas fa-laptop', 'text' => 'text-indigo-700', 'bg' => 'bg-indigo-200', 'bar' => 'bg-indigo-700'],
                    'Social Studies' => ['icon' => 'fas fa-users', 'text' => 'text-orange-600', 'bg' => 'bg-orange-100', 'bar' => 'bg-orange-600'],
                    'Economics' => ['icon' => 'fas fa-money-bill', 'text' => 'text-green-500', 'bg' => 'bg-green-100', 'bar' => 'bg-green-500'],
                    'Geography' => ['icon' => 'fas fa-map', 'text' => 'text-blue-500', 'bg' => 'bg-blue-100', 'bar' => 'bg-blue-500'],
                    'History' => ['icon' => 'fas fa-landmark', 'text' => 'text-purple-700', 'bg' => 'bg-purple-200', 'bar' => 'bg-purple-700'],
                    'Home Economics' => ['icon' => 'fas fa-home', 'text' => 'text-pink-500', 'bg' => 'bg-pink-100', 'bar' => 'bg-pink-500'],
                    'Political Science' => ['icon' => 'fas fa-balance-scale', 'text' => 'text-amber-700', 'bg' => 'bg-amber-200', 'bar' => 'bg-amber-700'],
                    'Health & Physical Education' => ['icon' => 'fas fa-heartbeat', 'text' => 'text-red-400', 'bg' => 'bg-red-50', 'bar' => 'bg-red-400'],
                    'Education' => ['icon' => 'fas fa-graduation-cap', 'text' => 'text-cyan-700', 'bg' => 'bg-cyan-200', 'bar' => 'bg-cyan-700'],
                    'Tarjuma-tul-Quran' => ['icon' => 'fas fa-mosque', 'text' => 'text-cyan-700', 'bg' => 'bg-cyan-200', 'bar' => 'bg-cyan-700'],
                    'default' => ['icon' => 'fas fa-book', 'text' => 'text-gray-500', 'bg' => 'bg-gray-100', 'bar' => 'bg-gray-500']
                ];
                ?>

                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-800">Subject-wise Performance</h2>
                    </div>

                    <?php if (!empty($subject_analysis)) { ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
                            <?php foreach ($subject_analysis as $subject) { 
                                $percentage = ($subject['TotalMarks'] > 0) 
                                    ? number_format(($subject['TotalObtained'] / $subject['TotalMarks']) * 100, 0) 
                                    : 0;
                                $last_test = ($subject['LastObtainedMarks'] !== null && $subject['LastTotalMarks'] !== null) 
                                    ? number_format((float)$subject['LastObtainedMarks'], 0) . '/' . number_format((float)$subject['LastTotalMarks'], 0) 
                                    : ($subject['LastObtainedMarks'] === null ? 'Absent' : 'N/A');

                                $subject_name = htmlspecialchars($subject['SubjectName']);
                                $style = $subject_styles[$subject_name] ?? $subject_styles['default'];
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow duration-300">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-500"><?php echo $subject_name; ?></h3>
                                            <p class="text-base font-bold text-gray-800"><?php echo $percentage; ?>%</p>
                                        </div>
                                        <div class="<?php echo $style['bg']; ?> p-2 rounded-lg">
                                            <i class="<?php echo $style['icon']; ?> <?php echo $style['text']; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="<?php echo $style['bar']; ?> h-2 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <span>Last Test: <?php echo htmlspecialchars($last_test); ?></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-line text-4xl mb-4"></i>
                            <p>No subject-wise performance data available.</p>
                        </div>
                    <?php } ?>
                </div>

                

                <!-- Fee Schedule Section -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Fee Payment Schedule</h2>
                        <div class="text-sm text-gray-500">
                            <span id="fee-count">0</span> of <span id="total-fees"><?php echo count($fee_results); ?></span> records
                        </div>
                    </div>

                    <?php if (!empty($fee_results)) { ?>
                        <div class="ml-2 w-full overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Date</th>
                                        <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="fee-tbody" class="bg-white divide-y divide-gray-200">
                                    <!-- Fee records will be loaded here dynamically -->
                                </tbody>
                            </table>
                            <div id="fee-loading-indicator" class="text-center py-4 hidden">
                                <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading more records...
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="text-center py-4 text-gray-500">No fee details found for this student.</div>
                    <?php } ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-dark text-white">
                <div class="flex items-center justify-center h-16 border-t border-gray-800 text-sm text-gray-400 text-center">
                    © 2025 MTB Schools & Colleges. All rights reserved. | Designed with 
                    <i class="fas fa-heart text-red-500 mx-1"></i> for education
                </div>
            </footer>

        <?php } else { ?>
            <div class="text-red-500 font-bold text-center my-4">No Student data found. Please log in.</div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data arrays for lazy loading
        const examResults = <?php echo json_encode($exam_results ?? []); ?>;
        const feeResults = <?php echo json_encode(array_map(function($fee) {
            // Convert DateTime objects to strings for JSON
            if (isset($fee['FeeDueDate']) && is_object($fee['FeeDueDate'])) {
                $fee['FeeDueDate'] = $fee['FeeDueDate']->format('d-M-Y');
            }
            if (isset($fee['Entry_Date_Time']) && is_object($fee['Entry_Date_Time'])) {
                $fee['Entry_Date_Time'] = $fee['Entry_Date_Time']->format('d-M-Y');
            }
            return $fee;
        }, $fee_results)); ?>;

        let currentExamIndex = 0;
        let currentFeeIndex = 0;
        const itemsPerLoad = 3; // Load 3 items at a time
        let isLoading = false;

        // Status classes mapping
        const statusClasses = {
            'Outstanding': 'bg-purple-100 text-purple-800',
            'Excellent': 'bg-green-100 text-green-800',
            'Very Good': 'bg-blue-100 text-blue-800',
            'Good': 'bg-blue-100 text-blue-800',
            'Average': 'bg-yellow-100 text-yellow-800',
            'Satisfactory': 'bg-yellow-100 text-yellow-800',
            'Low': 'bg-red-100 text-red-800',
            'Fail': 'bg-red-100 text-red-800',
            'Absent': 'bg-gray-100 text-gray-800',
            'N/A': 'bg-gray-100 text-gray-800'
        };

        // Function to load exam results progressively
        function loadMoreExamResults() {
            if (isLoading || currentExamIndex >= examResults.length) return;

            isLoading = true;
            document.getElementById('loading-indicator').classList.remove('hidden');

            setTimeout(() => { // Simulate loading delay
                const tbody = document.getElementById('results-tbody');
                const endIndex = Math.min(currentExamIndex + itemsPerLoad, examResults.length);

                for (let i = currentExamIndex; i < endIndex; i++) {
                    const exam = examResults[i];
                    const statusClass = statusClasses[exam.Status] || statusClasses['N/A'];

                    const row = document.createElement('tr');
                    row.className = 'opacity-0 transform translate-y-4 transition-all duration-500';
                    row.innerHTML = `
                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 leading-tight">${i + 1}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 leading-tight">${exam.ExamType || 'N/A'}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 leading-tight">${exam.SubjectName || 'N/A'}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 leading-tight">${exam.ExamDate || 'N/A'}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 leading-tight">${exam.ObtainedMarks !== null ? exam.ObtainedMarks : 'Absent'}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 leading-tight">${exam.TotalMarks || 0}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 leading-tight">${exam.Percentage || 0}%</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${exam.Status || 'N/A'}</span>
                        </td>
                    `;
                    tbody.appendChild(row);

                    // Trigger animation
                    setTimeout(() => {
                        row.classList.remove('opacity-0', 'translate-y-4');
                    }, 50);
                }

                currentExamIndex = endIndex;
                document.getElementById('results-count').textContent = currentExamIndex;
                document.getElementById('loading-indicator').classList.add('hidden');
                isLoading = false;
            }, 800);
        }

        // Function to load all exam results in modal
        function loadAllExamResults() {
            const tbody = document.getElementById('allResultsTbody');
            tbody.innerHTML = '';

            examResults.forEach((exam, index) => {
                const statusClass = statusClasses[exam.Status] || statusClasses['N/A'];
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2 text-sm font-medium text-gray-900 border">${index + 1}</td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-900 border">${exam.ExamType || 'N/A'}</td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-900 border">${exam.SubjectName || 'N/A'}</td>
                    <td class="px-3 py-2 text-sm text-gray-500 border">${exam.ExamDate || 'N/A'}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 border">${exam.ObtainedMarks !== null ? exam.ObtainedMarks : 'Absent'}</td>
                    <td class="px-3 py-2 text-sm text-gray-500 border">${exam.TotalMarks || 0}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 border">${exam.Percentage || 0}%</td>
                    <td class="px-3 py-2 border">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${exam.Status || 'N/A'}</span>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        

        // Function to load fee results progressively
        function loadMoreFeeResults() {
            if (isLoading || currentFeeIndex >= feeResults.length) return;

            isLoading = true;
            document.getElementById('fee-loading-indicator').classList.remove('hidden');

            setTimeout(() => { // Simulate loading delay
                const tbody = document.getElementById('fee-tbody');
                const endIndex = Math.min(currentFeeIndex + itemsPerLoad, feeResults.length);

                for (let i = currentFeeIndex; i < endIndex; i++) {
                    const fee = feeResults[i];
                    const status = fee.IsPad == 1 ? 'Paid' : 'Payable';
                    const statusClass = fee.IsPad == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const paAmount = status === 'Paid' ? '' : (fee.LineTotal ? parseInt(fee.LineTotal).toLocaleString() : '');
                    const paidAmount = status !== 'Paid' ? '' : (fee.LineTotal ? parseInt(fee.LineTotal).toLocaleString() : '');
                    const paidDate = status === 'Paid' && fee.Entry_Date_Time ? fee.Entry_Date_Time : '';

                    let feeType = fee.FeeType || 'N/A';
                    feeType = feeType.toLowerCase().split(' ').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');

                    const row = document.createElement('tr');
                    row.className = 'opacity-0 transform translate-y-4 transition-all duration-500';
                    row.innerHTML = `
                        <td class="px-2 py-1 whitespace-nowrap text-sm font-medium text-gray-900">${feeType}</td>
                        <td class="px-2 py-1 whitespace-nowrap text-sm text-gray-500">${fee.FeeDueDate || 'N/A'}</td>
                        <td class="px-2 py-1 whitespace-nowrap text-sm text-gray-900">${paAmount}</td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${status}</span>
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap text-sm text-gray-500">${paidDate}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900">${paidAmount}</td>
                    `;
                    tbody.appendChild(row);

                    // Trigger animation
                    setTimeout(() => {
                        row.classList.remove('opacity-0', 'translate-y-4');
                    }, 50);
                }

                currentFeeIndex = endIndex;
                document.getElementById('fee-count').textContent = currentFeeIndex;
                document.getElementById('fee-loading-indicator').classList.add('hidden');
                isLoading = false;
            }, 800);
        }

        // Attendance Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    foreach ($attendance_data as $monthNum => $row) {
                        echo "'" . date('M', mktime(0, 0, 0, $monthNum, 1)) . "',";
                    }
                ?>],
                datasets: [
                    {
                        label: 'Present',
                        data: [<?php 
                            foreach ($attendance_data as $row) {
                                echo $row['PresentDays'] . ",";
                            }
                        ?>],
                        backgroundColor: '#10B981',
                        borderRadius: 4
                    },
                    {
                        label: 'Absent',
                        data: [<?php 
                            foreach ($attendance_data as $row) {
                                echo $row['AbsentDays'] . ",";
                            }
                        ?>],
                        backgroundColor: '#EF4444',
                        borderRadius: 4
                    },
                    {
                        label: 'Leaves',
                        data: [<?php 
                            foreach ($attendance_data as $row) {
                                echo $row['LeaveDays'] . ",";
                            }
                        ?>],
                        backgroundColor: '#F59E0B',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        max: 25,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });

        // Intersection Observer for infinite scroll
        function createInfiniteScroll() {
            const resultsTable = document.getElementById('results-table');
            const feeTable = resultsTable.nextElementSibling.nextElementSibling; // Skip subject performance cards

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (entry.target.id === 'results-table') {
                            loadMoreExamResults();
                        } else {
                            loadMoreFeeResults();
                        }
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            observer.observe(resultsTable);
            observer.observe(feeTable);
        }

        // Scroll event listener for continuous loading
        function handleScroll() {
            const scrollPosition = window.innerHeight + window.scrollY;
            const documentHeight = document.documentElement.offsetHeight;

            // Load more when user is 80% down the page
            if (scrollPosition >= documentHeight * 0.8) {
                if (currentExamIndex < examResults.length) {
                    loadMoreExamResults();
                }
                if (currentFeeIndex < feeResults.length) {
                    loadMoreFeeResults();
                }
            }
        }

        // Main initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Progress rings
            const circles = document.querySelectorAll('.progress-ring__circle');
            circles.forEach(circle => {
                const radius = circle.r.baseVal.value;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (92 / 100) * circumference;
                circle.style.strokeDasharray = circumference;
                circle.style.strokeDashoffset = offset;
            });

            // Student Details Modal
            const studentModal = document.getElementById('studentModal');
            const showBtn = document.getElementById('showDetailsBtn');
            const closeStudentModalBtns = document.querySelectorAll('#closeModalBtn, #closeModalBtn2');

            if (showBtn) {
                showBtn.addEventListener('click', () => {
                    studentModal.classList.remove('modal-hidden');
                });
            }

            closeStudentModalBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    studentModal.classList.add('modal-hidden');
                });
            });

            studentModal.addEventListener('click', (e) => {
                if (e.target === studentModal) {
                    studentModal.classList.add('modal-hidden');
                }
            });

            // View All Modal
            const viewAllModal = document.getElementById('viewAllModal');
            const viewAllBtn = document.getElementById('viewAllBtn');
            const closeViewAllBtn = document.getElementById('closeViewAllBtn');
            const printReportBtn = document.getElementById('printReportBtn');
            const downloadPdfBtn = document.getElementById('downloadPdfBtn');

            if (viewAllBtn) {
                viewAllBtn.addEventListener('click', () => {
                    loadAllExamResults();
                    viewAllModal.classList.remove('modal-hidden');
                });
            }

            if (closeViewAllBtn) {
                closeViewAllBtn.addEventListener('click', () => {
                    viewAllModal.classList.add('modal-hidden');
                });
            }

            if (printReportBtn) {
                printReportBtn.addEventListener('click', () => {
                    const printContent = document.getElementById('printableContent').innerHTML;
                    const originalContent = document.body.innerHTML;
                    
                    document.body.innerHTML = `
                        <style>
                            @media print {
                                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                                th { background-color: #f5f5f5; }
                                .bg-purple-100 { background-color: #e9d5ff !important; }
                                .bg-green-100 { background-color: #dcfce7 !important; }
                                .bg-blue-100 { background-color: #dbeafe !important; }
                                .bg-yellow-100 { background-color: #fef3c7 !important; }
                                .bg-red-100 { background-color: #fee2e2 !important; }
                                .bg-gray-100 { background-color: #f3f4f6 !important; }
                            }
                        </style>
                        ${printContent}
                    `;
                    
                    window.print();
                    document.body.innerHTML = originalContent;
                    location.reload();
                });
            }

            if (downloadPdfBtn) {
                downloadPdfBtn.addEventListener('click', () => {
                    alert('PDF download functionality requires additional implementation. Please use the Print option and save as PDF from your browser.');
                });
            }

            viewAllModal.addEventListener('click', (e) => {
                if (e.target === viewAllModal) {
                    viewAllModal.classList.add('modal-hidden');
                }
            });

            // Initialize infinite scroll
            createInfiniteScroll();
            window.addEventListener('scroll', handleScroll);

            // Load initial content
            loadMoreExamResults();
            loadMoreFeeResults();
        });
    </script>
</body>
</html>