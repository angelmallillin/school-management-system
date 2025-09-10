<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'instructor') {
    header("Location: login.php");
    exit;
}

// Fetch instructor details to get their ID
$user_id = $_SESSION['user_id'];
$sql_instructor = "SELECT instructor_id, full_name FROM instructors WHERE user_id = ?";
$stmt = $conn->prepare($sql_instructor);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_instructor = $stmt->get_result();
$instructor_data = $result_instructor->fetch_assoc();
$instructor_id = $instructor_data['instructor_id'];
$instructor_name = $instructor_data['full_name'];
$stmt->close();

// Fetch subjects assigned to this instructor
$sql_subjects = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE assigned_instructor_id = ?";
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
    <title>My Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            position: relative;
            background-color: #F8F9FA;
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
            <h1 class="display-4">My Classes</h1>
        </div>
        
        <p class="lead">Welcome, <strong><?php echo htmlspecialchars($instructor_name); ?></strong>! Here are the subjects you are assigned to teach.</p>

        <div class="row g-4">
            <?php if ($result_subjects->num_rows > 0): ?>
                <?php while ($row = $result_subjects->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['subject_name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($row['subject_code']); ?></h6>
                                <a href="manage_grades.php?subject_id=<?php echo htmlspecialchars($row['subject_id']); ?>" class="btn btn-primary mt-auto">
                                    <i class="bi bi-check-lg me-1"></i>Manage Grades
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        You are not currently assigned to any subjects.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
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