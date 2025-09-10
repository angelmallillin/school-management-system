<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle student enrollment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_students'])) {
    $subject_id = $_POST['subject_id'];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    if (empty($subject_id) || empty($student_ids)) {
        $message = "Please select a subject and at least one student.";
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        $enrolled_count = 0;
        $failed_enrollments = [];

        try {
            // Fetch subject prerequisites once before the loop
            $sql_prerequisites = "SELECT prerequisite_subject_id FROM subject_prerequisites WHERE subject_id = ?";
            $stmt_prerequisites = $conn->prepare($sql_prerequisites);
            $stmt_prerequisites->bind_param("i", $subject_id);
            $stmt_prerequisites->execute();
            $result_prerequisites = $stmt_prerequisites->get_result();
            $prereq_ids = [];
            while ($row = $result_prerequisites->fetch_assoc()) {
                $prereq_ids[] = $row['prerequisite_subject_id'];
            }
            $stmt_prerequisites->close();

            foreach ($student_ids as $student_id) {
                $can_enroll = true;
                $missing_prereqs = [];

                // Check prerequisites if they exist
                if (!empty($prereq_ids)) {
                    foreach ($prereq_ids as $prereq_id) {
                        // Check if the student has passed the prerequisite with a grade >= 75
                        $sql_check_grade = "SELECT ss.grade
                                            FROM student_subjects ss
                                            WHERE ss.student_id = ? AND ss.subject_id = ? AND ss.grade >= 75";
                        $stmt_check_grade = $conn->prepare($sql_check_grade);
                        $stmt_check_grade->bind_param("ii", $student_id, $prereq_id);
                        $stmt_check_grade->execute();
                        $result_check_grade = $stmt_check_grade->get_result();
                        
                        if ($result_check_grade->num_rows == 0) {
                            $can_enroll = false;
                            // Fetch the name of the missing prerequisite subject
                            $sql_get_prereq_name = "SELECT subject_code, subject_name FROM subjects WHERE subject_id = ?";
                            $stmt_get_prereq_name = $conn->prepare($sql_get_prereq_name);
                            $stmt_get_prereq_name->bind_param("i", $prereq_id);
                            $stmt_get_prereq_name->execute();
                            $prereq_data = $stmt_get_prereq_name->get_result()->fetch_assoc();
                            $missing_prereqs[] = htmlspecialchars($prereq_data['subject_code'] . ' - ' . $prereq_data['subject_name']);
                        }
                        $stmt_check_grade->close();
                    }
                }
                
                if ($can_enroll) {
                    // Check if the student is already enrolled to prevent duplicates
                    $check_enrollment = $conn->prepare("SELECT * FROM student_subjects WHERE student_id = ? AND subject_id = ?");
                    $check_enrollment->bind_param("ii", $student_id, $subject_id);
                    $check_enrollment->execute();
                    $result_enrollment = $check_enrollment->get_result();

                    if ($result_enrollment->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
                        $stmt->bind_param("ii", $student_id, $subject_id);
                        $stmt->execute();
                        $enrolled_count++;
                    }
                } else {
                    // Get student name for the error message
                    $sql_get_student_name = "SELECT student_name FROM students WHERE student_id = ?";
                    $stmt_get_student_name = $conn->prepare($sql_get_student_name);
                    $stmt_get_student_name->bind_param("i", $student_id);
                    $stmt_get_student_name->execute();
                    $student_data = $stmt_get_student_name->get_result()->fetch_assoc();
                    $student_name = $student_data['student_name'];
                    
                    $prereq_list = implode(', ', $missing_prereqs);
                    $failed_enrollments[] = "$student_name: Not all required pre-requisites passed ($prereq_list).";
                }
            }

            $conn->commit();
            
            if ($enrolled_count > 0) {
                $message .= "Successfully enrolled $enrolled_count student(s)!";
                $message_type = 'success';
            }
            
            if (!empty($failed_enrollments)) {
                $message .= ($enrolled_count > 0) ? "<br><br>" : "";
                $message .= "Enrollment failed for the following student(s):<br>" . implode("<br>", $failed_enrollments);
                $message_type = ($enrolled_count > 0) ? 'warning' : 'danger';
            }

            if ($enrolled_count == 0 && empty($failed_enrollments)) {
                $message = "All selected students are already enrolled in this subject.";
                $message_type = 'warning';
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error enrolling students: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all subjects for the dropdown
$subjects_query = "SELECT subject_id, subject_code, subject_name FROM subjects";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch all students for the table
$students_query = "SELECT student_id, student_name, course, year_level FROM students ORDER BY student_name";
$students_result = $conn->query($students_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            position: relative;
            background-color: #FCE7F3; /* Light pink background */
            font-family: 'Poppins', sans-serif;
        }
        #sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        #main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease-in-out;
        }
        .collapsed #sidebar {
            transform: translateX(-250px);
        }
        .collapsed #main-content {
            margin-left: 0;
        }
        .btn-submit {
            background-color: #EC4899;
            border-color: #EC4899;
            transition: background-color 0.2s ease;
            font-weight: 500;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-submit:hover {
            background-color: #DB2777;
            border-color: #DB2777;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        table.dataTable thead tr {
            background-color: #F9A8D4;
        }
        table.dataTable thead th {
            color: #4A5568;
            font-weight: 600;
        }
        table.dataTable tbody tr:nth-child(even) {
            background-color: #FEEBF7;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #FBCFE8;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #EC4899;
            box-shadow: 0 0 0 0.25rem rgba(236, 72, 153, 0.25);
        }
    </style>
</head>
<body class="">
    <?php include "sidebar.php"; ?>
    <div id="main-content">
        <button class="btn btn-primary d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="display-5 fw-bold mb-1">Enroll Students</h1>
                <p class="text-muted">Enroll students in a specific subject.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <form action="enroll_students.php" method="post">
                <div class="mb-4">
                    <label for="subject_id" class="form-label fw-bold">Select Subject</label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        <option value="">Choose...</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h3 class="h5 fw-bold mb-3">Available Students</h3>
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" value="<?php echo $row['student_id']; ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="enroll_students" class="btn btn-submit text-white">
                        <i class="bi bi-check-lg me-2"></i>Enroll Selected Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#studentsTable').DataTable({
                "lengthChange": false,
                "searching": true,
                "info": false,
                "dom": '<"top"f>rt<"bottom"p><"clear">',
                "responsive": true
            });

            // Select/Deselect all students
            $('#selectAll').on('click', function() {
                var isChecked = $(this).prop('checked');
                $('input[name="student_ids[]"]').prop('checked', isChecked);
            });
            
            // This is the JavaScript for the sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebarToggle');

            if(sidebar && mainContent && sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('collapsed');
                });
            }
        });
    </script>
</body>
</html>