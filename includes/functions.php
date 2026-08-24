<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!defined('BASE_URL')) {
    $lcDocRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'])), '/') : '';
    $lcAppPath = str_replace('\\', '/', dirname(__DIR__));
    $lcBase    = ($lcDocRoot !== '' && strpos($lcAppPath, $lcDocRoot) === 0) ? substr($lcAppPath, strlen($lcDocRoot)) : '';
    define('BASE_URL', rtrim($lcBase, '/'));
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? BASE_URL : BASE_URL . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#i', $path) ? $path : url($path)));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function flash_style(string $type): string
{
    switch ($type) {
        case 'success':
            return 'bg-emerald-600';
        case 'error':
            return 'bg-red-600';
        case 'warning':
            return 'bg-amber-500';
        default:
            return 'bg-blue-600';
    }
}

function flash_icon(string $type): string
{
    switch ($type) {
        case 'success':
            return 'check';
        case 'warning':
        case 'error':
            return 'warn';
        default:
            return 'info';
    }
}

function icon(string $name, string $class = 'h-5 w-5'): string
{
    static $paths = [
        'logo'          => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z',
        'dashboard'     => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'users'         => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'book'          => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'swap'          => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        'report'        => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'logout'        => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9',
        'menu'          => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'close'         => 'M6 18L18 6M6 6l12 12',
        'search'        => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        'plus'          => 'M12 4.5v15m7.5-7.5h-15',
        'eye'           => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'edit'          => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
        'trash'         => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
        'back'          => 'M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18',
        'print'         => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5m-3 0h.008v.008H15V10.5',
        'check'         => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'warn'          => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z',
        'info'          => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        'calendar'      => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'user'          => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
        'chevron_left'  => 'M15.75 19.5L8.25 12l7.5-7.5',
        'chevron_right' => 'M8.25 4.5l7.5 7.5-7.5 7.5',
    ];

    $d = $paths[$name] ?? $paths['info'];

    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="' . e($class) . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
}

function db_today(): string
{
    global $pdo;
    static $today = null;
    if ($today === null) {
        $today = (string)$pdo->query('SELECT CURDATE()')->fetchColumn();
    }
    return $today;
}

function borrow_status(array $record): string
{
    if (!empty($record['return_date'])) {
        return 'Returned';
    }
    if (!empty($record['due_date']) && strcmp(db_today(), $record['due_date']) > 0) {
        return 'Overdue';
    }
    return 'Borrowed';
}

function status_badge_html(string $status): string
{
    $styles = [
        'Borrowed' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'Returned' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'Overdue'  => 'bg-red-50 text-red-700 ring-red-600/20',
    ];
    $style = $styles[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-500/20';

    return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $style . '">' . e($status) . '</span>';
}

function stock_status(int $available): array
{
    if ($available <= 0) {
        return ['Out of Stock', 'bg-red-50 text-red-700 ring-red-600/20'];
    }
    if ($available <= 2) {
        return ['Low Stock', 'bg-amber-50 text-amber-700 ring-amber-600/20'];
    }

    return ['Available', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'];
}

function stock_badge_html(int $available): string
{
    [$label, $style] = stock_status($available);

    return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $style . '">' . e($label) . '</span>';
}

function page_number(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

function paginate(int $page, int $totalPages): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $link = function (int $p) use ($page): string {
        $query = http_build_query(array_merge($_GET, ['page' => $p]));
        $cls   = $p === $page
            ? 'z-10 inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white shadow-sm'
            : 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-700';

        return '<a href="?' . e($query) . '" class="' . $cls . '">' . $p . '</a>';
    };

    $html = '';

    $prevCls = $page > 1
        ? 'inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-700'
        : 'inline-flex h-9 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-400';
    $html .= '<a href="?' . e(http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)]))) . '" class="' . $prevCls . '">' . icon('chevron_left', 'h-4 w-4') . ' Prev</a>';

    $marks = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 1) {
            $marks[$i] = $i;
        } elseif (abs($i - $page) === 2) {
            $marks[$i] = '&hellip;';
        }
    }
    $last = 0;
    foreach ($marks as $mark) {
        if (ctype_digit((string)$mark)) {
            $html .= $link((int)$mark);
            $last = (int)$mark;
        } elseif ($last !== -1) {
            $html .= '<span class="px-1 text-slate-400">' . $mark . '</span>';
            $last = -1;
        }
    }

    $nextCls = $page < $totalPages
        ? 'inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-700'
        : 'inline-flex h-9 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-400';
    $html .= '<a href="?' . e(http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)]))) . '" class="' . $nextCls . '">Next ' . icon('chevron_right', 'h-4 w-4') . '</a>';

    return '<nav class="mt-5 flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">' . $html . '</nav>';
}

function valid_date(?string $value): bool
{
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && (bool)strtotime($value);
}

function old_value(string $key, $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function input_class(array $errors, string $key): string
{
    $base = 'w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:outline-none focus:ring-2 ';
    $ok   = 'border-slate-300 focus:border-blue-500 focus:ring-blue-100';
    $bad  = 'border-red-400 focus:border-red-500 focus:ring-red-100';

    return $base . (isset($errors[$key]) ? $bad : $ok);
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">' . icon('warn', 'h-3.5 w-3.5') . e($errors[$key]) . '</p>'
        : '';
}
