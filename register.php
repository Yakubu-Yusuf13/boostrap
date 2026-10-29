<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    header('Location: ' . url(current_user()['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'));
    exit;
}

$errors = [];
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $matric = strtoupper(trim((string) ($_POST['matric_number'] ?? '')));
    $departmentId = filter_var($_POST['department_id'] ?? '', FILTER_VALIDATE_INT);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || strlen($name) > 150) {
        $errors[] = 'Enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($matric === '' || strlen($matric) > 50) {
        $errors[] = 'Enter your matric number.';
    }
    if (!$departmentId) {
        $errors[] = 'Select your department.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Your password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        $departmentCheck = db()->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        if (!$departmentCheck->fetch()) {
            $errors[] = 'Select a valid department.';
        } else {
            try {
                $create = db()->prepare('INSERT INTO users (full_name, email, matric_number, department_id, password_hash) VALUES (?, ?, ?, ?, ?)');
                $create->execute([$name, $email, $matric, $departmentId, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', 'Your student account is ready. Sign in to submit a support request.');
                header('Location: ' . url('login.php'));
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'That email address or matric number is already registered.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

auth_header('Create your student account');
?>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" action="<?= e(url('register.php')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="full_name">Full name</label>
        <input class="form-control" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" autocomplete="name" required maxlength="150">
    </div>
    <div class="form-group">
        <label for="email">Email address</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required maxlength="190">
    </div>
    <div class="form-group">
        <label for="matric_number">Matric number</label>
        <input class="form-control" id="matric_number" name="matric_number" value="<?= e($_POST['matric_number'] ?? '') ?>" autocomplete="off" required maxlength="50">
    </div>
    <div class="form-group">
        <label for="department_id">Department</label>
        <select class="form-control" id="department_id" name="department_id" required>
            <option value="">Select your department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= (int) $department['id'] ?>" <?= (string) ($_POST['department_id'] ?? '') === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="password">Password <span class="hint-text">(at least 8 characters)</span></label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm password</label>
        <input class="form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
</form>
<div class="auth-links"><span>Already registered?</span><a href="<?= e(url('login.php')) ?>">Sign in</a></div>
<?php auth_footer(); ?>
