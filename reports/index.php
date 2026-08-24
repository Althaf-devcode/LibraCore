<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Reports';
$active     = 'reports';

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM books) AS total_books,
        (SELECT COALESCE(SUM(available_quantity), 0) FROM books) AS available_copies,
        (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL) AS currently_borrowed,
        (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NOT NULL) AS returned,
        (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL AND due_date < CURDATE()) AS overdue
")->fetch();

$mostBorrowed = $pdo->query("
    SELECT b.id, b.isbn, b.title, b.author, COUNT(br.id) AS times_borrowed,
           SUM(br.return_date IS NULL) AS currently_out
    FROM books b
    JOIN borrow_records br ON br.book_id = b.id
    GROUP BY b.id, b.isbn, b.title, b.author
    ORDER BY times_borrowed DESC, b.title ASC
    LIMIT 10
")->fetchAll();

$currentBorrows = $pdo->query("
    SELECT br.borrow_date, br.due_date,
           s.student_id, s.name AS student_name,
           b.title AS book_title, b.isbn,
           CASE WHEN br.due_date < CURDATE() THEN 'Overdue' ELSE 'Borrowed' END AS calc_status
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    JOIN books b ON b.id = br.book_id
    WHERE br.return_date IS NULL
    ORDER BY br.due_date ASC
")->fetchAll();

$overdueBooks = $pdo->query("
    SELECT br.due_date,
           s.student_id, s.name AS student_name,
           b.title AS book_title, b.isbn,
           DATEDIFF(CURDATE(), br.due_date) AS days_overdue
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    JOIN books b ON b.id = br.book_id
    WHERE br.return_date IS NULL AND br.due_date < CURDATE()
    ORDER BY days_overdue DESC
")->fetchAll();

$maxBorrowedCount = 0;
foreach ($mostBorrowed as $row) {
    $maxBorrowedCount = max($maxBorrowedCount, (int)$row['times_borrowed']);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Library Reports</h2>
        <p class="mt-1 text-sm text-slate-500">Statistics and insights as of <?= e(date('F j, Y')) ?>.</p>
    </div>
    <button type="button" data-print
            class="no-print inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
        <?= icon('print', 'h-4 w-4') ?> Print Report
    </button>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
    <?php
    $reportCards = [
        ['label' => 'Total Students',      'value' => (int)$stats['total_students'],     'icon' => 'users',  'tone' => 'bg-blue-100 text-blue-700'],
        ['label' => 'Total Books',         'value' => (int)$stats['total_books'],        'icon' => 'book',   'tone' => 'bg-indigo-100 text-indigo-700'],
        ['label' => 'Available Books',     'value' => (int)$stats['available_copies'],   'icon' => 'check',  'tone' => 'bg-emerald-100 text-emerald-700'],
        ['label' => 'Currently Borrowed',  'value' => (int)$stats['currently_borrowed'], 'icon' => 'swap',   'tone' => 'bg-sky-100 text-sky-700'],
        ['label' => 'Returned Records',    'value' => (int)$stats['returned'],           'icon' => 'report', 'tone' => 'bg-violet-100 text-violet-700'],
        ['label' => 'Overdue Books',       'value' => (int)$stats['overdue'],            'icon' => 'warn',   'tone' => 'bg-red-100 text-red-700'],
    ];
    foreach ($reportCards as $card): ?>
        <div class="flex items-center gap-4 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl <?= $card['tone'] ?>">
                <?= icon($card['icon'], 'h-6 w-6') ?>
            </span>
            <div>
                <p class="text-3xl font-extrabold leading-tight text-blue-950"><?= number_format($card['value']) ?></p>
                <p class="text-sm font-medium text-slate-500"><?= e($card['label']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-8 space-y-6">

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-950">Most Borrowed Books</h3>
            <p class="mt-0.5 text-xs text-slate-400">Top <?= count($mostBorrowed) ?: '' ?> titles ranked by borrowing frequency.</p>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-semibold">Rank</th>
                        <th class="px-5 py-3 font-semibold">Book</th>
                        <th class="px-5 py-3 font-semibold">Author</th>
                        <th class="px-5 py-3 font-semibold">Times Borrowed</th>
                        <th class="px-5 py-3 font-semibold">Currently Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$mostBorrowed): ?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No borrow data available yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mostBorrowed as $i => $b): ?>
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-extrabold <?= $i === 0 ? 'bg-blue-600 text-white' : ($i < 3 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500') ?>">
                                        <?= $i + 1 ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-800"><?= e($b['title']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($b['isbn']) ?></p>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600"><?= e($b['author']) ?></td>
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-blue-800"><?= (int)$b['times_borrowed'] ?></p>
                                    <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-700"
                                             style="width: <?= $maxBorrowedCount > 0 ? round(((int)$b['times_borrowed'] / $maxBorrowedCount) * 100) : 0 ?>%"></div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?= (int)$b['currently_out'] > 0
                                        ? '<span class="font-semibold text-sky-600">' . (int)$b['currently_out'] . '</span>'
                                        : '<span class="text-slate-400">0</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-950">Currently Borrowed Books</h3>
            <p class="mt-0.5 text-xs text-slate-400"><?= count($currentBorrows) ?> book<?= count($currentBorrows) === 1 ? '' : 's' ?> out on loan.</p>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-semibold">Student</th>
                        <th class="px-5 py-3 font-semibold">Book</th>
                        <th class="px-5 py-3 font-semibold">Borrow Date</th>
                        <th class="px-5 py-3 font-semibold">Due Date</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$currentBorrows): ?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No books are currently borrowed.</td></tr>
                    <?php else: ?>
                        <?php foreach ($currentBorrows as $r): ?>
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-800"><?= e($r['student_name']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($r['student_id']) ?></p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-blue-900"><?= e($r['book_title']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($r['isbn']) ?></p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e(date('M j, Y', strtotime($r['borrow_date']))) ?></td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e(date('M j, Y', strtotime($r['due_date']))) ?></td>
                                <td class="px-5 py-3.5"><?= status_badge_html($r['calc_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-blue-950">Overdue Books</h3>
            <p class="mt-0.5 text-xs text-slate-400"><?= count($overdueBooks) ?> book<?= count($overdueBooks) === 1 ? '' : 's' ?> past the due date and not yet returned.</p>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-semibold">Student</th>
                        <th class="px-5 py-3 font-semibold">Book</th>
                        <th class="px-5 py-3 font-semibold">Due Date</th>
                        <th class="px-5 py-3 font-semibold">Days Overdue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$overdueBooks): ?>
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">Great news - there are no overdue books right now!</td></tr>
                    <?php else: ?>
                        <?php foreach ($overdueBooks as $o): ?>
                            <tr class="transition hover:bg-red-50/40">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-800"><?= e($o['student_name']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($o['student_id']) ?></p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-blue-900"><?= e($o['book_title']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($o['isbn']) ?></p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e(date('M j, Y', strtotime($o['due_date']))) ?></td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-extrabold text-red-600 ring-1 ring-inset ring-red-600/20">
                                        <?= icon('warn', 'h-3.5 w-3.5') ?> <?= (int)$o['days_overdue'] ?> day<?= (int)$o['days_overdue'] === 1 ? '' : 's' ?> late
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
