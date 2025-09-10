<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

// Fetch student details from the database
$user_id = $_SESSION['user_id'];
$sql_student = "SELECT * FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_student = $stmt->get_result();
$student_data = $result_student->fetch_assoc();
$student_id = $student_data['student_id'];

// Fetch enrolled subjects for the student, but only those without a final grade
$sql_subjects = "SELECT s.subject_code, s.subject_name, s.units, i.full_name AS instructor_name, ss.grade
                 FROM student_subjects ss
                 JOIN subjects s ON ss.subject_id = s.subject_id
                 LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
                 WHERE ss.student_id = ? AND ss.grade IS NULL";
$stmt_subjects = $conn->prepare($sql_subjects);
$stmt_subjects->bind_param("i", $student_id);
$stmt_subjects->execute();
$result_subjects = $stmt_subjects->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
       body { overflow-x: hidden; position: relative; }
       #sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 250px; transform: translateX(0); transition: transform 0.3s ease-in-out; z-index: 1000; }
       #main-content { margin-left: 250px; padding: 2rem; transition: margin-left 0.3s ease-in-out; width: 100%; }
       .collapsed #sidebar { transform: translateX(-250px); }
       .collapsed #main-content { margin-left: 0; }
       .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .list-group-item {
            border-radius: 10px;
            margin-bottom: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .list-group-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="">
    <?php include "sidebar.php"; ?>
    <button id="sidebarToggle" class="btn btn-primary m-2 d-lg-none">
    ☰
    </button>
    <div id="main-content">
        <h1 class="mb-4">Student Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($student_data['student_name']); ?>!</p>
        
        <div class="row g-4 mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Student Information</div>
                    <div class="card-body">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student_data['email']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($student_data['course']); ?></p>
                        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student_data['year_level']); ?></p>
                        <p><strong>Section:</strong> <?php echo htmlspecialchars($student_data['section']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">My Enrolled Subjects</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if ($result_subjects->num_rows > 0): ?>
                                <?php while($row = $result_subjects->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong> (<?php echo htmlspecialchars($row['subject_code']); ?>)
                                            <br>
                                            <small class="text-muted">Instructor: <?php echo htmlspecialchars($row['instructor_name'] ? $row['instructor_name'] : 'N/A'); ?></small>
                                        </div>
                                        <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($row['units']); ?> units</span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="list-group-item">No subjects enrolled.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
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