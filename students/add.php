<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Add Student';
$active     = 'students';

$errors = [];
$fields = ['student_id' => '', 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'department' => '', 'year' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $default) {
        $fields[$key] = trim($_POST[$key] ?? $default);
    }

    if ($fields['student_id'] === '') {
        $errors['student_id'] = 'Student ID is required.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE student_id = :sid');
        $stmt->execute([':sid' => $fields['student_id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors['student_id'] = 'This Student ID is already registered.';
        }
    }

    if ($fields['name'] === '') {
        $errors['name'] = 'Student name is required.';
    } elseif (mb_strlen($fields['name']) > 100) {
        $errors['name'] = 'Name must be 100 characters or fewer.';
    }

    if ($fields['email'] === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($fields['phone'] === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\-\s().]{7,20}$/', $fields['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number (7-20 digits).';
    }

    if ($fields['address'] === '') {
        $errors['address'] = 'Address is required.';
    }

    if ($fields['department'] === '') {
        $errors['department'] = 'Department / course is required.';
    }

    if (!in_array($fields['year'], ['1', '2', '3', '4', '5'], true)) {
        $errors['year'] = 'Please select a valid year.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('
            INSERT INTO students (student_id, name, email, phone, address, department, year, created_at, updated_at)
            VALUES (:student_id, :name, :email, :phone, :address, :department, :year, NOW(), NOW())
        ');
        $stmt->execute([
            ':student_id' => $fields['student_id'],
            ':name'       => $fields['name'],
            ':email'      => $fields['email'],
            ':phone'      => $fields['phone'],
            ':address'    => $fields['address'],
            ':department' => $fields['department'],
            ':year'       => (int)$fields['year'],
        ]);

        flash('success', 'Student "' . $fields['name'] . '" (' . $fields['student_id'] . ') was added successfully.');
        redirect('students/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-3xl">

    <div class="mb-6">
        <a href="<?= url('students/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
            <?= icon('back', 'h-4 w-4') ?> Back to Students
        </a>
        <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Add New Student</h2>
        <p class="mt-1 text-sm text-slate-500">Fill in the details below to register a new library member.</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>Please fix the highlighted fields below and try again.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>" novalidate data-validate
          class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label for="student_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Student ID <span class="text-red-500">*</span></label>
                <input type="text" id="student_id" name="student_id" value="<?= old_value('student_id', $fields['student_id']) ?>" placeholder="e.g. STU-2026-001"
                       class="<?= input_class($errors, 'student_id') ?>">
                <?= field_error($errors, 'student_id') ?>
            </div>

            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Student Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="<?= old_value('name', $fields['name']) ?>" placeholder="Full name"
                       class="<?= input_class($errors, 'name') ?>">
                <?= field_error($errors, 'name') ?>
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= old_value('email', $fields['email']) ?>" placeholder="student@university.edu"
                       class="<?= input_class($errors, 'email') ?>">
                <?= field_error($errors, 'email') ?>
            </div>

            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?= old_value('phone', $fields['phone']) ?>" placeholder="+91 98765 43210"
                       class="<?= input_class($errors, 'phone') ?>">
                <?= field_error($errors, 'phone') ?>
            </div>

            <div class="md:col-span-2">
                <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Address <span class="text-red-500">*</span></label>
                <textarea id="address" name="address" rows="3" placeholder="Street, city, state"
                          class="<?= input_class($errors, 'address') ?> resize-y"><?= old_value('address', $fields['address']) ?></textarea>
                <?= field_error($errors, 'address') ?>
            </div>

            <div>
                <label for="department" class="mb-1.5 block text-sm font-semibold text-slate-700">Department / Course <span class="text-red-500">*</span></label>
                <input type="text" id="department" name="department" list="departments" value="<?= old_value('department', $fields['department']) ?>" placeholder="e.g. Computer Science"
                       class="<?= input_class($errors, 'department') ?>">
                <datalist id="departments">
                    <option value="Computer Science"></option>
                    <option value="Information Technology"></option>
                    <option value="Electronics"></option>
                    <option value="Electrical Engineering"></option>
                    <option value="Mechanical Engineering"></option>
                    <option value="Business Administration"></option>
                    <option value="Mathematics"></option>
                    <option value="Physics"></option>
                </datalist>
                <?= field_error($errors, 'department') ?>
            </div>

            <div>
                <label for="year" class="mb-1.5 block text-sm font-semibold text-slate-700">Year <span class="text-red-500">*</span></label>
                <select id="year" name="year" class="<?= input_class($errors, 'year') ?>">
                    <option value="">Select year</option>
                    <?php $selYear = $_POST['year'] ?? $fields['year']; ?>
                    <?php foreach ([1, 2, 3, 4, 5] as $y): ?>
                        <option value="<?= $y ?>" <?= (string)$selYear === (string)$y ? 'selected' : '' ?>>Year <?= $y ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'year') ?>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-400">Fields marked with <span class="font-bold text-red-500">*</span> are required. The date added is recorded automatically.</p>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="<?= url('students/index.php') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 active:scale-[.99]">
                <?= icon('plus', 'h-4 w-4') ?> Save Student
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
