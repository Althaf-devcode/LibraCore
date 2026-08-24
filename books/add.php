<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Add Book';
$active     = 'books';

$errors = [];
$fields = [
    'isbn'              => '',
    'title'             => '',
    'author'            => '',
    'category'          => '',
    'publisher'         => '',
    'publication_year'  => '',
    'quantity'          => '1',
    'available_quantity'=> '',
    'shelf_location'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $default) {
        $fields[$key] = trim($_POST[$key] ?? $default);
    }

    if ($fields['isbn'] === '') {
        $errors['isbn'] = 'ISBN / Book ID is required.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE isbn = :isbn');
        $stmt->execute([':isbn' => $fields['isbn']]);
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

    $quantity = (int)$fields['quantity'];
    if ($fields['quantity'] === '' || !ctype_digit($fields['quantity'])) {
        $errors['quantity'] = 'Quantity must be a whole number.';
    } elseif ($quantity < 1) {
        $errors['quantity'] = 'Quantity must be at least 1.';
    }

    $available = null;
    if ($fields['available_quantity'] !== '') {
        if (!ctype_digit($fields['available_quantity'])) {
            $errors['available_quantity'] = 'Available quantity must be a whole number.';
        } else {
            $available = (int)$fields['available_quantity'];
            if ($available < 0) {
                $errors['available_quantity'] = 'Available quantity cannot be negative.';
            } elseif (!$errors && $available > $quantity) {
                $errors['available_quantity'] = 'Available quantity cannot exceed the total quantity.';
            }
        }
    } else {
        $available = $quantity;
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
        $stmt = $pdo->prepare('
            INSERT INTO books (isbn, title, author, category, publisher, publication_year, quantity, available_quantity, shelf_location, created_at, updated_at)
            VALUES (:isbn, :title, :author, :category, :publisher, :year, :quantity, :available, :shelf, NOW(), NOW())
        ');
        $stmt->execute([
            ':isbn'      => $fields['isbn'],
            ':title'     => $fields['title'],
            ':author'    => $fields['author'],
            ':category'  => $fields['category'],
            ':publisher' => $fields['publisher'] ?: null,
            ':year'      => $fields['publication_year'] !== '' ? (int)$fields['publication_year'] : null,
            ':quantity'  => $quantity,
            ':available' => $available,
            ':shelf'     => $fields['shelf_location'],
        ]);

        flash('success', 'Book "' . $fields['title'] . '" was added to the catalogue with ' . $available . ' available cop' . ($available === 1 ? 'y' : 'ies') . '.');
        redirect('books/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-3xl">

    <div class="mb-6">
        <a href="<?= url('books/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
            <?= icon('back', 'h-4 w-4') ?> Back to Books
        </a>
        <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Add New Book</h2>
        <p class="mt-1 text-sm text-slate-500">Register a new title in the library catalogue.</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>Please fix the highlighted fields below and try again.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>" novalidate data-validate
          class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label for="isbn" class="mb-1.5 block text-sm font-semibold text-slate-700">ISBN / Book ID <span class="text-red-500">*</span></label>
                <input type="text" id="isbn" name="isbn" value="<?= old_value('isbn', $fields['isbn']) ?>" placeholder="e.g. 9780132350884"
                       class="<?= input_class($errors, 'isbn') ?>">
                <?= field_error($errors, 'isbn') ?>
            </div>

            <div>
                <label for="category" class="mb-1.5 block text-sm font-semibold text-slate-700">Category <span class="text-red-500">*</span></label>
                <input type="text" id="category" name="category" list="book-categories" value="<?= old_value('category', $fields['category']) ?>" placeholder="e.g. Programming"
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
                <input type="text" id="title" name="title" value="<?= old_value('title', $fields['title']) ?>" placeholder="Full book title"
                       class="<?= input_class($errors, 'title') ?>">
                <?= field_error($errors, 'title') ?>
            </div>

            <div>
                <label for="author" class="mb-1.5 block text-sm font-semibold text-slate-700">Author <span class="text-red-500">*</span></label>
                <input type="text" id="author" name="author" value="<?= old_value('author', $fields['author']) ?>" placeholder="Author name"
                       class="<?= input_class($errors, 'author') ?>">
                <?= field_error($errors, 'author') ?>
            </div>

            <div>
                <label for="publisher" class="mb-1.5 block text-sm font-semibold text-slate-700">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?= old_value('publisher', $fields['publisher']) ?>" placeholder="Publisher name"
                       class="<?= input_class($errors, 'publisher') ?>">
                <?= field_error($errors, 'publisher') ?>
            </div>

            <div>
                <label for="publication_year" class="mb-1.5 block text-sm font-semibold text-slate-700">Publication Year</label>
                <input type="number" id="publication_year" name="publication_year" min="1400" max="<?= (int)date('Y') + 1 ?>" value="<?= old_value('publication_year', $fields['publication_year']) ?>" placeholder="e.g. 2023"
                       class="<?= input_class($errors, 'publication_year') ?>">
                <?= field_error($errors, 'publication_year') ?>
            </div>

            <div>
                <label for="quantity" class="mb-1.5 block text-sm font-semibold text-slate-700">Quantity <span class="text-red-500">*</span></label>
                <input type="number" id="quantity" name="quantity" min="1" value="<?= old_value('quantity', $fields['quantity']) ?>"
                       class="<?= input_class($errors, 'quantity') ?>">
                <?= field_error($errors, 'quantity') ?>
            </div>

            <div>
                <label for="available_quantity" class="mb-1.5 block text-sm font-semibold text-slate-700">Available Quantity</label>
                <input type="number" id="available_quantity" name="available_quantity" min="0" value="<?= old_value('available_quantity', $fields['available_quantity']) ?>" placeholder="Defaults to total quantity"
                       class="<?= input_class($errors, 'available_quantity') ?>">
                <?= field_error($errors, 'available_quantity') ?>
                <p class="mt-1.5 text-xs text-slate-400">Leave empty to set available copies equal to total quantity.</p>
            </div>

            <div>
                <label for="shelf_location" class="mb-1.5 block text-sm font-semibold text-slate-700">Shelf Location <span class="text-red-500">*</span></label>
                <input type="text" id="shelf_location" name="shelf_location" value="<?= old_value('shelf_location', $fields['shelf_location']) ?>" placeholder="e.g. L2-A4"
                       class="<?= input_class($errors, 'shelf_location') ?>">
                <?= field_error($errors, 'shelf_location') ?>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-400">Fields marked with <span class="font-bold text-red-500">*</span> are required. The ISBN must be unique.</p>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="<?= url('books/index.php') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 active:scale-[.99]">
                <?= icon('plus', 'h-4 w-4') ?> Save Book
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
