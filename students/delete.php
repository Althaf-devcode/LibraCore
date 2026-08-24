<?php

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('students/index.php');
}

$id   = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, student_id, name FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    flash('error', 'The student you are trying to delete could not be found.');
    redirect('students/index.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM borrow_records WHERE student_id = :sid AND return_date IS NULL');
$stmt->execute([':sid' => $id]);
$activeCount = (int)$stmt->fetchColumn();

if ($activeCount > 0) {
    flash('error', 'Deletion failed: "' . $student['name'] . '" (' . $student['student_id'] . ') currently has ' . $activeCount
        . ' actively borrowed book' . ($activeCount === 1 ? '' : 's') . '. All books must be returned before this student can be deleted.');
    redirect('students/index.php');
}

$stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);

flash('success', 'Student "' . $student['name'] . '" (' . $student['student_id'] . ') was deleted successfully.');
redirect('students/index.php');
