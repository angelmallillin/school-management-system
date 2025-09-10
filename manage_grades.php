<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an admin or instructor
if (!isset($_SESSION['username']) || ($_SESSION['role'] != 'instructor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle grade submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $grade = $_POST['grade'];

    try {
        // Check if grade already exists for this student and subject
        $check_grade = $conn->prepare("SELECT grade_id FROM student_grades WHERE student_id = ? AND subject_id = ?");
        $check_grade->bind_param("ii", $student_id, $subject_id);
        $check_grade->execute();
        $check_grade->store_result();

        if ($check_grade->num_rows > 0) {
            // Update existing grade
            $stmt = $conn->prepare("UPDATE student_grades SET grade = ? WHERE student_id = ? AND subject_id = ?");
            $stmt->bind_param("dii", $grade, $student_id, $subject_id);
            if ($stmt->execute()) {
                $message = "Grade updated successfully!";
                $message_type = "success";
            } else {
                throw new Exception("Error updating grade: " . $stmt->error);
            }
        } else {
            // Insert new grade
            $stmt = $conn->prepare("INSERT INTO student_grades (student_id, subject_id, grade) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $student_id, $subject_id, $grade);
            if ($stmt->execute()) {
                $message = "Grade added successfully!";
                $message_type = "success";
            } else {
                throw new Exception("Error adding grade: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Fetch subjects for the current instructor (or all subjects for admin)
$subjects = [];
if ($_SESSION['role'] == 'instructor') {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT s.subject_id, s.subject_name, s.subject_code, i.full_name AS instructor_name
            FROM subjects s
            JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
            WHERE i.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
} else if ($_SESSION['role'] == 'admin') {
    $sql = "SELECT subject_id, subject_name, subject_code FROM subjects";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch students for a specific subject if selected
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
$students_with_grades = [];
if ($subject_id) {
    $sql = "SELECT st.student_id, st.student_name, sg.grade
            FROM student_subjects ss
            JOIN students st ON ss.student_id = st.student_id
            LEFT JOIN student_grades sg ON st.student_id = sg.student_id AND ss.subject_id = sg.subject_id
            WHERE ss.subject_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students_with_grades[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
       body {
            overflow-x: hidden;
            position: relative;
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
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <button class="btn btn-primary d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-4">Manage Grades</h1>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">Select Subject to Manage Grades</div>
                <div class="card-body">
                    <form method="GET" action="manage_grades.php">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select a Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" <?php echo ($subject_id == $subject['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Load Students</button>
                    </form>
                </div>
            </div>

            <?php if ($subject_id): ?>
            <div class="card">
                <div class="card-header">Students in Selected Subject</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="gradesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Current Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students_with_grades as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#gradeModal"
                                            data-student-id="<?php echo $student['student_id']; ?>"
                                            data-subject-id="<?php echo $subject_id; ?>"
                                            data-student-name="<?php echo htmlspecialchars($student['student_name']); ?>"
                                            data-grade="<?php echo htmlspecialchars($student['grade']); ?>">
                                            <i class="bi bi-pencil-square"></i> Edit Grade
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradeModalLabel">Update Grade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_grades.php?subject_id=<?php echo $subject_id; ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="modal_student_id" name="student_id">
                        <input type="hidden" id="modal_subject_id" name="subject_id" value="<?php echo $subject_id; ?>">
                        <div class="mb-3">
                            <label for="modal_student_name" class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="modal_student_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="modal_grade" class="form-label">Grade (0-100)</label>
                            <input type="number" step="0.01" class="form-control" id="modal_grade" name="grade" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="submit_grade">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#gradesTable').DataTable();

            // Handle edit button click to populate modal
            $('#gradesTable').on('click', '.edit-btn', function() {
                var studentId = $(this).data('student-id');
                var subjectId = $(this).data('subject-id');
                var studentName = $(this).data('student-name');
                var grade = $(this).data('grade');

                $('#modal_student_id').val(studentId);
                $('#modal_subject_id').val(subjectId);
                $('#modal_student_name').val(studentName);
                $('#modal_grade').val(grade);
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