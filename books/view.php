<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Book Details';
$active     = 'books';

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'The requested book could not be found.');
    redirect('books/index.php');
}

$stmt = $pdo->prepare("
    SELECT br.*, s.student_id, s.name AS student_name
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    WHERE br.book_id = :bid
    ORDER BY br.borrow_date DESC, br.id DESC
");
$stmt->execute([':bid' => $id]);
$history = $stmt->fetchAll();

[$stockLabel] = stock_status((int)$book['available_quantity']);

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-5xl">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('books/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
                <?= icon('back', 'h-4 w-4') ?> Back to Books
            </a>
            <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Book Details</h2>
        </div>
        <div class="flex gap-2.5">
            <?php if ((int)$book['available_quantity'] > 0): ?>
                <a href="<?= url('borrow/add.php?book_id=' . $id) ?>"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700">
                    <?= icon('swap', 'h-4 w-4') ?> Borrow This Book
                </a>
            <?php endif; ?>
            <a href="<?= url('books/edit.php?id=' . $id) ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                <?= icon('edit', 'h-4 w-4') ?> Edit
            </a>
            <form method="post" action="<?= url('books/delete.php') ?>"
                  data-confirm="Delete book &quot;<?= e($book['title']) ?>&quot;? This cannot be undone.">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-100">
                    <?= icon('trash', 'h-4 w-4') ?> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <section class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <div class="flex flex-col items-center border-b border-slate-100 pb-5 text-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-800 text-white shadow-lg shadow-blue-600/25">
                    <?= icon('book', 'h-10 w-10') ?>
                </span>
                <h3 class="mt-3 text-lg font-bold leading-snug text-blue-950"><?= e($book['title']) ?></h3>
                <p class="mt-1 text-sm font-medium text-slate-500"><?= e($book['author']) ?></p>
                <span class="mt-2 rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700"><?= e($book['isbn']) ?></span>
            </div>

            <dl class="mt-5 space-y-3.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Category</dt>
                    <dd><span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"><?= e($book['category']) ?></span></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Publisher</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= e($book['publisher'] ?: '-') ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Published</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= $book['publication_year'] ? (int)$book['publication_year'] : '-' ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Shelf Location</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= e($book['shelf_location']) ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Total Quantity</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= (int)$book['quantity'] ?></dd>
                </div>
                <div class="flex items-center justify-between gap-3 pt-1">
                    <dt class="font-medium text-slate-400">Availability</dt>
                    <dd class="text-right">
                        <?= stock_badge_html((int)$book['available_quantity']) ?>
                        <p class="mt-1 text-xs text-slate-500"><span class="font-bold text-emerald-600"><?= (int)$book['available_quantity'] ?></span> of <?= (int)$book['quantity'] ?> available &middot; <?= $stockLabel ?></p>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 text-center shadow-sm">
                    <p class="text-2xl font-extrabold text-blue-950"><?= count($history) ?></p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Total Borrows</p>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 text-center shadow-sm">
                    <?php $currentlyOut = count(array_filter($history, fn($h) => $h['return_date'] === null)); ?>
                    <p class="text-2xl font-extrabold text-sky-600"><?= $currentlyOut ?></p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Currently Out</p>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 text-center shadow-sm">
                    <p class="text-2xl font-extrabold text-emerald-600"><?= (int)$book['available_quantity'] ?></p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">On Shelf Now</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
                <header class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-blue-950">Borrowing History</h3>
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-3 font-semibold">Student</th>
                                <th class="px-5 py-3 font-semibold">Borrowed</th>
                                <th class="px-5 py-3 font-semibold">Due</th>
                                <th class="px-5 py-3 font-semibold">Returned</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$history): ?>
                                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">This book has not been borrowed yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr class="transition hover:bg-blue-50/40">
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-slate-800"><?= e($h['student_name']) ?></p>
                                            <p class="text-xs text-slate-400"><?= e($h['student_id']) ?></p>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600"><?= e(date('M j, Y', strtotime($h['borrow_date']))) ?></td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600"><?= e(date('M j, Y', strtotime($h['due_date']))) ?></td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600"><?= $h['return_date'] ? e(date('M j, Y', strtotime($h['return_date']))) : '-' ?></td>
                                        <td class="px-5 py-3.5"><?= status_badge_html(borrow_status($h)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
