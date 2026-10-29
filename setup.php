<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$adminCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','department_admin')")->fetchColumn();
if ($adminCount > 0) {
    http_response_code(404);
    exit('Initial setup is already complete.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if ($name === '' || strlen($name) > 150) {
        $errors[] = 'Enter the administrator’s full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 12) {
        $errors[] = 'Use a password of at least 12 characters for the administrator account.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The password confirmation does not match.';
    }
    if (!$errors) {
        try {
            $create = db()->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $create->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Administrator account created. Sign in to manage support cases.');
            header('Location: ' . url('login.php'));
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'That email address is already registered.';
            } else {
                throw $exception;
            }
        }
    }
}

auth_header('Set up the support administrator');
?>
<div class="alert alert-info" role="status">This one-time page is available only until the first administrator account is created.</div>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= e(url('setup.php')) ?>">
    <?= csrf_field() ?>
    <div class="form-group"><label for="full_name">Administrator full name</label><input class="form-control" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required maxlength="150"></div>
    <div class="form-group"><label for="email">Administrator email</label><input class="form-control" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required maxlength="190"></div>
    <div class="form-group"><label for="password">Password <span class="hint-text">(at least 12 characters)</span></label><input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required></div>
    <div class="form-group"><label for="confirm_password">Confirm password</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="12" autocomplete="new-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">Create administrator</button>
</form>
<div class="auth-links"><a href="<?= e(url('index.php')) ?>">Back to portal</a></div>
<?php auth_footer(); ?>
