<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Borrow Book';
$active     = 'borrow';

$students = $pdo->query('SELECT id, student_id, name FROM students ORDER BY name')->fetchAll();
$books    = $pdo->query('SELECT id, isbn, title, author, category, shelf_location, available_quantity FROM books WHERE available_quantity > 0 ORDER BY title')->fetchAll();

$errors = [];
$selectedStudent = (int)($_POST['student_id'] ?? $_GET['student_id'] ?? 0);
$selectedBook    = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
$borrowDate      = $_POST['borrow_date'] ?? date('Y-m-d');
$dueDate         = $_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
$remarks         = trim($_POST['remarks'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare('SELECT id, student_id, name FROM students WHERE id = :id');
    $stmt->execute([':id' => $selectedStudent]);
    $student = $stmt->fetch();
    if (!$student) {
        $errors['student_id'] = 'Please select a valid student.';
    }

    $book = null;
    if ($selectedBook > 0) {
        $stmt = $pdo->prepare('SELECT id, isbn, title, author, category, shelf_location, quantity, available_quantity FROM books WHERE id = :id');
        $stmt->execute([':id' => $selectedBook]);
        $book = $stmt->fetch();
    }
    if (!$book) {
        $errors['book_id'] = 'Please select a valid book.';
    }

    if (!valid_date($borrowDate)) {
        $errors['borrow_date'] = 'Please provide a valid borrow date.';
    }

    if (!valid_date($dueDate)) {
        $errors['due_date'] = 'Please provide a valid due date.';
    } elseif (!$errors && strcmp($dueDate, $borrowDate) < 0) {
        $errors['due_date'] = 'The due date cannot be earlier than the borrow date.';
    }

    if (mb_strlen($remarks) > 255) {
        $errors['remarks'] = 'Remarks must be 255 characters or fewer.';
    }

    if (!$errors && $book && (int)$book['available_quantity'] <= 0) {
        $errors['book_id'] = '"' . $book['title'] . '" has no available copies right now.';
    }

    if (!$errors && $book && $student) {
        try {
            $pdo->beginTransaction();

            $insert = $pdo->prepare('
                INSERT INTO borrow_records (student_id, book_id, borrow_date, due_date, status, remarks, created_at)
                VALUES (:student_id, :book_id, :borrow_date, :due_date, :status, :remarks, NOW())
            ');
            $insert->execute([
                ':student_id' => $selectedStudent,
                ':book_id'    => $selectedBook,
                ':borrow_date'=> $borrowDate,
                ':due_date'   => $dueDate,
                ':status'     => 'Borrowed',
                ':remarks'    => $remarks !== '' ? $remarks : null,
            ]);

            $update = $pdo->prepare('UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :id AND available_quantity > 0');
            $update->execute([':id' => $selectedBook]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('availability');
            }

            $pdo->commit();

            flash('success', '"' . $book['title'] . '" was issued to ' . $student['name'] . '. Due date: '
                . date('M j, Y', strtotime($dueDate)) . '.');
            redirect('borrow/index.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['book_id'] = 'Could not complete the borrow operation. The book may have run out of copies. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';

$selectedBookRow = null;
if ($selectedBook > 0) {
    foreach ($books as $b) {
        if ((int)$b['id'] === $selectedBook) {
            $selectedBookRow = $b;
            break;
        }
    }
}
?>

<div class="mx-auto max-w-3xl">

    <div class="mb-6">
        <a href="<?= url('borrow/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
            <?= icon('back', 'h-4 w-4') ?> Back to Borrow Records
        </a>
        <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Issue a Book</h2>
        <p class="mt-1 text-sm text-slate-500">Record a new borrowing transaction for a student.</p>
    </div>

    <?php if (!$books): ?>
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800" role="note">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>No books are currently available for borrowing. Please add stock to the catalogue or process pending returns first.</span>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>Please review the errors below and try again.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>" novalidate data-validate
          class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">

        <div class="space-y-5">

            <div>
                <label for="student_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Student <span class="text-red-500">*</span></label>
                <select id="student_id" name="student_id"
                        class="<?= input_class($errors, 'student_id') ?>">
                    <option value="">Select student</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= $selectedStudent === (int)$s['id'] ? 'selected' : '' ?>>
                            [<?= e($s['student_id']) ?>] <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'student_id') ?>
            </div>

            <div>
                <label for="book_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Book (available copies only) <span class="text-red-500">*</span></label>
                <select id="book_id" name="book_id"
                        class="<?= input_class($errors, 'book_id') ?>">
                    <option value="" data-title="">Select book</option>
                    <?php foreach ($books as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"
                                data-title="<?= e($b['title']) ?>"
                                data-author="<?= e($b['author']) ?>"
                                data-category="<?= e($b['category']) ?>"
                                data-shelf="<?= e($b['shelf_location']) ?>"
                                data-available="<?= (int)$b['available_quantity'] ?>"
                                <?= $selectedBook === (int)$b['id'] ? 'selected' : '' ?>>
                            [<?= e($b['isbn']) ?>] <?= e($b['title']) ?> &mdash; <?= (int)$b['available_quantity'] ?> available
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'book_id') ?>
            </div>

            <div id="book-info-panel" class="hidden rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-blue-500">Selected Book</p>
                <p id="bi-title" class="mt-1 text-sm font-bold text-blue-950">&nbsp;</p>
                <div class="mt-1 grid grid-cols-1 gap-x-6 gap-y-0.5 text-xs text-blue-800 sm:grid-cols-3">
                    <p>Author: <span id="bi-author" class="font-semibold"></span></p>
                    <p>Category: <span id="bi-category" class="font-semibold"></span></p>
                    <p><span id="bi-available" class="font-bold text-emerald-700"></span></p>
                    <p id="bi-shelf" class="sm:col-span-3"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="borrow_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Borrow Date <span class="text-red-500">*</span></label>
                    <input type="date" id="borrow_date" name="borrow_date" value="<?= e($borrowDate) ?>" required
                           class="<?= input_class($errors, 'borrow_date') ?>">
                    <?= field_error($errors, 'borrow_date') ?>
                </div>
                <div>
                    <label for="due_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" id="due_date" name="due_date" value="<?= e($dueDate) ?>" required
                           class="<?= input_class($errors, 'due_date') ?>">
                    <?= field_error($errors, 'due_date') ?>
                    <p class="mt-1.5 text-xs text-slate-400">Standard loan period is 14 days.</p>
                </div>
            </div>

            <div>
                <label for="remarks" class="mb-1.5 block text-sm font-semibold text-slate-700">Notes / Remarks</label>
                <textarea id="remarks" name="remarks" rows="3" placeholder="Optional notes about this borrowing..."
                          class="<?= input_class($errors, 'remarks') ?> resize-y"><?= old_value('remarks', $remarks) ?></textarea>
                <?= field_error($errors, 'remarks') ?>
            </div>
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="<?= url('borrow/index.php') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                Cancel
            </a>
            <button type="submit" <?= !$books ? 'disabled' : '' ?>
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition enabled:hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 active:scale-[.99] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                <?= icon('swap', 'h-4 w-4') ?> Confirm Borrow
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
