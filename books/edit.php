<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Edit Book';
$active     = 'books';

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'The requested book could not be found.');
    redirect('books/index.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM borrow_records WHERE book_id = :bid AND return_date IS NULL');
$stmt->execute([':bid' => $id]);
$activeBorrowed = (int)$stmt->fetchColumn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'isbn'             => trim($_POST['isbn'] ?? ''),
        'title'            => trim($_POST['title'] ?? ''),
        'author'           => trim($_POST['author'] ?? ''),
        'category'         => trim($_POST['category'] ?? ''),
        'publisher'        => trim($_POST['publisher'] ?? ''),
        'publication_year' => trim($_POST['publication_year'] ?? ''),
        'quantity'         => trim($_POST['quantity'] ?? ''),
        'shelf_location'   => trim($_POST['shelf_location'] ?? ''),
    ];

    if ($fields['isbn'] === '') {
        $errors['isbn'] = 'ISBN / Book ID is required.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE isbn = :isbn AND id <> :id');
        $stmt->execute([':isbn' => $fields['isbn'], ':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors['isbn'] = 'A book with this ISBN already exists.';
        }
    }

    if ($fields['title'] === '') {
        $errors['title'] = 'Book title is required.';
    }

    if ($fields['author'] === '') {
        $errors['author'] = 'Author is required.';
    }

    if ($fields['category'] === '') {
        $errors['category'] = 'Category is required.';
    }

    if ($fields['quantity'] === '' || !ctype_digit($fields['quantity'])) {
        $errors['quantity'] = 'Quantity must be a whole number.';
    } elseif ((int)$fields['quantity'] < 1) {
        $errors['quantity'] = 'Quantity must be at least 1.';
    } elseif ((int)$fields['quantity'] < $activeBorrowed) {
        $errors['quantity'] = 'Quantity cannot be less than the ' . $activeBorrowed . ' cop'
            . ($activeBorrowed === 1 ? '' : 'ies') . ' currently borrowed.';
    }

    if ($fields['publication_year'] !== '') {
        $year = (int)$fields['publication_year'];
        if (!ctype_digit($fields['publication_year']) || $year < 1400 || $year > (int)date('Y') + 1) {
            $errors['publication_year'] = 'Enter a valid publication year.';
        }
    }

    if ($fields['shelf_location'] === '') {
        $errors['shelf_location'] = 'Shelf location is required.';
    }

    if (!$errors) {
        $newAvailable = (int)$fields['quantity'] - $activeBorrowed;

        $stmt = $pdo->prepare('
            UPDATE books
            SET isbn = :isbn, title = :title, author = :author, category = :category, publisher = :publisher,
                publication_year = :year, quantity = :quantity, available_quantity = :available,
                shelf_location = :shelf, updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':isbn'      => $fields['isbn'],
            ':title'     => $fields['title'],
            ':author'    => $fields['author'],
            ':category'  => $fields['category'],
            ':publisher' => $fields['publisher'] ?: null,
            ':year'      => $fields['publication_year'] !== '' ? (int)$fields['publication_year'] : null,
            ':quantity'  => (int)$fields['quantity'],
            ':available' => $newAvailable,
            ':shelf'     => $fields['shelf_location'],
            ':id'        => $id,
        ]);

        flash('success', 'Book "' . $fields['title'] . '" was updated successfully. Available copies: ' . $newAvailable . '.');
        redirect('books/view.php?id=' . $id);
    }

    $book = array_merge($book, $fields);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-3xl">

    <div class="mb-6">
        <a href="<?= url('books/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
            <?= icon('back', 'h-4 w-4') ?> Back to Books
        </a>
        <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Edit Book</h2>
        <p class="mt-1 text-sm text-slate-500">Update catalogue details for <span class="font-semibold text-blue-800"><?= e($book['title']) ?></span>.</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>Please fix the highlighted fields below and try again.</span>
        </div>
    <?php endif; ?>

    <?php if ($activeBorrowed > 0): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800" role="note">
            <?= icon('info', 'h-5 w-5 shrink-0') ?>
            <span>
                This book has <strong><?= $activeBorrowed ?></strong> borrowed cop<?= $activeBorrowed === 1 ? 'y' : 'ies' ?> right now.
                The available quantity is calculated automatically as <strong>Quantity &minus; Borrowed</strong> when you save.
            </span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>?id=<?= $id ?>" novalidate data-validate
          class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">

        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label for="isbn" class="mb-1.5 block text-sm font-semibold text-slate-700">ISBN / Book ID <span class="text-red-500">*</span></label>
                <input type="text" id="isbn" name="isbn" value="<?= old_value('isbn', $book['isbn']) ?>"
                       class="<?= input_class($errors, 'isbn') ?>">
                <?= field_error($errors, 'isbn') ?>
            </div>

            <div>
                <label for="category" class="mb-1.5 block text-sm font-semibold text-slate-700">Category <span class="text-red-500">*</span></label>
                <input type="text" id="category" name="category" list="book-categories" value="<?= old_value('category', $book['category']) ?>"
                       class="<?= input_class($errors, 'category') ?>">
                <datalist id="book-categories">
                    <option value="Programming"></option>
                    <option value="Algorithms"></option>
                    <option value="Databases"></option>
                    <option value="Operating Systems"></option>
                    <option value="Networking"></option>
                    <option value="Artificial Intelligence"></option>
                    <option value="Mathematics"></option>
                    <option value="Software Engineering"></option>
                </datalist>
                <?= field_error($errors, 'category') ?>
            </div>

            <div class="md:col-span-2">
                <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-700">Book Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?= old_value('title', $book['title']) ?>"
                       class="<?= input_class($errors, 'title') ?>">
                <?= field_error($errors, 'title') ?>
            </div>

            <div>
                <label for="author" class="mb-1.5 block text-sm font-semibold text-slate-700">Author <span class="text-red-500">*</span></label>
                <input type="text" id="author" name="author" value="<?= old_value('author', $book['author']) ?>"
                       class="<?= input_class($errors, 'author') ?>">
                <?= field_error($errors, 'author') ?>
            </div>

            <div>
                <label for="publisher" class="mb-1.5 block text-sm font-semibold text-slate-700">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?= old_value('publisher', $book['publisher']) ?>"
                       class="<?= input_class($errors, 'publisher') ?>">
                <?= field_error($errors, 'publisher') ?>
            </div>

            <div>
                <label for="publication_year" class="mb-1.5 block text-sm font-semibold text-slate-700">Publication Year</label>
                <input type="number" id="publication_year" name="publication_year" min="1400" max="<?= (int)date('Y') + 1 ?>" value="<?= old_value('publication_year', $book['publication_year']) ?>"
                       class="<?= input_class($errors, 'publication_year') ?>">
                <?= field_error($errors, 'publication_year') ?>
            </div>

            <div>
                <label for="quantity" class="mb-1.5 block text-sm font-semibold text-slate-700">Total Quantity <span class="text-red-500">*</span></label>
                <input type="number" id="quantity" name="quantity" min="<?= max(1, $activeBorrowed) ?>" value="<?= old_value('quantity', $book['quantity']) ?>"
                       class="<?= input_class($errors, 'quantity') ?>">
                <?= field_error($errors, 'quantity') ?>
                <p class="mt-1.5 text-xs text-slate-400">Current available: <?= (int)$book['available_quantity'] ?> of <?= (int)$book['quantity'] ?></p>
            </div>

            <div>
                <label for="shelf_location" class="mb-1.5 block text-sm font-semibold text-slate-700">Shelf Location <span class="text-red-500">*</span></label>
                <input type="text" id="shelf_location" name="shelf_location" value="<?= old_value('shelf_location', $book['shelf_location']) ?>"
                       class="<?= input_class($errors, 'shelf_location') ?>">
                <?= field_error($errors, 'shelf_location') ?>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-400">Fields marked with <span class="font-bold text-red-500">*</span> are required.</p>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="<?= url('books/view.php?id=' . $id) ?>"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 active:scale-[.99]">
                <?= icon('check', 'h-4 w-4') ?> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
