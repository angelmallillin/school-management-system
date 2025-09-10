<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode([]);
    exit;
}

$student_id = intval($_GET['student_id']);
$available_subjects = [];

// Get all subjects
$sql_all_subjects = "SELECT subject_id, subject_code, subject_name FROM subjects";
$result_all_subjects = $conn->query($sql_all_subjects);
$all_subjects = [];
while ($row = $result_all_subjects->fetch_assoc()) {
    $all_subjects[$row['subject_id']] = $row;
}

// Get subjects the student is already enrolled in
$sql_enrolled = "SELECT subject_id FROM student_subjects WHERE student_id = ?";
$stmt_enrolled = $conn->prepare($sql_enrolled);
$stmt_enrolled->bind_param("i", $student_id);
$stmt_enrolled->execute();
$result_enrolled = $stmt_enrolled->get_result();
$enrolled_subjects = [];
while ($row = $result_enrolled->fetch_assoc()) {
    $enrolled_subjects[] = $row['subject_id'];
}
$stmt_enrolled->close();

// Filter out enrolled subjects
$subjects_to_check = array_diff(array_keys($all_subjects), $enrolled_subjects);

// Check prerequisites for remaining subjects
foreach ($subjects_to_check as $subject_id) {
    // Check for prerequisites for this subject
    $sql_prereq = "SELECT prerequisite_subject_id FROM subject_prerequisites WHERE subject_id = ?";
    $stmt_prereq = $conn->prepare($sql_prereq);
    $stmt_prereq->bind_param("i", $subject_id);
    $stmt_prereq->execute();
    $result_prereq = $stmt_prereq->get_result();
    
    $prereq_met = true;
    if ($result_prereq->num_rows > 0) {
        // If there are prerequisites, check if the student passed them
        while ($prereq_row = $result_prereq->fetch_assoc()) {
            $prereq_subject_id = $prereq_row['prerequisite_subject_id'];
            
            $sql_grade = "SELECT grade FROM student_subjects WHERE student_id = ? AND subject_id = ? AND grade >= 75";
            $stmt_grade = $conn->prepare($sql_grade);
            $stmt_grade->bind_param("ii", $student_id, $prereq_subject_id);
            $stmt_grade->execute();
            $result_grade = $stmt_grade->get_result();
            
            if ($result_grade->num_rows == 0) {
                $prereq_met = false;
                break;
            }
        }
    }
    $stmt_prereq->close();
    
    // Add subject to the list if all prerequisites are met
    if ($prereq_met) {
        $available_subjects[] = $all_subjects[$subject_id];
    }
}

echo json_encode($available_subjects);
$conn->close();
?>