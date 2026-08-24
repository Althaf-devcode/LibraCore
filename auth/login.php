<?php

require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard/index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both your username/email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([':username' => $username, ':email' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int)$admin['id'];
            $_SESSION['admin_name']  = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];

            flash('success', 'Welcome back, ' . $admin['username'] . '! You are now logged in.');
            redirect('dashboard/index.php');
        }

        $error = 'Invalid credentials. Please check your username/email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraCore &middot; Admin Login</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%231d4ed8'/><text x='50' y='68' font-size='52' text-anchor='middle' fill='white' font-family='Georgia'>L</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</head>

<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-blue-950 px-4 py-10">

    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-sky-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute left-1/2 top-1/3 h-72 w-72 -translate-x-1/2 rounded-full bg-indigo-600/20 blur-3xl"></div>

    <div class="relative w-full max-w-md">

        <div class="mb-8 flex items-center justify-center gap-4">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-2xl shadow-blue-900/60 ring-1 ring-blue-400/40">
                <?= icon('logo', 'h-9 w-9') ?>
            </span>

            <div class="text-left">
                <h1 class="text-3xl font-extrabold tracking-tight text-white">LibraCore !</h1>
                <p class="mt-1 text-sm font-medium text-blue-300">Library Management System</p>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white p-8 shadow-2xl">

            <h2 class="text-lg font-bold text-blue-950">Admin Login</h2>
            <p class="mt-1 text-sm text-slate-500">Sign in to manage students, books and borrow records.</p>

            <?php if ($error !== ''): ?>
                <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
                    <?= icon('warn', 'h-5 w-5 shrink-0') ?>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php foreach (take_flashes() as $fl): ?>
                <div class="mt-5 flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm font-medium
                <?= $fl['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-700' ?>" role="alert">
                    <?= icon(flash_icon($fl['type']), 'h-5 w-5 shrink-0') ?>
                    <span><?= e($fl['message']) ?></span>
                </div>
            <?php endforeach; ?>

            <form method="post" action="<?= e(htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')) ?>" class="mt-6 space-y-5" data-validate novalidate>

                <div>
                    <label for="username" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Username or Email <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username"
                        placeholder="e.g. admin"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-11 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <button type="button" data-toggle-password="password" aria-label="Show password"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-blue-600">
                            <?= icon('eye', 'h-5 w-5') ?>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 active:scale-[.99]">
                    Sign in to LibraCore
                </button>
            </form>

        </div>

        <p class="mt-8 text-center text-xs text-blue-300/70">&copy; 2024 LibraCore &middot; Library Management System</p>
    </div>

    <script src="<?= url('assets/js/script.js') ?>"></script>
</body>

</html>