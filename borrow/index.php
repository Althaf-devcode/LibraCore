<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Borrow Records';
$active     = 'borrow';

$q         = trim($_GET['q'] ?? '');
$fStudent  = (int)($_GET['student'] ?? 0);
$fBook     = (int)($_GET['book'] ?? 0);
$fStatus   = $_GET['status'] ?? '';
$fDate     = trim($_GET['date'] ?? '');
$page      = page_number();
$perPage   = 8;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(s.student_id LIKE :q1 OR s.name LIKE :q2 OR b.isbn LIKE :q3 OR b.title LIKE :q4)';
    $like    = '%' . $q . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
if ($fStudent > 0) {
    $where[]  = 'br.student_id = :student';
    $params[':student'] = $fStudent;
}
if ($fBook > 0) {
    $where[] = 'br.book_id = :book';
    $params[':book'] = $fBook;
}
if ($fStatus === 'Borrowed') {
    $where[] = 'br.return_date IS NULL AND br.due_date >= CURDATE()';
} elseif ($fStatus === 'Returned') {
    $where[] = 'br.return_date IS NOT NULL';
} elseif ($fStatus === 'Overdue') {
    $where[] = 'br.return_date IS NULL AND br.due_date < CURDATE()';
}
if (valid_date($fDate)) {
    $where[]  = 'br.borrow_date = :bdate';
    $params[':bdate'] = $fDate;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    JOIN books b ON b.id = br.book_id
    $whereSql
");
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT br.*, s.student_id, s.name AS student_name, b.isbn, b.title AS book_title,
           CASE
               WHEN br.return_date IS NOT NULL THEN 'Returned'
               WHEN br.due_date < CURDATE() THEN 'Overdue'
               ELSE 'Borrowed'
           END AS calc_status,
           CASE WHEN br.return_date IS NULL AND br.due_date < CURDATE()
                THEN DATEDIFF(CURDATE(), br.due_date) ELSE NULL END AS overdue_days
    FROM borrow_records br
    JOIN students s ON s.id = br.student_id
    JOIN books b ON b.id = br.book_id
    $whereSql
    ORDER BY br.return_date IS NULL DESC,
             br.due_date ASC,
             br.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$records = $stmt->fetchAll();

$statusCounts = $pdo->query("
    SELECT
        SUM(return_date IS NULL AND due_date >= CURDATE()) AS borrowed,
        SUM(return_date IS NOT NULL) AS returned,
        SUM(return_date IS NULL AND due_date < CURDATE()) AS overdue
    FROM borrow_records
")->fetch();

$filterStudents = $pdo->query('SELECT id, student_id, name FROM students ORDER BY name')->fetchAll();
$filterBooks    = $pdo->query('SELECT id, isbn, title FROM books ORDER BY title')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Borrow Records</h2>
        <p class="mt-1 text-sm text-slate-500">Track every borrowing and return in the library.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
            <?= icon('swap', 'h-3.5 w-3.5') ?> Borrowed: <?= (int)$statusCounts['borrowed'] ?>
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
            <?= icon('check', 'h-3.5 w-3.5') ?> Returned: <?= (int)$statusCounts['returned'] ?>
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700">
            <?= icon('warn', 'h-3.5 w-3.5') ?> Overdue: <?= (int)$statusCounts['overdue'] ?>
        </span>
        <a href="<?= url('borrow/add.php') ?>"
           class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            <?= icon('plus', 'h-4 w-4') ?> New Borrow
        </a>
    </div>
</div>

<form method="get" action="<?= url('borrow/index.php') ?>" class="mb-5 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[1fr_170px_170px_150px_150px_auto]">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search student, book or ISBN..."
           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
    <select name="student"
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        <option value="">All Students</option>
        <?php foreach ($filterStudents as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $fStudent === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="book"
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        <option value="">All Books</option>
        <?php foreach ($filterBooks as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $fBook === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status"
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        <option value="">All Status</option>
        <option value="Borrowed" <?= $fStatus === 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
        <option value="Returned" <?= $fStatus === 'Returned' ? 'selected' : '' ?>>Returned</option>
        <option value="Overdue" <?= $fStatus === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
    </select>
    <input type="date" name="date" value="<?= e($fDate) ?>" aria-label="Borrow date filter"
           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
    <div class="flex gap-2 md:col-span-2 xl:col-span-1">
        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
            <?= icon('search', 'h-4 w-4') ?> Apply
        </button>
        <?php if ($q !== '' || $fStudent || $fBook || $fStatus !== '' || $fDate !== ''): ?>
            <a href="<?= url('borrow/index.php') ?>" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="text-sm font-bold text-blue-950">Transactions</h3>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= number_format($totalRows) ?> record<?= $totalRows === 1 ? '' : 's' ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3 font-semibold">#</th>
                    <th class="px-4 py-3 font-semibold">Student</th>
                    <th class="px-4 py-3 font-semibold">Book</th>
                    <th class="px-4 py-3 font-semibold">Borrowed</th>
                    <th class="px-4 py-3 font-semibold">Due Date</th>
                    <th class="px-4 py-3 font-semibold">Returned</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$records): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><?= icon('swap', 'h-6 w-6') ?></span>
                            <p class="font-semibold text-slate-500">No borrow records found.</p>
                            <p class="mt-1 text-xs text-slate-400"><?= ($q !== '' || $fStudent || $fBook || $fStatus !== '' || $fDate !== '') ? 'Try adjusting the filters.' : 'Record a new borrowing to get started.' ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-4 py-3.5 font-bold text-slate-400">#<?= (int)$r['id'] ?></td>
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-slate-800"><?= e($r['student_name']) ?></p>
                                <p class="text-xs text-slate-400"><?= e($r['student_id']) ?></p>
                            </td>
                            <td class="max-w-[220px] px-4 py-3.5">
                                <p class="truncate font-medium text-blue-900"><?= e($r['book_title']) ?></p>
                                <p class="truncate text-xs text-slate-400"><?= e($r['isbn']) ?></p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-slate-600"><?= e(date('M j, Y', strtotime($r['borrow_date']))) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-slate-600"><?= e(date('M j, Y', strtotime($r['due_date']))) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-slate-600"><?= $r['return_date'] ? e(date('M j, Y', strtotime($r['return_date']))) : '-' ?></td>
                            <td class="px-4 py-3.5">
                                <?= status_badge_html($r['calc_status']) ?>
                                <?php if ($r['overdue_days'] !== null): ?>
                                    <p class="mt-0.5 text-[11px] font-semibold text-red-500"><?= (int)$r['overdue_days'] ?> day<?= (int)$r['overdue_days'] === 1 ? '' : 's' ?> late</p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <?php if ($r['return_date'] === null): ?>
                                    <form method="post" action="<?= url('borrow/return.php') ?>"
                                          data-confirm="Confirm return of &quot;<?= e($r['book_title']) ?>&quot; from <?= e($r['student_name']) ?>?<?= $r['overdue_days'] !== null ? ' This book is OVERDUE by ' . (int)$r['overdue_days'] . ' day(s).' : '' ?>">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                            <?= icon('check', 'h-3.5 w-3.5') ?> Return Book
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"><?= icon('check', 'h-3.5 w-3.5') ?> Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100 px-5 py-4">
        <?= paginate($page, $totalPages) ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
