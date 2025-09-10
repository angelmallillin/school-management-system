<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Handle POST requests for CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add instructor
    if (isset($_POST['add_instructor'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $department = $_POST['department'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if username already exists
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $check_user->store_result();
            if ($check_user->num_rows > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
            $check_user->close();

            // Insert into users table
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'instructor')");
            $stmt_user->bind_param("ss", $username, $password);
            $stmt_user->execute();
            $user_id = $stmt_user->insert_id;

            // Insert into instructors table
            $stmt_instructor = $conn->prepare("INSERT INTO instructors (user_id, full_name, email, contact_number, department) VALUES (?, ?, ?, ?, ?)");
            $stmt_instructor->bind_param("issss", $user_id, $full_name, $email, $contact_number, $department);
            $stmt_instructor->execute();

            $conn->commit();
            $message = "New instructor added successfully!";
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }

    // Update instructor
    if (isset($_POST['update_instructor'])) {
        $instructor_id = $_POST['edit_instructor_id'];
        $full_name = $_POST['edit_full_name'];
        $email = $_POST['edit_email'];
        $contact_number = $_POST['edit_contact_number'];
        $department = $_POST['edit_department'];

        try {
            $stmt = $conn->prepare("UPDATE instructors SET full_name=?, email=?, contact_number=?, department=? WHERE instructor_id=?");
            $stmt->bind_param("ssssi", $full_name, $email, $contact_number, $department, $instructor_id);
            $stmt->execute();
            $message = "Instructor updated successfully!";
            $message_type = 'success';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }

    // Delete instructor
    if (isset($_POST['delete_instructor'])) {
        $instructor_id = $_POST['delete_instructor_id'];
        $user_id = $_POST['delete_user_id'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Delete from instructors table
            $stmt_instructor = $conn->prepare("DELETE FROM instructors WHERE instructor_id = ?");
            $stmt_instructor->bind_param("i", $instructor_id);
            $stmt_instructor->execute();

            // Delete from users table
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();

            $conn->commit();
            $message = "Instructor deleted successfully!";
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all instructors
$sql = "SELECT i.*, u.username FROM instructors i JOIN users u ON i.user_id = u.id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Instructors</title>
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
            /* Remove width: 100% to let it flex naturally */
        }
        .collapsed #sidebar {
            transform: translateX(-250px);
        }
        .collapsed #main-content {
            margin-left: 0;
        }
        .btn-add-instructor {
            background-color: #EC4899;
            border-color: #EC4899;
            transition: background-color 0.2s ease;
            font-weight: 500;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add-instructor:hover {
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
        .form-control {
            border-radius: 10px;
            border: 1px solid #FBCFE8; /* Soft pink border */
            padding: 10px 15px;
        }
        .form-control:focus {
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
                <h1 class="display-5 fw-bold mb-1">Manage Instructors</h1>
                <p class="text-muted">Add, edit, or remove instructor records.</p>
            </div>
            <button class="btn btn-add-instructor text-white" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                <i class="bi bi-person-plus-fill me-2"></i>Add New Instructor
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="instructorsTable" class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Department</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['instructor_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info text-white edit-btn" data-bs-toggle="modal" data-bs-target="#editInstructorModal"
                                    data-id="<?php echo htmlspecialchars($row['instructor_id']); ?>"
                                    data-full_name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-contact_number="<?php echo htmlspecialchars($row['contact_number']); ?>"
                                    data-department="<?php echo htmlspecialchars($row['department']); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteInstructorModal"
                                    data-id="<?php echo htmlspecialchars($row['instructor_id']); ?>"
                                    data-user_id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Instructor Modal -->
    <div class="modal fade" id="addInstructorModal" tabindex="-1" aria-labelledby="addInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInstructorModalLabel">Add New Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_instructors.php" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_instructor" class="btn btn-primary">Add Instructor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Instructor Modal -->
    <div class="modal fade" id="editInstructorModal" tabindex="-1" aria-labelledby="editInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editInstructorModalLabel">Edit Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_instructors.php" method="post">
                        <input type="hidden" id="edit_instructor_id" name="edit_instructor_id">
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_full_name" name="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact_number" name="edit_contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="edit_department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="edit_department" name="edit_department" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_instructor" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Instructor Modal -->
    <div class="modal fade" id="deleteInstructorModal" tabindex="-1" aria-labelledby="deleteInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteInstructorModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this instructor record?</p>
                    <form action="manage_instructors.php" method="post">
                        <input type="hidden" id="delete_instructor_id" name="delete_instructor_id">
                        <input type="hidden" id="delete_user_id" name="delete_user_id">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_instructor" class="btn btn-danger">Delete</button>
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
            // Initialize DataTables with responsive option
            $('#instructorsTable').DataTable({
                "lengthChange": false,
                "searching": true,
                "info": false,
                "dom": '<"top"f>rt<"bottom"p><"clear">',
                "responsive": true // Ito ang nag-a-adjust ng table para magkasya sa screen
            });

            // Handle edit button click
            $('#instructorsTable').on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                var full_name = $(this).data('full_name');
                var email = $(this).data('email');
                var contact_number = $(this).data('contact_number');
                var department = $(this).data('department');
                
                $('#edit_instructor_id').val(id);
                $('#edit_full_name').val(full_name);
                $('#edit_email').val(email);
                $('#edit_contact_number').val(contact_number);
                $('#edit_department').val(department);
                
                var editModal = new bootstrap.Modal(document.getElementById('editInstructorModal'));
                editModal.show();
            });

            // Handle delete button click
            $('#instructorsTable').on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                var user_id = $(this).data('user_id');
                
                $('#delete_instructor_id').val(id);
                $('#delete_user_id').val(user_id);
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteInstructorModal'));
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
