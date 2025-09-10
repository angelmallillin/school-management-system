<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add Prerequisite
    if (isset($_POST['add_prerequisite'])) {
        $subject_id = $_POST['subject_id'];
        $prerequisite_subject_id = $_POST['prerequisite_subject_id'];

        // Check if a subject can be its own prerequisite
        if ($subject_id == $prerequisite_subject_id) {
            $message = "A subject cannot be its own prerequisite.";
            $message_type = 'danger';
        } else {
            // First, check if the entry already exists
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM subject_prerequisites WHERE subject_id = ? AND prerequisite_subject_id = ?");
            $check_stmt->bind_param("ii", $subject_id, $prerequisite_subject_id);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $message = "This prerequisite already exists. No new entry was added.";
                $message_type = 'warning';
            } else {
                // If it doesn't exist, proceed with insertion
                try {
                    $stmt = $conn->prepare("INSERT INTO subject_prerequisites (subject_id, prerequisite_subject_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $subject_id, $prerequisite_subject_id);
                    if ($stmt->execute()) {
                        $message = "Prerequisite added successfully!";
                        $message_type = 'success';
                    } else {
                        throw new Exception("Error adding prerequisite.");
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }

    // Delete Prerequisite
    if (isset($_POST['delete_prerequisite'])) {
        $prerequisite_id = $_POST['prerequisite_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM subject_prerequisites WHERE prerequisite_id = ?");
            $stmt->bind_param("i", $prerequisite_id);
            if ($stmt->execute()) {
                $message = "Prerequisite deleted successfully!";
                $message_type = 'success';
            } else {
                throw new Exception("Error deleting prerequisite.");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Fetch all subjects for dropdowns
$sql_subjects = "SELECT subject_id, subject_code, subject_name FROM subjects ORDER BY subject_name";
$result_subjects = $conn->query($sql_subjects);
$subjects = [];
if ($result_subjects->num_rows > 0) {
    while ($row = $result_subjects->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch all pre-requisite relationships
$sql_prerequisites = "SELECT sp.prerequisite_id, s.subject_code, s.subject_name, p.subject_code AS prerequisite_code, p.subject_name AS prerequisite_name
                      FROM subject_prerequisites sp
                      JOIN subjects s ON sp.subject_id = s.subject_id
                      JOIN subjects p ON sp.prerequisite_subject_id = p.subject_id
                      ORDER BY s.subject_name, p.subject_name";
$result_prerequisites = $conn->query($sql_prerequisites);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pre-requisites</title>
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
        .btn-add {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 500;
            border-radius: 50px;
            padding: 10px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .form-select {
            border-radius: 5px;
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
                <h1 class="display-5 fw-bold mb-1">Manage Pre-requisites</h1>
                <p class="text-muted">Configure which subjects are required for others.</p>
            </div>
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addPrerequisiteModal">
                <i class="bi bi-plus-circle me-2"></i>Add Prerequisite
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
                <table id="prerequisitesTable" class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Requires</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_prerequisites->num_rows > 0): ?>
                            <?php while ($row = $result_prerequisites->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_name']) . " (" . htmlspecialchars($row['subject_code']) . ")"; ?></td>
                                    <td><?php echo htmlspecialchars($row['prerequisite_name']) . " (" . htmlspecialchars($row['prerequisite_code']) . ")"; ?></td>
                                    <td>
                                        <form action="manage_prerequisites.php" method="post" onsubmit="return confirm('Are you sure you want to delete this prerequisite?');" class="d-inline-block">
                                            <input type="hidden" name="prerequisite_id" value="<?php echo $row['prerequisite_id']; ?>">
                                            <button type="submit" name="delete_prerequisite" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No pre-requisites configured yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addPrerequisiteModal" tabindex="-1" aria-labelledby="addPrerequisiteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPrerequisiteModalLabel">Add New Prerequisite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_prerequisites.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"><?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="prerequisite_subject_id" class="form-label">Pre-requisite Subject</label>
                            <select class="form-select" id="prerequisite_subject_id" name="prerequisite_subject_id" required>
                                <option value="">Select Pre-requisite Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"><?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_prerequisite" class="btn btn-primary">Add Prerequisite</button>
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
            $('#prerequisitesTable').DataTable({
                "lengthChange": false,
                "searching": true,
                "info": false,
                "dom": '<"top"f>rt<"bottom"p><"clear">',
                "responsive": true
            });

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