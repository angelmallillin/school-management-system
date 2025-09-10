<?php
// This is a helper script to fetch a student's assigned subjects for the edit modal
session_start();
include 'db_connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin' || !isset($_GET['student_id'])) {
    http_response_code(403);
    exit;
}

$student_id = $_GET['student_id'];
$assigned_subjects = [];

$stmt = $conn->prepare("SELECT subject_id FROM student_subjects WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assigned_subjects[] = $row['subject_id'];
}

header('Content-Type: application/json');
echo json_encode($assigned_subjects);

$stmt->close();
$conn->close();
?>
