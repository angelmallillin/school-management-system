<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit;
}

// Fetch instructor details
$user_id = $_SESSION['user_id'];
$sql_instructor = "SELECT * FROM instructors WHERE user_id = ?";
$stmt = $conn->prepare($sql_instructor);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_instructor = $stmt->get_result();
$instructor_data = $result_instructor->fetch_assoc();
$instructor_id = $instructor_data['instructor_id'];

// Fetch subjects assigned to this instructor
$sql_subjects = "SELECT subject_code, subject_name, units FROM subjects WHERE assigned_instructor_id = ?";
$stmt_subjects = $conn->prepare($sql_subjects);
$stmt_subjects->bind_param("i", $instructor_id);
$stmt_subjects->execute();
$result_subjects = $stmt_subjects->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
       body { overflow-x: hidden; position: relative; }
       #sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 250px; transform: translateX(0); transition: transform 0.3s ease-in-out; z-index: 1000; }
       #main-content { margin-left: 250px; padding: 2rem; transition: margin-left 0.3s ease-in-out; width: 100%; }
       .collapsed #sidebar { transform: translateX(-250px); }
       .collapsed #main-content { margin-left: 0; }
    </style>
</head>
<body class="">
    <?php include "sidebar.php"; ?>
    <button id="sidebarToggle" class="btn btn-primary m-2 d-lg-none">
    ☰
    </button>
    <div id="main-content">
        <h1 class="mb-4">Instructor Dashboard</h1>
        <p>Welcome, <?php echo $instructor_data['full_name']; ?>!</p>
        
        <div class="row g-4 mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Instructor Information</div>
                    <div class="card-body">
                        <p><strong>Email:</strong> <?php echo $instructor_data['email']; ?></p>
                        <p><strong>Department:</strong> <?php echo $instructor_data['department']; ?></p>
                        <p><strong>Contact:</strong> <?php echo $instructor_data['contact_number']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">My Assigned Subjects</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if ($result_subjects->num_rows > 0): ?>
                                <?php while($row = $result_subjects->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo $row['subject_name']; ?></strong> (<?php echo $row['subject_code']; ?>)
                                        </div>
                                        <span class="badge bg-secondary rounded-pill"><?php echo $row['units']; ?> units</span>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="list-group-item">No subjects assigned.</li>
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