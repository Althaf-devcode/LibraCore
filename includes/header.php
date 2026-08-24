<?php

$page_title = $page_title ?? 'Dashboard';
$active     = $active ?? '';
$flashes    = take_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraCore &middot; <?= e($page_title) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%231d4ed8'/><text x='50' y='68' font-size='52' text-anchor='middle' fill='white' font-family='Georgia'>L</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        #sidebar::-webkit-scrollbar { width: 5px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 9999px; }
        @media print {
            .no-print { display: none !important; }
            .lg\:pl-64 { padding-left: 0 !important; }
            body { background: white !important; }
            main { padding: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">

<div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-blue-950/60 backdrop-blur-sm lg:hidden"></div>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="flex min-h-screen flex-col lg:pl-64">

    <header class="no-print sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 shadow-sm backdrop-blur sm:px-6 lg:px-8">
        <button id="sidebarToggle" type="button" aria-label="Open menu"
                class="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 lg:hidden">
            <?= icon('menu', 'h-6 w-6') ?>
        </button>

        <span class="text-base font-extrabold tracking-tight text-blue-950 lg:hidden">LibraCore</span>

        <div class="hidden min-w-0 lg:block">
            <h1 class="truncate text-lg font-bold text-blue-950"><?= e($page_title) ?></h1>
            <p class="-mt-0.5 text-xs text-slate-400">Library Management System</p>
        </div>

        <div class="ml-auto flex items-center gap-3">
            <div class="hidden items-center gap-2.5 rounded-full border border-slate-200 bg-slate-50 py-1.5 pl-1.5 pr-4 sm:flex">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-blue-900 text-sm font-bold text-white">
                    <?= e(strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1))) ?>
                </span>
                <span class="leading-tight">
                    <span class="block text-sm font-semibold text-slate-800"><?= e($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                    <span class="block text-[11px] text-slate-500">Administrator</span>
                </span>
            </div>

            <a href="<?= url('auth/logout.php') ?>" data-confirm="Log out from LibraCore?"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-1">
                <?= icon('logout', 'h-4 w-4') ?><span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 lg:p-8">

        <?php foreach ($flashes as $fl): ?>
            <div data-toast
                 class="fixed right-4 top-4 z-[70] flex w-80 max-w-[92vw] items-start gap-3 rounded-xl px-4 py-3.5 text-sm font-medium text-white shadow-xl <?= flash_style($fl['type']) ?>">
                <span class="mt-0.5 shrink-0"><?= icon(flash_icon($fl['type']), 'h-5 w-5') ?></span>
                <p class="flex-1 leading-snug"><?= e($fl['message']) ?></p>
                <button type="button" data-toast-close aria-label="Dismiss" class="shrink-0 opacity-70 transition hover:opacity-100">
                    <?= icon('close', 'h-4 w-4') ?>
                </button>
            </div>
        <?php endforeach; ?>
