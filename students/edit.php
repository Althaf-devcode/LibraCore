<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Edit Student';
$active     = 'students';

$id      = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt    = $pdo->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    flash('error', 'The requested student could not be found.');
    redirect('students/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'student_id' => trim($_POST['student_id'] ?? ''),
        'name'       => trim($_POST['name'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'phone'      => trim($_POST['phone'] ?? ''),
        'address'    => trim($_POST['address'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'year'       => trim($_POST['year'] ?? ''),
    ];

    if ($fields['student_id'] === '') {
        $errors['student_id'] = 'Student ID is required.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE student_id = :sid AND id <> :id');
        $stmt->execute([':sid' => $fields['student_id'], ':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors['student_id'] = 'This Student ID is already used by another student.';
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
            UPDATE students
            SET student_id = :student_id, name = :name, email = :email, phone = :phone,
                address = :address, department = :department, year = :year, updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':student_id' => $fields['student_id'],
            ':name'       => $fields['name'],
            ':email'      => $fields['email'],
            ':phone'      => $fields['phone'],
            ':address'    => $fields['address'],
            ':department' => $fields['department'],
            ':year'       => (int)$fields['year'],
            ':id'         => $id,
        ]);

        flash('success', 'Student "' . $fields['name'] . '" was updated successfully.');
        redirect('students/view.php?id=' . $id);
    }

    $student = array_merge($student, $fields);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-3xl">

    <div class="mb-6">
        <a href="<?= url('students/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-800 hover:underline">
            <?= icon('back', 'h-4 w-4') ?> Back to Students
        </a>
        <h2 class="mt-2 text-xl font-extrabold text-blue-950 sm:text-2xl">Edit Student</h2>
        <p class="mt-1 text-sm text-slate-500">Update the record for <span class="font-semibold text-blue-800"><?= e($student['name']) ?> (<?= e($student['student_id']) ?>)</span>.</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            <?= icon('warn', 'h-5 w-5 shrink-0') ?>
            <span>Please fix the highlighted fields below and try again.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($_SERVER['PHP_SELF']) . '?id=' . $id ?>" novalidate data-validate
          class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">

        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label for="student_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Student ID <span class="text-red-500">*</span></label>
                <input type="text" id="student_id" name="student_id" value="<?= old_value('student_id', $student['student_id']) ?>"
                       class="<?= input_class($errors, 'student_id') ?>">
                <?= field_error($errors, 'student_id') ?>
            </div>

            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Student Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="<?= old_value('name', $student['name']) ?>"
                       class="<?= input_class($errors, 'name') ?>">
                <?= field_error($errors, 'name') ?>
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= old_value('email', $student['email']) ?>"
                       class="<?= input_class($errors, 'email') ?>">
                <?= field_error($errors, 'email') ?>
            </div>

            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?= old_value('phone', $student['phone']) ?>"
                       class="<?= input_class($errors, 'phone') ?>">
                <?= field_error($errors, 'phone') ?>
            </div>

            <div class="md:col-span-2">
                <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Address <span class="text-red-500">*</span></label>
                <textarea id="address" name="address" rows="3"
                          class="<?= input_class($errors, 'address') ?> resize-y"><?= old_value('address', $student['address']) ?></textarea>
                <?= field_error($errors, 'address') ?>
            </div>

            <div>
                <label for="department" class="mb-1.5 block text-sm font-semibold text-slate-700">Department / Course <span class="text-red-500">*</span></label>
                <input type="text" id="department" name="department" list="departments" value="<?= old_value('department', $student['department']) ?>"
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
                    <?php $selYear = $_POST['year'] ?? $student['year']; ?>
                    <?php foreach ([1, 2, 3, 4, 5] as $y): ?>
                        <option value="<?= $y ?>" <?= (string)$selYear === (string)$y ? 'selected' : '' ?>>Year <?= $y ?></option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'year') ?>
            </div>
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a href="<?= url('students/view.php?id=' . $id) ?>"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 active:scale-[.99]">
                <?= icon('check', 'h-4 w-4') ?> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
