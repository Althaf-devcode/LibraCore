<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Student Details';
$active     = 'students';

$id      = (int)($_GET['id'] ?? 0);
$stmt    = $pdo->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    flash('error', 'The requested student could not be found.');
    redirect('students/index.php');
}

$stmt = $pdo->prepare("
    SELECT br.*, b.isbn, b.title AS book_title, b.author
    FROM borrow_records br
    JOIN books b ON b.id = br.book_id
    WHERE br.student_id = :id
    ORDER BY br.borrow_date DESC, br.id DESC
");
$stmt->execute([':id' => $id]);
$history = $stmt->fetchAll();

$totalActive  = 0;
$totalOverdue = 0;
foreach ($history as $h) {
    if ($h['return_date'] === null) {
        $totalActive++;
        if (borrow_status($h) === 'Overdue') {
            $totalOverdue++;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-5xl">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('students/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
                <?= icon('back', 'h-4 w-4') ?> Back to Students
            </a>
            <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Student Details</h2>
        </div>
        <div class="flex gap-2.5">
            <a href="<?= url('students/edit.php?id=' . $id) ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                <?= icon('edit', 'h-4 w-4') ?> Edit
            </a>
            <form method="post" action="<?= url('students/delete.php') ?>"
                  data-confirm="Delete student &quot;<?= e($student['name']) ?>&quot;? This cannot be undone.">
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
                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-800 text-2xl font-extrabold text-white shadow-lg shadow-blue-600/25">
                    <?= e(strtoupper(mb_substr($student['name'], 0, 1))) ?>
                </span>
                <h3 class="mt-3 text-lg font-bold text-blue-950"><?= e($student['name']) ?></h3>
                <span class="mt-1.5 rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700"><?= e($student['student_id']) ?></span>
                <p class="mt-2 text-sm font-medium text-slate-500"><?= e($student['department']) ?></p>
            </div>

            <dl class="mt-5 space-y-3.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Email</dt>
                    <dd class="break-all text-right font-semibold text-slate-700"><?= e($student['email']) ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Phone</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= e($student['phone']) ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-400">Year</dt>
                    <dd class="text-right font-semibold text-slate-700">Year <?= (int)$student['year'] ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 font-medium text-slate-400">Added</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= e(date('M j, Y', strtotime($student['created_at']))) ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 font-medium text-slate-400">Updated</dt>
                    <dd class="text-right font-semibold text-slate-700"><?= e(date('M j, Y', strtotime($student['updated_at']))) ?></dd>
                </div>
                <div class="pt-2">
                    <dt class="mb-1 font-medium text-slate-400">Address</dt>
                    <dd class="rounded-lg bg-slate-50 p-3 text-sm leading-relaxed text-slate-600"><?= e($student['address']) ?></dd>
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
                    <p class="text-2xl font-extrabold text-sky-600"><?= $totalActive ?></p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Currently Borrowed</p>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-white p-4 text-center shadow-sm">
                    <p class="text-2xl font-extrabold <?= $totalOverdue > 0 ? 'text-red-600' : 'text-blue-950' ?>"><?= $totalOverdue ?></p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Overdue</p>
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
                                <th class="px-5 py-3 font-semibold">Book</th>
                                <th class="px-5 py-3 font-semibold">Borrowed</th>
                                <th class="px-5 py-3 font-semibold">Due</th>
                                <th class="px-5 py-3 font-semibold">Returned</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$history): ?>
                                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">This student has not borrowed any books yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr class="transition hover:bg-blue-50/40">
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-slate-800"><?= e($h['book_title']) ?></p>
                                            <p class="text-xs text-slate-400"><?= e($h['isbn']) ?></p>
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
