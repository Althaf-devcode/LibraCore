<?php

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('borrow/index.php');
}

$id   = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT br.id, br.due_date, br.return_date, b.title
    FROM borrow_records br
    JOIN books b ON b.id = br.book_id
    WHERE br.id = :id
");
$stmt->execute([':id' => $id]);
$record = $stmt->fetch();

if (!$record) {
    flash('error', 'The borrow record you are trying to return could not be found.');
    redirect('borrow/index.php');
}

if ($record['return_date'] !== null) {
    flash('warning', '"' . $record['title'] . '" has already been returned. A record cannot be returned twice.');
    redirect('borrow/index.php');
}

$today = db_today();
$lateDays = (int)((strtotime($today) - strtotime($record['due_date'])) / 86400);

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare('UPDATE borrow_records SET return_date = CURDATE(), status = :status WHERE id = :id AND return_date IS NULL');
    $update->execute([':status' => 'Returned', ':id' => $id]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException('already_returned');
    }

    $restock = $pdo->prepare('UPDATE books SET available_quantity = LEAST(quantity, available_quantity + 1) WHERE id = (SELECT book_id FROM borrow_records WHERE id = :id)');
    $restock->execute([':id' => $id]);

    if ($restock->rowCount() !== 1) {
        throw new RuntimeException('restock_failed');
    }

    $pdo->commit();

    if ($lateDays > 0) {
        flash('warning', '"' . $record['title'] . '" was returned ' . $lateDays . ' day'
            . ($lateDays === 1 ? '' : 's') . ' after the due date. The return has been recorded.');
    } else {
        flash('success', '"' . $record['title'] . '" was returned on time. One copy is now back on the shelf.');
    }
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Could not record the return. The record may have already been processed. Please refresh and try again.');
}

redirect('borrow/index.php');
