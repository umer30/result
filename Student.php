
<?php
class Student {
    private $conn;
    private $student_id;

    public function __construct($connection, $student_id) {
        $this->conn = $connection;
        $this->student_id = $student_id;
    }

    public function getStudentInfo() {
        // Validate connection first
        if (!$this->conn || $this->conn === false) {
            throw new Exception('Database connection is not available');
        }

        // Validate student ID
        if (!$this->student_id || !is_numeric($this->student_id)) {
            throw new Exception('Invalid student ID provided');
        }

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
				
	
				
				
				

        $params = array($this->student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = "Database query failed";
            if (is_array($errors) && !empty($errors)) {
                $error_msg .= ": " . $errors[0]['message'];
            }
            throw new Exception($error_msg);
        }

        $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$student) {
            throw new Exception('Student record not found for ID: ' . $this->student_id);
        }

        return $student;
    }

    

    public function getAttendanceData() {
        $sql = "WITH Deduplicated AS (
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

        $params = array($this->student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception('Error fetching attendance data: ' . print_r(sqlsrv_errors(), true));
        }

        $attendance = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $attendance[$row['Month']] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $attendance;
    }

    public function getLatestClassStudentId() {
        // Validate connection and student ID
        if (!$this->conn || $this->conn === false) {
            throw new Exception('Database connection is not available');
        }

        if (!$this->student_id || !is_numeric($this->student_id)) {
            throw new Exception('Invalid student ID provided');
        }

        $sql = "SELECT TOP 1 ClassStudentID
                FROM tbl1_05ClassStudent
                WHERE StudentID = ?
                ORDER BY ClassStudentID DESC";
        $params = array($this->student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = "Failed to fetch class student ID";
            if (is_array($errors) && !empty($errors)) {
                $error_msg .= ": " . $errors[0]['message'];
            }
            throw new Exception($error_msg);
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        return $row ? $row['ClassStudentID'] : null;
    }

    public function getExamResults($class_student_id, $page = 1, $per_page = 10) {
        $sql = "SELECT 
                    et.Title AS ExamType,
                    em.ExamDate,
                    esd.ObtainedMarks,
                    esd.TotalMarks,
                    s.Title AS SubjectName,
                    e.ExamDetailID,
                    et.ExamTypeID,
                    CASE 
                        WHEN esd.TotalMarks > 0 THEN 
                            CAST(ROUND((CAST(esd.ObtainedMarks AS FLOAT) / CAST(esd.TotalMarks AS FLOAT)) * 100, 2) AS INT)
                        ELSE 0 
                    END AS Percentage
                 FROM tbl1_10ExamStudentDetail e
                 JOIN tbl1_05ClassStudent cs ON e.ClassStudentID = cs.ClassStudentID
                 JOIN tbl1_09ExamMaster em ON e.ExamID = em.ExamID
                 JOIN tbl0_10ExamTypeInfo et ON em.ExamTypeID = et.ExamTypeID
                 JOIN tbl1_11ExamSubjectsDetail esd ON e.ExamDetailID = esd.ExamDetailID
                 JOIN tbl0_08SubjectInfo s ON esd.SubjectID = s.SubjectID
                 WHERE cs.ClassStudentID = ?
                 ORDER BY em.ExamDate DESC, s.Title ASC";

        $params = array($class_student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception('Error fetching exam results: ' . print_r(sqlsrv_errors(), true));
        }

        $exam_results = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Format date properly
            if (isset($row['ExamDate']) && is_object($row['ExamDate'])) {
                $row['ExamDate'] = $row['ExamDate']->format('d-M-Y');
            }

            // Calculate status based on your logic
            if ($row['ObtainedMarks'] !== null && $row['TotalMarks'] > 0) {
                $percent = ($row['ObtainedMarks'] / $row['TotalMarks']) * 100;
                if ($percent >= 90) $row['Status'] = 'Outstanding';
                elseif ($percent >= 80) $row['Status'] = 'Excellent';
                elseif ($percent >= 70) $row['Status'] = 'Very Good';
                elseif ($percent >= 60) $row['Status'] = 'Good';
                elseif ($percent >= 50) $row['Status'] = 'Average';
                elseif ($percent >= 40) $row['Status'] = 'Satisfactory';
                elseif ($percent >= 33) $row['Status'] = 'Low';
                else $row['Status'] = 'Fail';
            } elseif ($row['ObtainedMarks'] === null) {
                $row['Status'] = 'Absent';
            } else {
                $row['Status'] = 'N/A';
            }

            // Ensure integer values
            $row['ObtainedMarks'] = $row['ObtainedMarks'] !== null ? (int)$row['ObtainedMarks'] : null;
            $row['TotalMarks'] = (int)$row['TotalMarks'];
            $row['Percentage'] = (int)$row['Percentage'];

            $exam_results[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $exam_results;
    }

    public function getSubjectWiseResults($class_student_id) {
        $sql = "SELECT 
                    si.Title AS SubjectName,
                    esd.ObtainedMarks,
                    esd.TotalMarks,
                    CASE 
                        WHEN esd.TotalMarks > 0 THEN 
                            CAST(ROUND((CAST(esd.ObtainedMarks AS FLOAT) / CAST(esd.TotalMarks AS FLOAT)) * 100, 2) AS INT)
                        ELSE 0 
                    END AS Percentage,
                    CASE 
                        WHEN esd.TotalMarks > 0 AND 
                             (CAST(esd.ObtainedMarks AS FLOAT) / CAST(esd.TotalMarks AS FLOAT)) * 100 >= 33 
                        THEN 'Pass' 
                        ELSE 'Fail' 
                    END AS Status,
                    et.Title AS ExamType,
                    em.ExamDate
                FROM tbl1_11ExamStudentSubjectDetail esd
                JOIN tbl1_10ExamStudentDetail esd_main ON esd.ExamDetailID = esd_main.ExamDetailID
                JOIN tbl1_05ClassStudent cs ON esd_main.ClassStudentID = cs.ClassStudentID
                JOIN tbl1_09ExamMaster em ON esd_main.ExamID = em.ExamID
                JOIN tbl0_10ExamTypeInfo et ON em.ExamTypeID = et.ExamTypeID
                JOIN tbl0_04SubjectInfo si ON esd.SubjectID = si.SubjectID
                WHERE cs.ClassStudentID = ?
                ORDER BY em.ExamDate DESC, si.Title ASC";

        $params = array($class_student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception('Error fetching subject-wise results: ' . print_r(sqlsrv_errors(), true));
        }

        $subject_results = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Format date properly
            if (isset($row['ExamDate']) && is_object($row['ExamDate'])) {
                $row['ExamDate'] = $row['ExamDate']->format('d-M-Y');
            }

            // Ensure integer values
            $row['ObtainedMarks'] = (int)$row['ObtainedMarks'];
            $row['TotalMarks'] = (int)$row['TotalMarks'];
            $row['Percentage'] = (int)$row['Percentage'];

            $subject_results[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $subject_results;
    }

    public function getSubjectWisePerformance($class_student_id) {
        $sql = "SELECT 
                    si.Title AS SubjectName,
                    SUM(esd.ObtainedMarks) AS TotalObtained,
                    SUM(esd.TotalMarks) AS TotalMarks,
                    COUNT(*) AS TestsCount,
                    AVG(CASE 
                        WHEN esd.TotalMarks > 0 THEN 
                            (CAST(esd.ObtainedMarks AS FLOAT) / CAST(esd.TotalMarks AS FLOAT)) * 100
                        ELSE 0 
                    END) AS AveragePercentage,
                    MAX(esd.ObtainedMarks) AS LastObtainedMarks,
                    MAX(esd.TotalMarks) AS LastTotalMarks
                FROM tbl1_11ExamSubjectsDetail esd
                JOIN tbl1_10ExamStudentDetail esd_main ON esd.ExamDetailID = esd_main.ExamDetailID
                JOIN tbl1_05ClassStudent cs ON esd_main.ClassStudentID = cs.ClassStudentID
                JOIN tbl1_09ExamMaster em ON esd_main.ExamID = em.ExamID
                JOIN tbl0_10ExamTypeInfo et ON em.ExamTypeID = et.ExamTypeID
                JOIN tbl0_08SubjectInfo si ON esd.SubjectID = si.SubjectID
                WHERE cs.ClassStudentID = ?
                GROUP BY si.Title
                ORDER BY si.Title ASC";

        $params = array($class_student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception('Error fetching subject-wise performance: ' . print_r(sqlsrv_errors(), true));
        }

        $subject_performance = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $subject_performance[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $subject_performance;
    }

    public function getFeeDetails($class_student_id) {
        $sql = "SELECT 
            ROW_NUMBER() OVER (ORDER BY FeeDueDate) AS SrNo,
            FeeType,
            FeeDueDate,
            LineTotal,
            IsPad,
            Entry_Date_Time,
            StudentFeeTypeID
        FROM (
            SELECT 
                fii.Title AS FeeType,
                inst.FeeDueDate,
                COALESCE(inst.LineTotal, 0) + COALESCE(lib.LineTotal, 0) AS LineTotal,
                COALESCE(inst.IsPad, lib.IsPad) AS IsPad,
                COALESCE(inst.Entry_Date_Time, lib.Entry_Date_Time) AS Entry_Date_Time,
                inst.StudentFeeTypeID
            FROM (
                SELECT 
                    csf.FeeDueDate,
                    csf.LineTotal,
                    CAST(csf.IsPad AS INT) AS IsPad,
                    sfd.Entry_Date_Time,
                    csf.FeeInstallmentNumber,
                    csf.StudentFeeTypeID
                FROM tbl1_04StudentFeeType AS csf
                LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
                WHERE csf.ClassStudentID = ?
                AND csf.FeeTypeID = 217
            ) AS inst
            LEFT JOIN (
                SELECT 
                    csf.FeeDueDate,
                    csf.LineTotal,
                    CAST(csf.IsPad AS INT) AS IsPad,
                    sfd.Entry_Date_Time,
                    csf.FeeInstallmentNumber,
                    csf.StudentFeeTypeID
                FROM tbl1_04StudentFeeType AS csf
                LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
                WHERE csf.ClassStudentID = ?
                AND csf.FeeTypeID = 216
            ) AS lib ON inst.FeeInstallmentNumber = lib.FeeInstallmentNumber
            LEFT JOIN tbl0_11FeeInstallmentInfo AS fii ON inst.FeeInstallmentNumber = fii.FeeInstallmentID

            UNION ALL

            SELECT 
                (SELECT fti.Title FROM tbl0_09FeeTypeInfo fti WHERE fti.FeeTypeID = 216) AS FeeType,
                lib.FeeDueDate,
                lib.LineTotal,
                lib.IsPad,
                lib.Entry_Date_Time,
                lib.StudentFeeTypeID
            FROM (
                SELECT 
                    csf.FeeDueDate,
                    csf.LineTotal,
                    CAST(csf.IsPad AS INT) AS IsPad,
                    sfd.Entry_Date_Time,
                    csf.FeeInstallmentNumber,
                    csf.ClassStudentID,
                    csf.StudentFeeTypeID
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

            SELECT 
                (SELECT fti.Title FROM tbl0_09FeeTypeInfo fti WHERE fti.FeeTypeID = csf.FeeTypeID) AS FeeType,
                csf.FeeDueDate,
                csf.LineTotal,
                CAST(csf.IsPad AS INT) AS IsPad,
                sfd.Entry_Date_Time,
                csf.StudentFeeTypeID
            FROM tbl1_04StudentFeeType AS csf
            LEFT JOIN tbl1_08StudentFeeDetail AS sfd ON csf.StudentFeeTypeID = sfd.StudentFeeTypeID
            WHERE csf.ClassStudentID = ?
            AND csf.FeeTypeID NOT IN (216, 217)
        ) AS final
        ORDER BY StudentFeeTypeID";

        $params = array($class_student_id, $class_student_id, $class_student_id, $class_student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception('Error fetching fee details: ' . print_r(sqlsrv_errors(), true));
        }

        $fee_results = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Handle resource types
            if (isset($row['LineTotal']) && is_resource($row['LineTotal'])) {
                $row['LineTotal'] = (float)sqlsrv_get_field($stmt, array_search('LineTotal', array_keys($row)), SQLSRV_PHPTYPE_FLOAT);
            } elseif (isset($row['LineTotal'])) {
                $row['LineTotal'] = (float)$row['LineTotal'];
            }

            if (isset($row['FeeType']) && is_resource($row['FeeType'])) {
                $row['FeeType'] = sqlsrv_get_field($stmt, array_search('FeeType', array_keys($row)), SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }

            if (isset($row['Entry_Date_Time']) && is_resource($row['Entry_Date_Time'])) {
                $row['Entry_Date_Time'] = sqlsrv_get_field($stmt, array_search('Entry_Date_Time', array_keys($row)), SQLSRV_PHPTYPE_DATETIME);
            }

            $fee_results[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $fee_results;
    }

    public function getSubjectWiseAnalysis($class_student_id) {
        // Validate connection and class student ID
        if (!$this->conn || $this->conn === false) {
            throw new Exception('Database connection is not available');
        }

        if (!$class_student_id || !is_numeric($class_student_id)) {
            throw new Exception('Invalid class student ID provided');
        }

        $sql = "SELECT 
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

        $params = array($class_student_id, $class_student_id);
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error_msg = "Failed to fetch subject-wise analysis";
            if (is_array($errors) && !empty($errors)) {
                $error_msg .= ": " . $errors[0]['message'];
            }
            throw new Exception($error_msg);
        }

        $subject_analysis = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $subject_analysis[] = $row;
        }
        sqlsrv_free_stmt($stmt);

        return $subject_analysis;
    }
}
?>
