<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Dashboard';
$active     = 'dashboard';

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM books) AS total_books,
        (SELECT COALESCE(SUM(quantity), 0) FROM books) AS total_copies,
        (SELECT COALESCE(SUM(available_quantity), 0) FROM books) AS available_copies,
        (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL) AS borrowed_books,
        (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL AND due_date < CURDATE()) AS overdue_books,
        (SELECT COUNT(*) FROM borrow_records) AS total_records
")->fetch();

$recentStudents = $pdo->query('SELECT id, student_id, name, department, year, created_at FROM students ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
$recentBooks    = $pdo->query('SELECT id, isbn, title, author, available_quantity FROM books ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();

$recentBorrows = $pdo->query("
    SELECT br.id, br.borrow_date, br.due_date, br.return_date,
           s.name AS student_name, s.student_id,
           b.title AS book_title
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    JOIN books b ON b.id = br.book_id
    ORDER BY br.borrow_date DESC, br.id DESC
    LIMIT 6
")->fetchAll();

$cards = [
    ['label' => 'Total Students',    'value' => number_format((int)$stats['total_students']),   'icon' => 'users',     'tone' => 'bg-blue-100 text-blue-700'],
    ['label' => 'Total Book Titles', 'value' => number_format((int)$stats['total_books']),      'icon' => 'book',      'tone' => 'bg-indigo-100 text-indigo-700'],
    ['label' => 'Available Copies',  'value' => number_format((int)$stats['available_copies']), 'icon' => 'check',     'tone' => 'bg-emerald-100 text-emerald-700'],
    ['label' => 'Borrowed Books',    'value' => number_format((int)$stats['borrowed_books']),   'icon' => 'swap',      'tone' => 'bg-sky-100 text-sky-700'],
    ['label' => 'Overdue Books',     'value' => number_format((int)$stats['overdue_books']),    'icon' => 'warn',      'tone' => 'bg-red-100 text-red-700'],
    ['label' => 'Borrow Records',    'value' => number_format((int)$stats['total_records']),    'icon' => 'report',    'tone' => 'bg-violet-100 text-violet-700'],
];

function lc_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

    return strtoupper($first . $last);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Welcome back, <?= e($_SESSION['admin_name'] ?? 'Admin') ?>!</h2>
        <p class="mt-1 text-sm text-slate-500">Here is an overview of your library for <?= e(date('l, F j, Y')) ?>.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= url('students/add.php') ?>"
           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            <?= icon('plus', 'h-4 w-4') ?> Add Student
        </a>
        <a href="<?= url('books/add.php') ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            <?= icon('book', 'h-4 w-4') ?> Add Book
        </a>
        <a href="<?= url('borrow/add.php') ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            <?= icon('swap', 'h-4 w-4') ?> Borrow Book
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($cards as $card): ?>
        <div class="group flex items-center gap-4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl <?= $card['tone'] ?> transition group-hover:scale-105">
                <?= icon($card['icon'], 'h-6 w-6') ?>
            </span>
            <div class="min-w-0">
                <p class="truncate text-2xl font-extrabold leading-tight text-blue-950"><?= $card['value'] ?></p>
                <p class="text-sm font-medium text-slate-500"><?= e($card['label']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-blue-950">
                <?= icon('users', 'h-4 w-4 text-blue-600') ?> Recent Students
            </h3>
            <a href="<?= url('students/index.php') ?>" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
        </header>
        <?php if (!$recentStudents): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-400">No students added yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($recentStudents as $s): ?>
                    <li>
                        <a href="<?= url('students/view.php?id=' . (int)$s['id']) ?>" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-blue-50/60">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                <?= e(lc_initials($s['name'])) ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800"><?= e($s['name']) ?></span>
                                <span class="block truncate text-xs text-slate-500"><?= e($s['student_id']) ?> &middot; <?= e($s['department']) ?></span>
                            </span>
                            <span class="shrink-0 text-[11px] font-medium text-slate-400"><?= e(date('M j, Y', strtotime($s['created_at']))) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-blue-950">
                <?= icon('book', 'h-4 w-4 text-blue-600') ?> Recent Books
            </h3>
            <a href="<?= url('books/index.php') ?>" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
        </header>
        <?php if (!$recentBooks): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-400">No books added yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($recentBooks as $b): ?>
                    <li>
                        <a href="<?= url('books/view.php?id=' . (int)$b['id']) ?>" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-blue-50/60">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                <?= icon('book', 'h-5 w-5') ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800"><?= e($b['title']) ?></span>
                                <span class="block truncate text-xs text-slate-500"><?= e($b['author']) ?></span>
                            </span>
                            <?= stock_badge_html((int)$b['available_quantity']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-blue-950">
                <?= icon('swap', 'h-4 w-4 text-blue-600') ?> Recent Borrowing Activity
            </h3>
            <a href="<?= url('borrow/index.php') ?>" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
        </header>
        <?php if (!$recentBorrows): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-400">No borrowing activity yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($recentBorrows as $r): ?>
                    <?php $row = ['return_date' => $r['return_date'], 'due_date' => $r['due_date']]; ?>
                    <li class="px-5 py-3.5 transition hover:bg-blue-50/60">
                        <div class="flex items-start justify-between gap-3">
                            <p class="min-w-0 flex-1 truncate text-sm text-slate-700">
                                <span class="font-semibold text-slate-900"><?= e($r['student_name']) ?></span>
                                borrowed
                                <span class="font-medium text-blue-800"><?= e($r['book_title']) ?></span>
                            </p>
                            <?= status_badge_html(borrow_status($row)) ?>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Borrowed <?= e(date('M j, Y', strtotime($r['borrow_date']))) ?> &middot;
                            Due <?= e(date('M j, Y', strtotime($r['due_date']))) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
