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

// Fetch subjects the student is enrolled in
$sql_courses = "SELECT s.subject_code, s.subject_name, i.full_name AS instructor_name
                FROM student_subjects ss
                JOIN subjects s ON ss.subject_id = s.subject_id
                LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
                WHERE ss.student_id = ?";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
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
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            <h1 class="display-4">My Courses</h1>
        </div>
        
        <p class="lead">Welcome, <strong><?php echo htmlspecialchars($student_name); ?></strong>! Here are the courses you are currently enrolled in.</p>

        <div class="row g-4">
            <?php if ($result_courses->num_rows > 0): ?>
                <?php while ($row = $result_courses->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['subject_name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($row['subject_code']); ?></h6>
                                <p class="card-text mt-3">
                                    <i class="bi bi-person-badge-fill me-1"></i> Instructor: <strong><?php echo htmlspecialchars($row['instructor_name'] ?? 'Not Assigned'); ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        You are not currently enrolled in any courses.
                    </div>
                </div>
            <?php endif; ?>
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