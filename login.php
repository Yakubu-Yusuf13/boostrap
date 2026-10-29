<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$existingUser = current_user();
if ($existingUser) {
    header('Location: ' . url($existingUser['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $query = db()->prepare('SELECT u.id, u.full_name, u.email, u.matric_number, u.department_id, u.password_hash, u.role, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.email = ? LIMIT 1');
    $query->execute([$email]);
    $user = $query->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: ' . url($user['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'));
        exit;
    }
    $error = 'The email or password you entered is incorrect.';
}

auth_header('Welcome back');
?>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('login.php')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="email">Email address</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="username" required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>
<div class="auth-links"><a href="<?= e(url('register.php')) ?>">Create student account</a><a href="<?= e(url('index.php')) ?>">Back to portal</a></div>
<?php auth_footer(); ?>
