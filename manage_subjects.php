<?php
session_start();
include 'db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle Add Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $units = $_POST['units'];
    $assigned_instructor_id = $_POST['assigned_instructor_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, units, assigned_instructor_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $subject_code, $subject_name, $units, $assigned_instructor_id);
        $stmt->execute();
        $message = "New subject added successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Update Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_subject'])) {
    $subject_id = $_POST['edit_subject_id'];
    $subject_code = $_POST['edit_subject_code'];
    $subject_name = $_POST['edit_subject_name'];
    $units = $_POST['edit_units'];
    $assigned_instructor_id = $_POST['edit_assigned_instructor_id'];

    try {
        $stmt = $conn->prepare("UPDATE subjects SET subject_code=?, subject_name=?, units=?, assigned_instructor_id=? WHERE subject_id=?");
        $stmt->bind_param("ssiii", $subject_code, $subject_name, $units, $assigned_instructor_id, $subject_id);
        $stmt->execute();
        $message = "Subject updated successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Delete Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['delete_subject_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $message = "Subject deleted successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all subjects with instructor names
$sql = "SELECT s.*, i.full_name AS instructor_name FROM subjects s LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id";
$result = $conn->query($sql);

// Fetch all instructors for the dropdown list
$sql_instructors = "SELECT instructor_id, full_name FROM instructors ORDER BY full_name";
$instructors_result = $conn->query($sql_instructors);
$instructors = [];
while ($row = $instructors_result->fetch_assoc()) {
    $instructors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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
        .btn-add-subject {
            background-color: #EC4899;
            border-color: #EC4899;
            transition: background-color 0.2s ease;
            font-weight: 500;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add-subject:hover {
            background-color: #DB2777;
            border-color: #DB2777;
        }
        .modal-header {
            background-color: #F9A8D4; /* Soft pink header */
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            border-bottom: none;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .modal-footer .btn-primary {
            background-color: #EC4899;
            border-color: #EC4899;
            border-radius: 50px;
        }
        .modal-footer .btn-primary:hover {
            background-color: #DB2777;
            border-color: #DB2777;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        table.dataTable thead tr {
            background-color: #F9A8D4; /* Soft header color */
        }
        table.dataTable thead th {
            color: #4A5568;
            font-weight: 600;
        }
        table.dataTable tbody tr:nth-child(even) {
            background-color: #FEEBF7; /* Lighter stripe */
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #FBCFE8; /* Soft pink border */
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #EC4899;
            box-shadow: 0 0 0 0.25rem rgba(236, 72, 153, 0.25);
        }
        .btn-sm.btn-info {
            background-color: #93C5FD;
            border-color: #93C5FD;
            color: white;
            border-radius: 8px;
        }
        .btn-sm.btn-danger {
            background-color: #F87171;
            border-color: #F87171;
            color: white;
            border-radius: 8px;
        }
        .dataTables_filter .form-control {
            border-radius: 50px;
            padding: 8px 20px;
        }
        .page-item.active .page-link {
            background-color: #F9A8D4;
            border-color: #F9A8D4;
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
                <h1 class="display-5 fw-bold mb-1">Manage Subjects</h1>
                <p class="text-muted">Add, edit, or remove subjects and assign instructors.</p>
            </div>
            <button class="btn btn-add-subject text-white" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="bi bi-journal-plus me-2"></i>Add New Subject
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="subjectsTable" class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Assigned Instructor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['subject_code']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['units']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['instructor_name'] ?? 'Not Assigned') . "</td>";
                            echo "<td>";
                            echo "<button class='btn btn-sm btn-info text-white edit-btn' data-bs-toggle='modal' data-bs-target='#editSubjectModal' data-id='" . htmlspecialchars($row['subject_id']) . "' data-code='" . htmlspecialchars($row['subject_code']) . "' data-name='" . htmlspecialchars($row['subject_name']) . "' data-units='" . htmlspecialchars($row['units']) . "' data-instructor='" . htmlspecialchars($row['assigned_instructor_id']) . "'><i class='bi bi-pencil'></i></button>";
                            echo "<button class='btn btn-sm btn-danger ms-1 delete-btn' data-bs-toggle='modal' data-bs-target='#deleteSubjectModal' data-id='" . htmlspecialchars($row['subject_id']) . "'><i class='bi bi-trash'></i></button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No subjects found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_subjects.php" method="post">
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="units" name="units" required>
                        </div>
                        <div class="mb-3">
                            <label for="assigned_instructor_id" class="form-label">Assign Instructor</label>
                            <select class="form-select" id="assigned_instructor_id" name="assigned_instructor_id">
                                <option value="">No Instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['instructor_id']; ?>"><?php echo htmlspecialchars($instructor['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_subjects.php" method="post">
                        <input type="hidden" id="edit_subject_id" name="edit_subject_id">
                        <div class="mb-3">
                            <label for="edit_subject_code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="edit_subject_code" name="edit_subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_subject_name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="edit_subject_name" name="edit_subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_units" class="form-label">Units</label>
                            <input type="number" class="form-control" id="edit_units" name="edit_units" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_assigned_instructor_id" class="form-label">Assign Instructor</label>
                            <select class="form-select" id="edit_assigned_instructor_id" name="edit_assigned_instructor_id">
                                <option value="">No Instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['instructor_id']; ?>"><?php echo htmlspecialchars($instructor['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_subject" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this subject?</p>
                    <form action="manage_subjects.php" method="post">
                        <input type="hidden" id="delete_subject_id" name="delete_subject_id">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_subject" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
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
            $('#subjectsTable').DataTable({
                "lengthChange": false,
                "searching": true,
                "info": false,
                "dom": '<"top"f>rt<"bottom"p><"clear">',
                "responsive": true
            });

            // Handle edit button click
            $('#subjectsTable').on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                var code = $(this).data('code');
                var name = $(this).data('name');
                var units = $(this).data('units');
                var instructor = $(this).data('instructor');

                $('#edit_subject_id').val(id);
                $('#edit_subject_code').val(code);
                $('#edit_subject_name').val(name);
                $('#edit_units').val(units);
                $('#edit_assigned_instructor_id').val(instructor);

                var editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                editModal.show();
            });

            // Handle delete button click
            $('#subjectsTable').on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                $('#delete_subject_id').val(id);
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
                deleteModal.show();
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