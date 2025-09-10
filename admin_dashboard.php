<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch counts for dashboard overview
$sql_students = "SELECT COUNT(*) AS student_count FROM students";
$sql_instructors = "SELECT COUNT(*) AS instructor_count FROM instructors";
$sql_subjects = "SELECT COUNT(*) AS subject_count FROM subjects";

$result_students = $conn->query($sql_students);
$result_instructors = $conn->query($sql_instructors);
$result_subjects = $conn->query($sql_subjects);

$student_count = $result_students->fetch_assoc()['student_count'];
$instructor_count = $result_instructors->fetch_assoc()['instructor_count'];
$subject_count = $result_subjects->fetch_assoc()['subject_count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            position: relative;
            background-color: #F8F9FA; /* Light gray background */
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
            transition: transform 0.2s;
            overflow: hidden;
            border: none;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-body.dashboard-card {
            padding: 2rem;
        }
        .card-body h5 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .card-body p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }
        .card-icon {
            font-size: 3rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0.3;
        }
        .bg-primary-dark {
            background-color: #0d6efd;
        }
        .bg-info-dark {
            background-color: #0dcaf0;
        }
        .bg-success-dark {
            background-color: #198754;
        }
    </style>
</head>
<body class="">
    <?php include "sidebar.php"; ?>
    <div id="main-content">
        <button class="btn btn-primary d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-4 fw-bold mb-1">Admin Dashboard</h1>
                <p class="text-muted lead">Overview of your system at a glance.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary-dark">
                    <div class="card-body dashboard-card position-relative">
                        <i class="bi bi-person-badge card-icon"></i>
                        <h5 class="card-title"><?php echo $student_count; ?></h5>
                        <p class="card-text">Total Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info-dark">
                    <div class="card-body dashboard-card position-relative">
                        <i class="bi bi-person-workspace card-icon"></i>
                        <h5 class="card-title"><?php echo $instructor_count; ?></h5>
                        <p class="card-text">Total Instructors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success-dark">
                    <div class="card-body dashboard-card position-relative">
                        <i class="bi bi-book card-icon"></i>
                        <h5 class="card-title"><?php echo $subject_count; ?></h5>
                        <p class="card-text">Total Subjects</p>
                    </div>
                </div>
            </div>
        </div>

        
   
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // This is the JavaScript for the sidebar toggle functionality
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