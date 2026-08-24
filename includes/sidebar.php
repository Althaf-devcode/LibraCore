<aside id="sidebar"
       class="no-print fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-blue-950 via-blue-950 to-blue-900 shadow-2xl transition-transform duration-200 ease-out lg:translate-x-0">

    <div class="flex items-center gap-3 border-b border-blue-900/60 px-5 py-5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-900/50 ring-1 ring-blue-500/40">
            <?= icon('logo', 'h-6 w-6') ?>
        </span>
        <span class="leading-tight">
            <span class="block text-lg font-extrabold tracking-tight text-white">LibraCore</span>
            <span class="block text-[11px] font-medium text-blue-300">Library Management System</span>
        </span>
        <button id="sidebarClose" type="button" aria-label="Close menu"
                class="ml-auto rounded-lg p-1.5 text-blue-300 transition hover:bg-blue-900 hover:text-white lg:hidden">
            <?= icon('close', 'h-5 w-5') ?>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Main navigation">
        <p class="px-3 pb-1.5 pt-1 text-[11px] font-bold uppercase tracking-widest text-blue-400/70">Main</p>

        <?php
        function lc_nav_item(string $href, string $label, string $ic, bool $isActive): void
        {
            $cls = $isActive
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40'
                : 'text-blue-200 hover:bg-blue-900/70 hover:text-white';
            echo '<a href="' . url($href) . '"' . ($isActive ? ' aria-current="page"' : '')
                . ' class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 '
                . $cls . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
        }

        lc_nav_item('dashboard/index.php', 'Dashboard', 'dashboard', $active === 'dashboard');

        echo '<p class="px-3 pb-1.5 pt-4 text-[11px] font-bold uppercase tracking-widest text-blue-400/70">Management</p>';
        lc_nav_item('students/index.php', 'Students', 'users', $active === 'students');
        lc_nav_item('books/index.php', 'Books', 'book', $active === 'books');
        lc_nav_item('borrow/index.php', 'Borrow Records', 'swap', $active === 'borrow');

        echo '<p class="px-3 pb-1.5 pt-4 text-[11px] font-bold uppercase tracking-widest text-blue-400/70">Insights</p>';
        lc_nav_item('reports/index.php', 'Reports', 'report', $active === 'reports');
        ?>
    </nav>

    <div class="border-t border-blue-900/60 px-3 py-4">
        <a href="<?= url('auth/logout.php') ?>" data-confirm="Log out from LibraCore?"
           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-blue-200 transition hover:bg-red-500 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
            <?= icon('logout') ?><span>Logout</span>
        </a>
        <p class="px-3 pt-3 text-[11px] leading-relaxed text-blue-400/60">
            LibraCore v1.0<br>Admin Panel
        </p>
    </div>
</aside>
