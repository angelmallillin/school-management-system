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

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];
    $section = $_POST['section']; // Added section
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Start a transaction
        $conn->begin_transaction();

        // Check if username already exists
        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        $check_user->store_result();

        if ($check_user->num_rows > 0) {
            throw new Exception("Username already exists. Please choose a different username.");
        }

        // Insert into users table
        $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        $stmt_user->bind_param("ss", $username, $password);
        $stmt_user->execute();
        $user_id = $stmt_user->insert_id;
        $stmt_user->close();

        // Insert into students table
        $stmt_student = $conn->prepare("INSERT INTO students (user_id, student_id, student_name, dob, gender, contact_number, address, email, course, year_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_student->bind_param("iisssssssis", $user_id, $student_id, $student_name, $dob, $gender, $contact_number, $address, $email, $course, $year_level, $section); // Added section
        $stmt_student->execute();
        $stmt_student->close();

        $conn->commit();
        $message = "New student added successfully!";
        $message_type = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Update Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id_to_update = $_POST['edit_student_id'];
    $student_name = $_POST['edit_student_name'];
    $dob = $_POST['edit_dob'];
    $gender = $_POST['edit_gender'];
    $contact_number = $_POST['edit_contact_number'];
    $address = $_POST['edit_address'];
    $email = $_POST['edit_email'];
    $course = $_POST['edit_course'];
    $year_level = $_POST['edit_year_level'];
    $section = $_POST['edit_section']; // Added section

    try {
        $stmt = $conn->prepare("UPDATE students SET student_name = ?, dob = ?, gender = ?, contact_number = ?, address = ?, email = ?, course = ?, year_level = ?, section = ? WHERE student_id = ?");
        $stmt->bind_param("sssssssssi", $student_name, $dob, $gender, $contact_number, $address, $email, $course, $year_level, $section, $student_id_to_update); // Added section
        $stmt->execute();
        $message = "Student information updated successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id_to_delete = $_POST['delete_student_id'];
    $user_id_to_delete = $_POST['delete_user_id'];
    
    $conn->begin_transaction();
    
    try {
        // Delete from students table
        $stmt_student = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt_student->bind_param("i", $student_id_to_delete);
        $stmt_student->execute();
        $stmt_student->close();
        
        // Delete from users table
        $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id_to_delete);
        $stmt_user->execute();
        $stmt_user->close();
        
        $conn->commit();
        $message = "Student deleted successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all students
$sql_students = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.student_name";
$result_students = $conn->query($sql_students);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            position: relative;
            background-color: #FCE7F3;
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
        .btn-add {
            background-color: #EC4899;
            border-color: #EC4899;
            transition: background-color 0.2s ease;
            font-weight: 500;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add:hover {
            background-color: #EC4899;
            border-color: #EC4899;
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
            <div>
                <h1 class="display-5 fw-bold mb-1">Manage Students</h1>
                <p class="text-muted">Add, edit, or delete student information.</p>
            </div>
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="bi bi-plus-lg me-2"></i>Add New Student
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <div class="table-responsive">
                <table id="studentsTable" class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Section</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                                <td><?php echo htmlspecialchars($row['section']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?php echo htmlspecialchars($row['student_id']); ?>"
                                            data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                            data-dob="<?php echo htmlspecialchars($row['dob']); ?>"
                                            data-gender="<?php echo htmlspecialchars($row['gender']); ?>"
                                            data-contact="<?php echo htmlspecialchars($row['contact_number']); ?>"
                                            data-address="<?php echo htmlspecialchars($row['address']); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                            data-course="<?php echo htmlspecialchars($row['course']); ?>"
                                            data-year_level="<?php echo htmlspecialchars($row['year_level']); ?>"
                                            data-section="<?php echo htmlspecialchars($row['section']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn"
                                            data-id="<?php echo htmlspecialchars($row['student_id']); ?>"
                                            data-user-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_students.php" method="post">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="student_name" name="student_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="course" name="course" required>
                        </div>
                        <div class="mb-3">
                            <label for="year_level" class="form-label">Year Level</label>
                            <input type="text" class="form-control" id="year_level" name="year_level" required>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <input type="text" class="form-control" id="section" name="section">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_student" class="btn btn-add text-white">Add Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="manage_students.php" method="post">
                        <input type="hidden" id="edit_student_id" name="edit_student_id">
                        <div class="mb-3">
                            <label for="edit_student_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_student_name" name="edit_student_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="edit_dob" name="edit_dob" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_gender" class="form-label">Gender</label>
                            <select class="form-select" id="edit_gender" name="edit_gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact_number" name="edit_contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="edit_address" name="edit_address">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="edit_course" name="edit_course" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_year_level" class="form-label">Year Level</label>
                            <input type="text" class="form-control" id="edit_year_level" name="edit_year_level" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_section" class="form-label">Section</label>
                            <input type="text" class="form-control" id="edit_section" name="edit_section">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_student" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStudentModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this student?</p>
                </div>
                <div class="modal-footer">
                    <form action="manage_students.php" method="post">
                        <input type="hidden" id="delete_student_id" name="delete_student_id">
                        <input type="hidden" id="delete_user_id" name="delete_user_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_student" class="btn btn-danger">Delete</button>
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
            $('#studentsTable').DataTable();

            // Handle edit button click to populate modal
            $('#studentsTable').on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var dob = $(this).data('dob');
                var gender = $(this).data('gender');
                var contact = $(this).data('contact');
                var address = $(this).data('address');
                var email = $(this).data('email');
                var course = $(this).data('course');
                var year_level = $(this).data('year_level');
                var section = $(this).data('section');

                $('#edit_student_id').val(id);
                $('#edit_student_name').val(name);
                $('#edit_dob').val(dob);
                $('#edit_gender').val(gender);
                $('#edit_contact_number').val(contact);
                $('#edit_address').val(address);
                $('#edit_email').val(email);
                $('#edit_course').val(course);
                $('#edit_year_level').val(year_level);
                $('#edit_section').val(section);
                
                var editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
                editModal.show();
            });

            // Handle delete button click
            $('#studentsTable').on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                var user_id = $(this).data('user-id');
                
                $('#delete_student_id').val(id);
                $('#delete_user_id').val(user_id);
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
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