<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

// Fetch student details to get their ID
$user_id = $_SESSION['user_id'];
$sql_student = "SELECT student_id, student_name FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_student = $stmt->get_result();
$student_data = $result_student->fetch_assoc();
$student_id = $student_data['student_id'];
$student_name = $student_data['student_name'];
$stmt->close();

// Fetch grades for the logged-in student, including subject details
$sql_grades = "SELECT s.subject_code, s.subject_name, sg.grade
               FROM student_grades sg
               JOIN subjects s ON sg.subject_id = s.subject_id
               WHERE sg.student_id = ?";
$stmt_grades = $conn->prepare($sql_grades);
$stmt_grades->bind_param("i", $student_id);
$stmt_grades->execute();
$result_grades = $stmt_grades->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
       body {
            overflow-x: hidden;
            position: relative;
            background-color: #F8F9FA; /* Light gray background */
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
            width: 100%;
        }
        .collapsed #sidebar {
            transform: translateX(-250px);
        }
        .collapsed #main-content {
            margin-left: 0;
        }
        .grade-pass {
            color: #198754; /* Green for passing grades */
            font-weight: bold;
        }
        .grade-fail {
            color: #DC3545; /* Red for failing grades */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include "sidebar.php"; ?>
    <div id="main-content">
        <button class="btn btn-primary d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-4">My Grades</h1>
        </div>
        
        <p class="lead">Welcome, <strong><?php echo htmlspecialchars($student_name); ?></strong>! Here are your grades.</p>

        <div class="card">
            <div class="card-header">Academic Performance</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_grades->num_rows > 0): ?>
                                <?php while ($row = $result_grades->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($row['grade'], 2)); ?></td>
                                    <td>
                                        <?php
                                        // Assume passing grade is 75 or above
                                        $status_class = ($row['grade'] >= 75) ? 'grade-pass' : 'grade-fail';
                                        $status_text = ($row['grade'] >= 75) ? 'Passed' : 'Failed';
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No grades have been recorded for you yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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