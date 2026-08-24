<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Books';
$active     = 'books';

$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$page     = page_number();
$perPage  = 8;

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(isbn LIKE :q1 OR title LIKE :q2 OR author LIKE :q3 OR category LIKE :q4)';
    $like    = '%' . $q . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
if ($category !== '') {
    $where[]  = 'category = :category';
    $params[':category'] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM books $whereSql");
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM books $whereSql ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM books WHERE category <> "" ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Book Management</h2>
        <p class="mt-1 text-sm text-slate-500">Manage the library catalogue, stock levels and shelf locations.</p>
    </div>
    <a href="<?= url('books/add.php') ?>"
       class="inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
        <?= icon('plus', 'h-4 w-4') ?> Add Book
    </a>
</div>

<form method="get" action="<?= url('books/index.php') ?>" class="mb-5 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm sm:grid-cols-[1fr_220px_auto]">
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><?= icon('search', 'h-4 w-4') ?></span>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by ISBN, title, author or category..."
               class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
    </div>
    <select name="category" data-auto-submit
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
            <?= icon('search', 'h-4 w-4') ?> Filter
        </button>
        <?php if ($q !== '' || $category !== ''): ?>
            <a href="<?= url('books/index.php') ?>" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="text-sm font-bold text-blue-950">Library Catalogue</h3>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= number_format($totalRows) ?> book<?= $totalRows === 1 ? '' : 's' ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3 font-semibold">ISBN</th>
                    <th class="px-5 py-3 font-semibold">Title</th>
                    <th class="px-5 py-3 font-semibold">Author</th>
                    <th class="px-5 py-3 font-semibold">Category</th>
                    <th class="px-5 py-3 font-semibold">Qty</th>
                    <th class="px-5 py-3 font-semibold">Available</th>
                    <th class="px-5 py-3 font-semibold">Shelf</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$books): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><?= icon('book', 'h-6 w-6') ?></span>
                            <p class="font-semibold text-slate-500"><?= ($q !== '' || $category !== '') ? 'No books match your search.' : 'No books in the catalogue yet.' ?></p>
                            <p class="mt-1 text-xs text-slate-400"><?= ($q !== '' || $category !== '') ? 'Try different keywords or clear the filters.' : 'Add your first book to get started.' ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $b): ?>
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-5 py-3.5"><span class="whitespace-nowrap rounded-md bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700"><?= e($b['isbn']) ?></span></td>
                            <td class="max-w-[240px] px-5 py-3.5">
                                <p class="truncate font-semibold text-slate-800"><?= e($b['title']) ?></p>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600"><?= e($b['author']) ?></td>
                            <td class="px-5 py-3.5"><span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"><?= e($b['category']) ?></span></td>
                            <td class="px-5 py-3.5 font-medium text-slate-600"><?= (int)$b['quantity'] ?></td>
                            <td class="px-5 py-3.5">
                                <span class="mr-2 font-bold <?= (int)$b['available_quantity'] === 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= (int)$b['available_quantity'] ?></span>
                                <?= stock_badge_html((int)$b['available_quantity']) ?>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600"><?= e($b['shelf_location']) ?></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= url('books/view.php?id=' . (int)$b['id']) ?>" title="View" aria-label="View book"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100"><?= icon('eye', 'h-4 w-4') ?></a>
                                    <a href="<?= url('books/edit.php?id=' . (int)$b['id']) ?>" title="Edit" aria-label="Edit book"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100"><?= icon('edit', 'h-4 w-4') ?></a>
                                    <form method="post" action="<?= url('books/delete.php') ?>" data-confirm="Delete book &quot;<?= e($b['title']) ?>&quot;? This cannot be undone.">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button type="submit" title="Delete" aria-label="Delete book"
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
