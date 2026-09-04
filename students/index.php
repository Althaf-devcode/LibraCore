<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Students';
$active     = 'students';

$q        = trim($_GET['q'] ?? '');
$page     = page_number();
$perPage  = 8;

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(student_id LIKE :q1 OR name LIKE :q2 OR email LIKE :q3 OR department LIKE :q4)';
    $like    = '%' . $q . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM students $whereSql");
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM students $whereSql ORDER BY student_id ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Student Management</h2>
        <p class="mt-1 text-sm text-slate-500">Add, view, update and remove student records.</p>
    </div>
    <a href="<?= url('students/add.php') ?>"
        class="inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
        <?= icon('plus', 'h-4 w-4') ?> Add Student
    </a>
</div>

<form method="get" action="<?= url('students/index.php') ?>" class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
    <div class="relative flex-1">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><?= icon('search', 'h-4 w-4') ?></span>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by student ID, name, email or department..."
            class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
            <?= icon('search', 'h-4 w-4') ?> Search
        </button>
        <?php if ($q !== ''): ?>
            <a href="<?= url('students/index.php') ?>" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="text-sm font-bold text-blue-950">All Students</h3>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= number_format($totalRows) ?> record<?= $totalRows === 1 ? '' : 's' ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3 font-semibold">Student ID</th>
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Email</th>
                    <th class="px-5 py-3 font-semibold">Phone</th>
                    <th class="px-5 py-3 font-semibold">Department</th>
                    <th class="px-5 py-3 font-semibold">Year</th>
                    <th class="px-5 py-3 font-semibold">Date Added</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$students): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><?= icon('users', 'h-6 w-6') ?></span>
                            <p class="font-semibold text-slate-500"><?= $q !== '' ? 'No students match your search.' : 'No students found.' ?></p>
                            <p class="mt-1 text-xs text-slate-400"><?= $q !== '' ? 'Try a different keyword.' : 'Get started by adding your first student.' ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-5 py-3.5 whitespace-nowrap">
    <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700">
        <?= e($s['student_id']) ?>
    </span>
</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($s['name']) ?></td>
                            <td class="px-5 py-3.5 text-slate-600"><?= e($s['email']) ?></td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600">
                                <?= e($s['phone']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600"><?= e($s['department']) ?></td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full bg-indigo-50 px-2 text-xs font-bold text-indigo-700">
                                    Year <?= (int)$s['year'] ?>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-500"><?= e(date('M j, Y', strtotime($s['created_at']))) ?></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= url('students/view.php?id=' . (int)$s['id']) ?>" title="View" aria-label="View student"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100"><?= icon('eye', 'h-4 w-4') ?></a>
                                    <a href="<?= url('students/edit.php?id=' . (int)$s['id']) ?>" title="Edit" aria-label="Edit student"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100"><?= icon('edit', 'h-4 w-4') ?></a>
                                    <form method="post" action="<?= url('students/delete.php') ?>" data-confirm="Delete student &quot;<?= e($s['name']) ?> (<?= e($s['student_id']) ?>)&quot;? This cannot be undone.">
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" title="Delete" aria-label="Delete student"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100"><?= icon('trash', 'h-4 w-4') ?></button>
                                    </form>
                                </div>
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