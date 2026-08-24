<?php

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('books/index.php');
}

$id   = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, isbn, title FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'The book you are trying to delete could not be found.');
    redirect('books/index.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM borrow_records WHERE book_id = :bid AND return_date IS NULL');
$stmt->execute([':bid' => $id]);
$activeCount = (int)$stmt->fetchColumn();

if ($activeCount > 0) {
    flash('error', 'Deletion failed: "' . $book['title'] . '" (' . $book['isbn'] . ') has ' . $activeCount
        . ' active borrowing record' . ($activeCount === 1 ? '' : 's') . '. All copies must be returned before this book can be deleted.');
    redirect('books/index.php');
}

$stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);

flash('success', 'Book "' . $book['title'] . '" (' . $book['isbn'] . ') was deleted successfully.');
redirect('books/index.php');
