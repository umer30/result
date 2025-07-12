<?php
session_start();

require_once 'conn.php';
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#4F46E5',
                    secondary: '#10B981',
                    accent: '#F59E0B',
                    dark: '#1F2937',
                    light: '#F3F4F6',
                },
                fontFamily: {
                    sans: ['tahoma', 'arial', 'helvetica'],
                },
                animation: {
                    gradient: 'gradient 6s ease infinite',
                },
                keyframes: {
                    gradient: {
                        '0%, 100%': {
                            'background-position': '0% 50%',
                        },
                        '50%': {
                            'background-position': '100% 50%',
                        },
                    },
                },
            }
        }
    }
    </script>
    <style>
        .attendance-chart {
            height: 300px;
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
        @media (max-width: 768px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
            .student-details {
                display: none;
            }
            .show-details-btn {
                display: block;
            }
        }
        @media (min-width: 769px) {
            .show-details-btn {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen">
        <?php if (isset($_SESSION['student_id'])) {
            $student_id = $_SESSION['student_id'];
        ?>
            <!-- Header -->
            <header class="bg-primary text-white shadow-lg">
                <div class="w-full px-4 py-4 flex justify-between items-center text-2lg bg-gradient-to-r from-pink-600 via-blue-600 to-purple-600 bg-[length:400%_400%] animate-gradient text-center">
                    <div class="flex items-center space-x-2 ml-6">
                        <i class="fas fa-graduation-cap text-2xl"></i>
                        <h1 class="text-2xl font-bold">MTB Schools & Colleges</h1>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="images/mtb-logo.png" alt="MTB Logo" class="h-14 w-auto">
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="hidden md:block">Welcome, Testing Name</span>
                        <a href="logout.php" class="inline-flex items-center justify-center px-4 py-1.5 text-white-500 bg-white/20 border border-white-500 hover:text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded-md shadow-md transition duration-300 ease-in-out text-sm">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
			
			
			
          
		  
		  
		  
		  
		  
<!-- Student Profile Section -->
            
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 relative">
<button id="showDetailsBtn" class="show-details-btn md:hidden absolute top-4 right-4 bg-primary text-white p-2 rounded-full">
<i class="fas fa-info-circle"></i>
</button>

<?php
                        $sql = "SELECT s.StudentID, s.RegistrationNumber, s.Name, s.DOB, s.Gender, s.Address, s.Mobile, s.FatherName, 
                                       s.MotherName, s.GuardianName, s.Student_Snap, s.CNIC, s.FatherCNIC, s.IsActive,
                                       cs.ClassStudentID, cs.InstituteClassID, cs.GroupID, cs.IsActive AS ClassActive, cs.IsExpelled,
                                       g.Title AS GroupTitle, cl.Title AS ClassLevel, c.Title AS ClassTitle, sec.Title AS SectionTitle,
                                       sess.Title AS SessionTitle
                                FROM tbl0_02StudentInfo s
                                LEFT JOIN tbl1_05ClassStudent cs ON s.StudentID = cs.StudentID AND cs.IsActive = 1
                                LEFT JOIN tbl1_01InstituteClass ic ON cs.InstituteClassID = ic.InstituteClassID
                                LEFT JOIN tbl0_07ClassInfo c ON ic.ClassID = c.ClassID
                                LEFT JOIN tbl_ClassLevel cl ON c.LevelID = cl.LevelID
                                LEFT JOIN tbl0_06SectionInfo sec ON ic.SectionID = sec.SectionID
                                LEFT JOIN tbl0_06Session sess ON ic.Session = sess.Title
                                LEFT JOIN tbl0_08GroupInfo g ON cs.GroupID = g.GroupID
                                WHERE s.StudentID = ?";

                        $params = array($student_id);
                        $stmt = sqlsrv_query($conn, $sql, $params);

                        if ($stmt === false) {
                            echo '<div class="text-red-500 font-bold text-center my-4">Error: ' . print_r(sqlsrv_errors(), true) . '</div>';
                        } else {
                            $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                            if ($student) {
                                
                                $photo = isset($student['Student_Snap']) && !empty($student['Student_Snap']) ? 'data:image/jpeg;base64,' . base64_encode($student['Student_Snap']) : 'https://via.placeholder.com/150';
                    ?>
                                <div class="md:flex">
                                   
<div class="md:w-1/4 p-6 bg-gradient-to-br from-primary to-indigo-700 text-white flex flex-col items-center circle-bg rounded-xl shadow-md">
    <div class="w-32 h-32 rounded-full border-4 border-white mb-4 overflow-hidden">
        <img src="<?php echo $photo; ?>" alt="Student" class="w-full h-full object-cover">
    </div>
    <h2 class="text-base font-bold"><?php echo htmlspecialchars($student['Name']); ?></h2>
    <p class="text-indigo-200">
        <?php echo htmlspecialchars($student['ClassTitle'] ?? 'N/A'); ?> - 
        <?php echo htmlspecialchars($student['SectionTitle'] ?? 'N/A'); ?> - 
        <?php echo htmlspecialchars($student['GroupTitle'] ?? 'N/A'); ?>
    </p>
    <div class="mt-4 w-full student-details">
        <div class="flex justify-between text-sm mb-1">
            <span>Student ID:</span>
            <span class="font-semibold"><?php echo htmlspecialchars($student['StudentID']); ?></span>
        </div>
        <div class="flex justify-between text-sm mb-1">
            <span>Registration No:</span>
            <span class="font-semibold"><?php echo htmlspecialchars($student['RegistrationNumber'] ?? 'N/A'); ?></span>
        </div>
    </div>
</div>
                                    <div class="md:w-3/4 p-6 student-details">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-700 mb-2" >Personal Information</h3>
                                                <div class="space-y-2">
                                                    <p><span class="text-gray-500">Father's Name:</span> <?php echo htmlspecialchars($student['FatherName'] ?? 'N/A'); ?></p>
                                                    <p><span class="text-gray-500">Mother's Name:</span> <?php echo htmlspecialchars($student['MotherName'] ?? 'N/A'); ?></p>
                                                    <p><span class="text-gray-500">Date of Birth:</span> <?php echo isset($student['DOB']) ? $student['DOB']->format('d-M-Y') : 'N/A'; ?></p>
                                                    <p><span class="text-gray-500">Address:</span> <?php echo htmlspecialchars($student['Address'] ?? 'N/A'); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Additional Details</h3>
                                                <div class="space-y-2">
                                                    <p><span class="text-gray-500">CNIC:</span> <?php echo htmlspecialchars($student['CNIC'] ?? 'N/A'); ?></p>
                                                    <p><span class="text-gray-500">Gender:</span> <?php echo htmlspecialchars($student['Gender'] ?? 'N/A'); ?></p>
                                                    <p><span class="text-gray-500">Level:</span> <?php echo htmlspecialchars($student['ClassLevel'] ?? 'N/A'); ?></p> 
                                                    <p><span class="text-gray-500">Session:</span> <?php echo htmlspecialchars($student['SessionTitle'] ?? 'N/A'); ?></p>                                                                            
													
                                                </div>
                                            </div>
                                            <div class="md:col-span-2">
                                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Guardian Information</h3>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div class="bg-blue-100 p-3 rounded-lg">
                                                        <p class="text-sm text-blue-600">Guardian Name: </p>
                                                        <p class="font-medium"><?php echo htmlspecialchars($student['GuardianName'] ?? 'N/A'); ?></p>
                                                    </div>
                                                    <div class="bg-green-100 p-3 rounded-lg">
                                                        <p class="text-sm text-green-600">Contact: </p>
                                                        <p class="font-medium"><?php echo htmlspecialchars($student['Mobile'] ?? 'N/A'); ?></p>
                                                    </div>
                                                    <div class="bg-purple-100 p-3 rounded-lg">
                                                        <p class="text-sm text-purple-600">Father's CNIC: </span>
                                                        <p class="font-medium"><?php echo htmlspecialchars($student['FatherCNIC'] ?? 'N/A'); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
								
								   <!-- Student Details Modal -->
                <div id="studentModal" class="modal modal-hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
                    <div class="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-gray-800">Student Details</h3>
                                <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php if (isset($student) && $student) { ?>
                                <div class="space-y-4">
                                    <div class="bg-gradient-to-br from-primary to-indigo-700 text-white p-4 rounded-lg flex flex-col items-center">
                                        <div class="w-24 h-24 rounded-full border-4 border-white mb-3 overflow-hidden">
                                            <img src="<?php echo $photo; ?>" alt="Student" class="w-full h-full object-cover">
                                        </div>
                                        <h2 class="text-lg font-bold"><?php echo htmlspecialchars($student['Name']); ?></h2>
                                        <p class="text-indigo-200 text-sm"><?php echo htmlspecialchars($student['ClassTitle'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($student['SectionTitle'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Student ID</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student['StudentID']); ?></p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Registration No</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student['RegistrationNumber'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Status</p>
                                            <p class="font-medium"><?php echo $student['IsActive'] ? 'Active' : 'Inactive'; ?></p>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <p class="text-xs text-gray-500">Father Name</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($student['FatherName'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Personal Info</h4>
                                        <div class="space-y-2 text-sm">
                                            <p><span class="text-gray-500">Mother's Name:</span> <?php echo htmlspecialchars($student['MotherName'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">DOB:</span> <?php echo isset($student['DOB']) ? $student['DOB']->format('d-M-Y') : 'N/A'; ?></p>
                                            <p><span class="text-gray-500">Contact:</span> <?php echo htmlspecialchars($student['Mobile'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Address:</span> <?php echo htmlspecialchars($student['Address'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2">Additional Info</h4>
                                        <div class="space-y-2 text-sm">
                                            <p><span class="text-gray-500">CNIC:</span> <?php echo htmlspecialchars($student['CNIC'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Gender:</span> <?php echo htmlspecialchars($student['Gender'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Level:</span> <?php echo htmlspecialchars($student['ClassLevel'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Session:</span> <?php echo htmlspecialchars($student['SessionTitle'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Group:</span> <?php echo htmlspecialchars($student['GroupTitle'] ?? 'N/A'); ?></p>
                                            <p><span class="text-gray-500">Class Status:</span> <?php echo $student['ClassActive'] ? 'Active' : 'Inactive'; ?></p>
                                            <p><span class="text-gray-500">Expelled:</span> <?php echo $student['IsExpelled'] ? 'Yes' : 'No'; ?></p>
                                        </div>
                                    </div>
                                    <div class="pt-4 border-t border-gray-200">
                                        <button id="closeModalBtn2" class="w-full bg-primary text-white py-2 rounded-lg">Close</button>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <p class="text-gray-500 text-center">No student data available. Please log in.</p>
                                <div class="pt-4 border-t border-gray-200">
                                    <button id="closeModalBtn2" class="w-full bg-primary text-white py-2 rounded-lg">Close</button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                    <?php                                
					sqlsrv_free_stmt($stmt);
                            } else {
                                echo '<div class="text-red-500 font-bold text-center my-4">No student found with ID: ' . htmlspecialchars($student_id) . '</div>';
                            }
                        }
                   
					?>            
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

$sql_attendance = "	WITH Deduplicated AS (
    SELECT 
        a.AttendanceDate, 
        a.IsPresent, 
        a.LeaveTypeID,
        ROW_NUMBER() OVER (
            PARTITION BY a.ClassStudentID, a.AttendanceDate 
            ORDER BY a.Entry_DateTime DESC
        ) AS rn
    FROM tbl0_06StudentAttendance a
    INNER JOIN tbl1_05ClassStudent cs 
        ON a.ClassStudentID = cs.ClassStudentID
    WHERE cs.StudentID = ?  
      AND cs.IsActive = 1
),
Filtered AS (
    SELECT 
        AttendanceDate, 
        IsPresent, 
        LeaveTypeID
    FROM Deduplicated
    WHERE rn = 1
      AND (IsPresent IS NOT NULL OR LeaveTypeID IS NOT NULL)
),
BaseCounts AS (
    SELECT 
        YEAR(AttendanceDate) AS Year,
        MONTH(AttendanceDate) AS Month,
        DATENAME(MONTH, AttendanceDate) AS MonthName,

        COUNT(CASE WHEN IsPresent = 1 THEN 1 END) AS PresentDays,
        COUNT(CASE WHEN IsPresent = 0 AND LeaveTypeID IS NULL THEN 1 END) AS AbsentDays,
        COUNT(CASE WHEN LeaveTypeID = 1 THEN 1 END) AS LeaveDays
    FROM Filtered
    GROUP BY 
        YEAR(AttendanceDate), 
        MONTH(AttendanceDate), 
        DATENAME(MONTH, AttendanceDate)
)
SELECT 
    *,
    (PresentDays + AbsentDays + LeaveDays) AS TotalDays,
    CAST(PresentDays * 100.0 / NULLIF(PresentDays + AbsentDays + LeaveDays, 0) AS DECIMAL(5,2)) AS PresentPercentage,
    CAST(AbsentDays * 100.0 / NULLIF(PresentDays + AbsentDays + LeaveDays, 0) AS DECIMAL(5,2)) AS AbsentPercentage
FROM BaseCounts
ORDER BY Year ASC, Month ASC";
					
									
                    $params_attendance = array($student_id);
                    $stmt_attendance = sqlsrv_query($conn, $sql_attendance, $params_attendance);


                        $total_present = 0;
                        $total_absent = 0;
                        $total_leave = 0;
                        $total_days = 0;						
                        $attendance = array();
						
						
                        while ($row = sqlsrv_fetch_array($stmt_attendance, SQLSRV_FETCH_ASSOC)) {
                            $attendance[$row['Month']] = $row;
							
							$total_present += $row['PresentDays'];
                            $total_absent += $row['AbsentDays'];
                            $total_leave += $row['LeaveDays'];
                            $total_days += $row['TotalDays'];
                        }
						
						

                        sqlsrv_free_stmt($stmt_attendance);
?>

          <div class="flex justify-center mb-6">
                            <div class="relative w-40 h-40">
                                <svg class="w-full h-full" viewBox="0 0 100 100">
                                    <circle class="text-gray-200" stroke-width="8" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" />
                                    <circle class="progress-ring__circle text-secondary" stroke-width="8" stroke-linecap="round" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" 
                                            stroke-dasharray="251.2" stroke-dashoffset="20.096" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center flex-col">
                                    <span class="text-3xl font-bold text-gray-800"><?php 
										
										$total_days = isset($total_days) ? $total_days : 0;
										$total_absent = isset($total_absent) ? $total_absent : 0;										
										if ($total_days > 0) {
											echo  100-round(($total_absent / $total_days) * 100, 0) . "%";
										} else {
											echo "N/A";
										}
									?></span>
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
                  
<?php
$student_id = (int)$_SESSION['student_id'];
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;

// Step 1: Get the latest ClassStudentID for the StudentID
$sql_class_student = "SELECT TOP 1 ClassStudentID
                     FROM tbl1_05ClassStudent
                     WHERE StudentID = ?
                     ORDER BY ClassStudentID DESC";
$params_class_student = array($student_id);
$stmt_class_student = sqlsrv_query($conn, $sql_class_student, $params_class_student);



$row_class_student = sqlsrv_fetch_array($stmt_class_student, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_class_student);

if (!$row_class_student) {
    echo '<div class="text-red-500 font-bold text-center mt-4">No enrollment found for this Student ID.</div>';
    exit;
}

$class_student_id = $row_class_student['ClassStudentID'];

// Step 2: Fetch exam results using ClassStudentID
$sql_exam = "SELECT 
                et.Title,
                em.ExamDate,
                e.TotalObtainedMarks,
                e.GrandTotalMarks,
                e.Position,
                e.ExamDetailID,
                et.ExamTypeID
             FROM tbl1_10ExamStudentDetail e
             JOIN tbl1_05ClassStudent cs ON e.ClassStudentID = cs.ClassStudentID
             JOIN tbl1_09ExamMaster em ON e.ExamID = em.ExamID
             JOIN tbl0_10ExamTypeInfo et ON em.ExamTypeID = et.ExamTypeID
             WHERE cs.ClassStudentID = ?";
$params_exam = array($class_student_id);
$stmt_exam = sqlsrv_query($conn, $sql_exam, $params_exam);

if ($stmt_exam === false) {
    echo '<div class="text-red-500 font-bold text-center mt-4">Error fetching exam results: ' . print_r(sqlsrv_errors(), true) . '</div>';
    exit;
}

$exam_results = array();
while ($row = sqlsrv_fetch_array($stmt_exam, SQLSRV_FETCH_ASSOC)) {
    $exam_results[] = $row;
}
sqlsrv_free_stmt($stmt_exam);

if (!empty($exam_results)) {
    // Group results by ExamTypeID
    $grouped_results = [];
    foreach ($exam_results as $exam) {
        $examTypeID = $exam['ExamTypeID'];
        if (!isset($grouped_results[$examTypeID])) {
            $grouped_results[$examTypeID] = [
                'Title' => $exam['Title'],
                'ExamDate' => $exam['ExamDate'],
                'subjects' => []
            ];
        }
        $sql_subjects = "SELECT s.Title AS SubjectName, esd.ObtainedMarks, esd.TotalMarks, esd.PaperDate
                         FROM tbl1_11ExamSubjectsDetail esd
                         JOIN tbl0_08SubjectInfo s ON esd.SubjectID = s.SubjectID
                         WHERE esd.ExamDetailID = ?
                         ORDER BY esd.dOrder";
        $params_subjects = array($exam['ExamDetailID']);
        $stmt_subjects = sqlsrv_query($conn, $sql_subjects, $params_subjects);
        if ($stmt_subjects !== false) {
            while ($subject = sqlsrv_fetch_array($stmt_subjects, SQLSRV_FETCH_ASSOC)) {
                $subject_date = ($subject['PaperDate'] instanceof DateTime) ? $subject['PaperDate']->format('d-M-Y') : ($subject['PaperDate'] ?? 'N/A');
                $obtained = ($subject['ObtainedMarks'] === null) ? 'Absent' : (is_numeric($subject['ObtainedMarks']) ? number_format((float)$subject['ObtainedMarks'], 0) : $subject['ObtainedMarks']);
                $total = ($subject['TotalMarks'] === null) ? 'N/A' : (is_numeric($subject['TotalMarks']) ? number_format((float)$subject['TotalMarks'], 0) : $subject['TotalMarks']);
                // Calculate Percentage and Status
                $percentage = ($obtained === 'Absent' || $total === 'N/A') ? 'N/A' : round(($obtained / $total) * 100, 2) . '%';
                $status = 'N/A';
                if ($subject['ObtainedMarks'] !== null && $subject['TotalMarks'] > 0) {
                    $percent = ($subject['ObtainedMarks'] / $subject['TotalMarks']) * 100;
                    if ($percent >= 90) $status = 'Outstanding';
                    elseif ($percent >= 80) $status = 'Excellent';
                    elseif ($percent >= 70) $status = 'Very Good';
                    elseif ($percent >= 60) $status = 'Good';
                    elseif ($percent >= 50) $status = 'Average';
                    elseif ($percent >= 40) $status = 'Satisfactory';
                    elseif ($percent >= 33) $status = 'Low';
                    else $status = 'Fail';
                } elseif ($subject['ObtainedMarks'] === null) {
                    $status = 'Absent';
                }
                $grouped_results[$examTypeID]['subjects'][] = [
                    'SubjectName' => $subject['SubjectName'],
                    'ObtainedMarks' => $obtained,
                    'TotalMarks' => $total,
                    'Percentage' => $percentage,
                    'Status' => $status,
                    'PaperDate' => $subject_date
                ];
            }
            sqlsrv_free_stmt($stmt_subjects);
        }
    }

    // Pagination logic for main table
    $records_per_page = 10;
    $total_records = 0;

    // Count total records
    foreach ($grouped_results as $group) {
        $total_records += count($group['subjects']);
    }

    $total_pages = max(1, ceil($total_records / $records_per_page));
    $current_page = min($current_page, $total_pages); // Prevent exceeding total pages
    $start_index = ($current_page - 1) * $records_per_page;
    $end_index = min($start_index + $records_per_page, $total_records);

    // Flatten records for pagination
    $all_records = [];
    foreach ($grouped_results as $examTypeID => $group) {
        foreach ($group['subjects'] as $subject) {
            $all_records[] = [
                'ExamTitle' => $group['Title'],
                'Subject' => $subject
            ];
        }
    }

    // Slice records for current page
    $paginated_records = array_slice($all_records, $start_index, $records_per_page);

    // Render table and modal
    ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Academic Performance</h2>
            <button id="viewAllResultsBtn" class="bg-primary text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition flex items-center">
                <i class="fas fa-eye mr-2"></i> View All
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Test</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Subject</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Date</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Obtained</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Total</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Percentage</th>
                        <th scope="col" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider leading-tight">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($paginated_records)) : ?>
                        <tr><td colspan="7" class="px-6 py-2 text-center text-sm text-gray-500 leading-tight">No records found for this page.</td></tr>
                    <?php else : ?>
                        <?php foreach ($paginated_records as $record) : ?>
                            <?php
                            $subject = $record['Subject'];
                            $percentage_value = $subject['Percentage'] === 'N/A' ? 0 : floatval(str_replace('%', '', $subject['Percentage']));
                            $arrow_class = $percentage_value >= 60 ? 'text-green-500' : 'text-red-500';
                            $arrow_icon = $percentage_value >= 60 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                            if ($subject['Percentage'] === 'N/A') $arrow_icon = '';
                            $status_classes = [
                                'Outstanding' => 'bg-purple-100 text-purple-800',
                                'Excellent' => 'bg-green-100 text-green-800',
                                'Very Good' => 'bg-blue-100 text-blue-800',
                                'Good' => 'bg-blue-100 text-blue-800',
                                'Average' => 'bg-yellow-100 text-yellow-800',
                                'Satisfactory' => 'bg-yellow-100 text-yellow-800',
                                'Low' => 'bg-red-100 text-red-800',
                                'Fail' => 'bg-red-100 text-red-800',
                                'Absent' => 'bg-gray-100 text-gray-800',
                                'N/A' => 'bg-gray-100 text-gray-800'
                            ];
                            $status_class = $status_classes[$subject['Status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <tr>
                                <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 leading-tight"><?php echo htmlspecialchars($record['ExamTitle']); ?></td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['SubjectName']); ?></td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['PaperDate']); ?></td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 leading-tight"><?php echo htmlspecialchars($subject['ObtainedMarks']); ?></td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['TotalMarks']); ?></td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 leading-tight">
                                    <p class="<?php echo $arrow_class; ?> text-sm flex items-center">
                                        <?php if ($arrow_icon) : ?><i class="<?php echo $arrow_icon; ?> mr-1"></i><?php endif; ?>
                                        <?php echo htmlspecialchars($subject['Percentage']); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap leading-tight">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($subject['Status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span class="font-medium"><?php echo $start_index + 1; ?></span> to <span class="font-medium"><?php echo min($start_index + count($paginated_records), $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
            </div>
            <div class="flex space-x-2">
                <button class="pagination-link px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded-md <?php echo $current_page == 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>" data-page="<?php echo $current_page - 1; ?>" <?php echo $current_page == 1 ? 'disabled' : ''; ?>>Previous</button>
                <button class="pagination-link px-3 py-1 text-sm bg-primary text-white rounded-md <?php echo $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>" data-page="<?php echo $current_page + 1; ?>" <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
            </div>
        </div>

        <!-- Modal for All Records -->
        <div id="allResultsModal" class="modal modal-hidden fixed inset-0 bg" style="background-color: rgba(0, 0, 0, 0.5);display: flex;align-items: center;justify-content: center;padding: 1rem;box-sizing: border-box;z-index: 50;">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800">All Academic Records</h3>
                        <button id="closeAllResultsModalBtn" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                               <tr>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Test</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Subject</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Date</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Obtained</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Total</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Percentage</th>
    <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-normal leading-tight">Status</th>
</tr>

                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($all_records)) : ?>
                                    <tr><td colspan="7" class="px-6 py-2 text-center text-sm text-gray-500 leading-tight">No records found.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($all_records as $record) : ?>
                                        <?php
                                        $subject = $record['Subject'];
                                        $percentage_value = $subject['Percentage'] === 'N/A' ? 0 : floatval(str_replace('%', '', $subject['Percentage']));
                                        $arrow_class = $percentage_value >= 60 ? 'text-green-500' : 'text-red-500';
                                        $arrow_icon = $percentage_value >= 60 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                                        if ($subject['Percentage'] === 'N/A') $arrow_icon = '';
                                        $status_class = $status_classes[$subject['Status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <tr>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm font-medium text-gray-900 leading-tight"><?php echo htmlspecialchars($record['ExamTitle']); ?></td>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['SubjectName']); ?></td>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['PaperDate']); ?></td>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm text-gray-900 leading-tight"><?php echo htmlspecialchars($subject['ObtainedMarks']); ?></td>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm text-gray-500 leading-tight"><?php echo htmlspecialchars($subject['TotalMarks']); ?></td>
                                            <td class="px-3 py-1 whitespace-nowrap text-sm text-gray-900 leading-tight">
                                                <p class="<?php echo $arrow_class; ?> text-sm flex items-center">
                                                    <?php if ($arrow_icon) : ?><i class="<?php echo $arrow_icon; ?> mr-1"></i><?php endif; ?>
                                                    <?php echo htmlspecialchars($subject['Percentage']); ?>
                                                </p>
                                            </td>
                                            <td class="px-3 py-1 whitespace-nowrap leading-tight">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($subject['Status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <button id="closeAllResultsModalBtn2" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-indigo-700 transition">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Academic Performance</h2>
            <button id="viewAllResultsBtn" class="bg-primary text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition flex items-center" disabled>
                <i class="fas fa-eye mr-2"></i> View All
            </button>
        </div>
        <div class="text-red-500 font-bold text-center mt-4 py-4">No exam results found for this student in the current academic year.</div>
        <!-- Modal for empty records -->
        <div id="allResultsModal" class="modal modal-hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800">All Academic Records</h3>
                        <button id="closeAllResultsModalBtn" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="text-gray-500 text-center py-4">No records available.</div>
                    <div class="pt-4 border-t border-gray-200">
                        <button id="closeAllResultsModalBtn2" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-indigo-700 transition">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
 </div>




                <!-- Fee Schedule Section -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Fee Payment Schedule</h2>                       
                </div>	
				
<?php

$sql_fee = "SELECT 
    ROW_NUMBER() OVER (ORDER BY FeeDueDate) AS SrNo,
    FeeType,
    FeeDueDate,
    LineTotal,
    IsPad,
    Entry_Date_Time,
    StudentFeeTypeID  -- Added to the outer SELECT
FROM (
    -- Part 1: Combine FeeTypeID = 217 (installments) with FeeTypeID = 216 (library fees)
    SELECT 
        fii.Title AS FeeType,
        inst.FeeDueDate,
        COALESCE(inst.LineTotal, 0) + COALESCE(lib.LineTotal, 0) AS LineTotal,
        COALESCE(inst.IsPad, lib.IsPad) AS IsPad,
        COALESCE(inst.Entry_Date_Time, lib.Entry_Date_Time) AS Entry_Date_Time,
        inst.StudentFeeTypeID  -- Added StudentFeeTypeID from inst
    FROM (
        -- Installment rows (FeeTypeID = 217)
        SELECT 
            csf.FeeDueDate,
            csf.LineTotal,
            CAST(csf.IsPad AS INT) AS IsPad,
            sfd.Entry_Date_Time,
            csf.FeeInstallmentNumber,
            csf.StudentFeeTypeID  -- Added StudentFeeTypeID
        FROM tbl1_04StudentFeeType AS csf
        LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
        WHERE csf.ClassStudentID = ?
        AND csf.FeeTypeID = 217
    ) AS inst
    LEFT JOIN (
        -- Library fee rows (FeeTypeID = 216) to be added to installments
        SELECT 
            csf.FeeDueDate,
            csf.LineTotal,
            CAST(csf.IsPad AS INT) AS IsPad,
            sfd.Entry_Date_Time,
            csf.FeeInstallmentNumber,
            csf.StudentFeeTypeID  -- Added StudentFeeTypeID
        FROM tbl1_04StudentFeeType AS csf
        LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
        WHERE csf.ClassStudentID = ?
        AND csf.FeeTypeID = 216
    ) AS lib ON inst.FeeInstallmentNumber = lib.FeeInstallmentNumber
    LEFT JOIN tbl0_11FeeInstallmentInfo AS fii ON inst.FeeInstallmentNumber = fii.FeeInstallmentID

    UNION ALL

    -- Part 2: Unmatched FeeTypeID = 216 rows (if any)
    SELECT 
        (SELECT fti.Title FROM tbl0_09FeeTypeInfo fti WHERE fti.FeeTypeID = 216) AS FeeType,
        lib.FeeDueDate,
        lib.LineTotal,
        lib.IsPad,
        lib.Entry_Date_Time,
        lib.StudentFeeTypeID  -- Added StudentFeeTypeID from lib
    FROM (
        SELECT 
            csf.FeeDueDate,
            csf.LineTotal,
            CAST(csf.IsPad AS INT) AS IsPad,
            sfd.Entry_Date_Time,
            csf.FeeInstallmentNumber,
            csf.ClassStudentID,
            csf.StudentFeeTypeID  -- Added StudentFeeTypeID
        FROM tbl1_04StudentFeeType AS csf
        LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
        WHERE csf.ClassStudentID = ?
        AND csf.FeeTypeID = 216
    ) AS lib
    WHERE NOT EXISTS (
        SELECT 1 
        FROM tbl1_04StudentFeeType inst 
        WHERE inst.ClassStudentID = lib.ClassStudentID 
        AND inst.FeeTypeID = 217 
        AND inst.FeeInstallmentNumber = lib.FeeInstallmentNumber
    )

    UNION ALL

    -- Part 3: All other FeeTypeID rows (not 216 or 217)
    SELECT 
        (SELECT fti.Title FROM tbl0_09FeeTypeInfo fti WHERE fti.FeeTypeID = csf.FeeTypeID) AS FeeType,
        csf.FeeDueDate,
        csf.LineTotal,
        CAST(csf.IsPad AS INT) AS IsPad,
        sfd.Entry_Date_Time,
        csf.StudentFeeTypeID  -- Added StudentFeeTypeID
    FROM tbl1_04StudentFeeType AS csf
    LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
    WHERE csf.ClassStudentID = ?
    AND csf.FeeTypeID NOT IN (216, 217)
) AS final
ORDER BY StudentFeeTypeID";
									
									
                        $params_fee = array($class_student_id, $class_student_id, $class_student_id, $class_student_id); // Pass ClassStudentID four times
                        $stmt_fee = sqlsrv_query($conn, $sql_fee, $params_fee);

                        if ($stmt_fee === false) {
                            echo '<div class="text-red-600 font-bold text-center mt-4">Error fetching fee details: ' . print_r(sqlsrv_errors(), true) . '</div>';
                        } else {
                            $fee_results = array();
                            while ($row = sqlsrv_fetch_array($stmt_fee, SQLSRV_FETCH_ASSOC)) {
                                // Convert LineTotal to float to handle smallmoney type
                                if (isset($row['LineTotal']) && is_resource($row['LineTotal'])) {
                                    $row['LineTotal'] = (float)sqlsrv_get_field($stmt_fee, array_search('LineTotal', array_keys($row)), SQLSRV_PHPTYPE_FLOAT);
                                } elseif (isset($row['LineTotal'])) {
                                    $row['LineTotal'] = (float)$row['LineTotal'];
                                }

                                // Convert FeeType to string if it's a resource (e.g., varchar stream)
                                if (isset($row['FeeType']) && is_resource($row['FeeType'])) {
                                    $row['FeeType'] = sqlsrv_get_field($stmt_fee, array_search('FeeType', array_keys($row)), SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
                                }

                                // Convert Entry_Date_Time to handle potential resource
                                if (isset($row['Entry_Date_Time']) && is_resource($row['Entry_Date_Time'])) {
                                    $row['Entry_Date_Time'] = sqlsrv_get_field($stmt_fee, array_search('Entry_Date_Time', array_keys($row)), SQLSRV_PHPTYPE_DATETIME);
                                }

                                $fee_results[] = $row;
                            }
                            sqlsrv_free_stmt($stmt_fee);

                            if (!empty($fee_results)) {
								

					echo '<div class="ml-2 w-full overflow-x-auto">';
                    echo ' <table class=" min-w-full divide-y divide-gray-200 table-auto">'; 
				
					echo '<thead class="bg-gray-50">';							
                                echo '<tr>';				
							
                                   echo ' <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>';
                                   echo ' <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>';
                                  echo '  <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>';
                                   echo ' <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
                                  echo '  <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Date</th>';
                                 echo '   <th scope="col" class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>';
                                    
                               echo ' </tr>';
                            echo '</thead>';
                            echo '<tbody class="bg-white divide-y divide-gray-200">';
								
								
								
								
                               
                                foreach ($fee_results as $fee) {
                                    $status = ($fee['IsPad'] == 1) ? 'Paid' : 'Payable';
                                    $pa_amount = ($status === 'Paid') ? '' : ($fee['LineTotal'] ?? 0);
									$style= ($fee['IsPad'] == 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                    $date_if_paid = ($status === 'Paid' && $fee['Entry_Date_Time']) ? $fee['Entry_Date_Time'] : null;
                                    $paid_amount = ($status !== 'Paid') ? '' : ($fee['LineTotal'] ?? 0);

                                    // Convert FeeType to lowercase and title case
                                    $fee_type = $fee['FeeType'] ?? 'N/A';
                                    $fee_type = strtolower($fee_type);
                                    $fee_type = ucwords($fee_type);

                           									
									echo '<tr>';
                                    echo '<td class="px-2 py-1 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($fee_type) . '</td>';
                                    echo '<td class="px-2 py-1 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($fee['FeeDueDate'] ? $fee['FeeDueDate']->format('d-M-Y') : 'N/A') . '</td>';
                                    echo '<td class="px-2 py-1 whitespace-nowrap text-sm text-gray-900">' . ($pa_amount !== '' ? htmlspecialchars(number_format($pa_amount, 0)) : '') . '</td>';
                                    echo '<td class="px-2 py-1 whitespace-nowrap">';
                                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full '.$style.'">' . htmlspecialchars($status) . '</span>';
                                    echo '</td>';
                                    echo '<td class="px-2 py-1 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($date_if_paid ? $date_if_paid->format('d-M-Y') : '') . '</td>';
                                    echo '<td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900">' . ($paid_amount !== '' ? htmlspecialchars(number_format($paid_amount, 0)) : '') . '</td>';
                                    echo '</tr>';
									

                                }
								echo '<tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"></td></tr>';
								
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                              
                            } else {
                                echo '<div class="no-results">No fee details found for this student. Debug Info: ClassStudentID = ' . htmlspecialchars($class_student_id) . '. Check if records exist in tbl1_04StudentFeeType.</div>';
                            }
                        }

            ?>


				
				
				</div>









 <!-- Subject-wise Performance -->
       <?php
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
            'English A' => ['icon' => 'fas fa-fa-language', 'text' => 'text-red-700', 'bg' => 'bg-red-200', 'bar' => 'bg-red-700'],
            'English B' => ['icon' => 'fas fa-fa-language', 'text' => 'text-red-500', 'bg' => 'bg-red-100', 'bar' => 'bg-red-500'],
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

        echo '<div class="bg-white rounded-xl shadow-md overflow-hidden mt-6">';
        echo '  <div class="border-b border-gray-200 px-6 py-4">';
        echo '      <h2 class="text-lg font-semibold text-gray-800">Subject-wise Performance</h2>';
        echo '  </div>';
        echo '  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6">';

        if ($stmt_subject_analysis === false) {
            echo '<div class="text-red-600 font-bold text-center col-span-full">';
            echo 'Error fetching subject-wise analysis: ' . htmlspecialchars(print_r(sqlsrv_errors(), true));
            echo '</div>';
        } else {
            $subject_analysis = [];
            while ($row = sqlsrv_fetch_array($stmt_subject_analysis, SQLSRV_FETCH_ASSOC)) {
                $subject_analysis[] = $row;
            }
            sqlsrv_free_stmt($stmt_subject_analysis);

            if (!empty($subject_analysis)) {
                foreach ($subject_analysis as $subject) {
                    $percentage = ($subject['TotalMarks'] > 0) 
                        ? number_format(($subject['TotalObtained'] / $subject['TotalMarks']) * 100, 0) 
                        : 0;
                    $last_test = ($subject['LastObtainedMarks'] !== null && $subject['LastTotalMarks'] !== null) 
                        ? number_format((float)$subject['LastObtainedMarks'], 0) . '/' . number_format((float)$subject['LastTotalMarks'], 0) 
                        : ($subject['LastObtainedMarks'] === null ? 'Absent' : 'N/A');

                    $subject_name = htmlspecialchars($subject['SubjectName']);
                    $style = $subject_styles[$subject_name] ?? $subject_styles['default'];

                    echo '<div class="border border-gray-200 rounded-lg p-4">';
                    echo '  <div class="flex justify-between items-start mb-2">';
                    echo '      <div>';
                    echo '          <h3 class="text-sm font-medium text-gray-500">' . $subject_name . '</h3>';
                    echo '          <p class="text-base font-bold text-gray-800">' . $percentage . '%</p>';
                    echo '      </div>';
                    echo '      <div class="' . $style['bg'] . ' p-2 rounded-lg">';
                    echo '          <i class="' . $style['icon'] . ' ' . $style['text'] . '"></i>';
                    echo '      </div>';
                    echo '  </div>';
                    echo '  <div class="w-full bg-gray-200 rounded-full h-2">';
                    echo '      <div class="' . $style['bar'] . ' h-2 rounded-full" style="width: ' . $percentage . '%"></div>';
                    echo '  </div>';
                    echo '  <div class="mt-2 text-xs text-gray-500">';
                    echo '      <span>Last Test: ' . htmlspecialchars($last_test) . '</span>';
                    echo '  </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="col-span-full text-center text-gray-500">No subject-wise performance data available.</div>';
            }
        }
        echo '  </div>';
        echo '</div>';
        ?>








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
            <div class="text-red-500 font-bold text-center my-4">No Student ID found. Please log in.</div>
        <?php } ?>
        <?php sqlsrv_close($conn); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Attendance Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [ <?php 
            foreach ($attendance as $monthNum => $row) {
                echo "'" . date('M', mktime(0, 0, 0, $monthNum, 1)) . "',";
            }
            ?>],
                datasets: [
                    {
                        label: 'Present',
                        data: [<?php 
                    foreach ($attendance as $row) {
                        echo $row['PresentDays'] . ",";
                    }
                    ?>],
                        backgroundColor: '#10B981',
                        borderRadius: 4
                    },
                    {
                        label: 'Absent',
                        data: [<?php 
                    foreach ($attendance as $row) {
                        echo $row['AbsentDays'] . ",";
                    }
                    ?>],
                        backgroundColor: '#EF4444',
                        borderRadius: 4
                    },
                    {
                        label: 'Leaves',
                        data: [<?php 
                    foreach ($attendance as $row) {
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

        // Animate progress rings and modal functionality
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

            // All Results Modal
            function attachModalListeners() {
                const allResultsModal = document.getElementById('allResultsModal');
                const viewAllResultsBtn = document.getElementById('viewAllResultsBtn');
                const closeAllResultsModalBtns = document.querySelectorAll('#closeAllResultsModalBtn, #closeAllResultsModalBtn2');

                if (viewAllResultsBtn && !viewAllResultsBtn.dataset.listenerAdded) {
                    viewAllResultsBtn.addEventListener('click', () => {
                        allResultsModal.classList.remove('modal-hidden');
                    });
                    viewAllResultsBtn.dataset.listenerAdded = 'true';
                }

                closeAllResultsModalBtns.forEach(btn => {
                    if (!btn.dataset.listenerAdded) {
                        btn.addEventListener('click', () => {
                            allResultsModal.classList.add('modal-hidden');
                        });
                        btn.dataset.listenerAdded = 'true';
                    }
                });

                if (allResultsModal && !allResultsModal.dataset.listenerAdded) {
                    allResultsModal.addEventListener('click', (e) => {
                        if (e.target === allResultsModal) {
                            allResultsModal.classList.add('modal-hidden');
                        }
                    });
                    allResultsModal.dataset.listenerAdded = 'true';
                }
            }

            // Initial modal listeners
            attachModalListeners();

            // AJAX Pagination for Academic Performance
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('pagination-link')) {
                    e.preventDefault();
                    const page = e.target.dataset.page;
                    fetchResults(page);
                }
            });

            function fetchResults(page) {
                const resultsTable = document.getElementById('results-table');
                const originalContent = resultsTable.innerHTML;
                resultsTable.innerHTML = '<div class="text-center py-4 text-gray-500">Loading...</div>';

                fetch(`fetch_results.php?page=${page}`, {
                    method: 'GET',
                    headers: { 'Content-Type': 'text/html' }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    if (html.trim() === '') {
                        console.error('Empty response from fetch_results.php?page=' + page);
                        resultsTable.innerHTML = '<div class="text-red-500 font-bold text-center py-4">No data received. Please try again.</div>';
                    } else {
                        resultsTable.innerHTML = html;
                        attachModalListeners(); // Re-attach modal listeners after AJAX update
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    resultsTable.innerHTML = originalContent;
                    resultsTable.insertAdjacentHTML('beforeend', '<div class="text-red-500 font-bold text-center py-4">Error loading results: ' + error.message + '</div>');
                });
            }
        });
    </script>
</body>
</html>