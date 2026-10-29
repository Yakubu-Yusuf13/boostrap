<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['admin']);
$connection = db();
$departments = $connection->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $departmentId = filter_var($_POST['department_id'] ?? '', FILTER_VALIDATE_INT);
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || strlen($name) > 150) {
        $errors[] = 'Enter the staff member’s full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!$departmentId) {
        $errors[] = 'Select the staff member’s department.';
    }
    if ($departmentId) {
        $departmentCheck = $connection->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        if (!$departmentCheck->fetch()) {
            $errors[] = 'Select a valid department.';
        }
    }
    if (strlen($password) < 12) {
        $errors[] = 'Use a temporary password of at least 12 characters.';
    }
    if (!$errors) {
        try {
            $create = $connection->prepare("INSERT INTO users (full_name, email, department_id, password_hash, role) VALUES (?, ?, ?, ?, 'department_admin')");
            $create->execute([$name, $email, $departmentId, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Department administrator account created.');
            header('Location: ' . url('admin/staff.php'));
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
$staff = $connection->query("SELECT u.full_name, u.email, u.created_at, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.role = 'department_admin' ORDER BY d.name, u.full_name")->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Department admins', 'admin', 'staff');
?>
<section class="staff-hero">
    <div>
        <span class="cases-hero-kicker">FEDERAL COLLEGE OF WILDLIFE MANAGEMENT</span>
        <h2>Department admin workspace</h2>
        <p>Connect support teams with the students and cases they serve.</p>
    </div>
</section>
<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h5>Create department admin</h5></div><div class="card-body">
            <p class="hint-text">Department admins can see and update cases routed to their assigned department only.</p>
            <?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="<?= e(url('admin/staff.php')) ?>">
                <?= csrf_field() ?>
                <div class="form-group"><label for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required maxlength="150"></div>
                <div class="form-group"><label for="email">Work email</label><input class="form-control" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required maxlength="190"></div>
                <div class="form-group"><label for="department_id">Department</label><select class="form-control" id="department_id" name="department_id" required><option value="">Choose department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) ($_POST['department_id'] ?? '') === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="password">Temporary password <span class="hint-text">(at least 12 characters)</span></label><input class="form-control" type="password" id="password" name="password" autocomplete="new-password" minlength="12" required></div>
                <button class="btn btn-primary btn-block" type="submit">Create department admin</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h5>Department admin accounts</h5></div><div class="card-body p-0">
            <?php if (!$staff): ?><div class="empty-state"><i class="feather icon-users"></i>No department admins have been added.</div>
            <?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Added</th></tr></thead><tbody><?php foreach ($staff as $member): ?><tr><td><?= e($member['full_name']) ?></td><td><?= e($member['email']) ?></td><td><?= e($member['department_name'] ?: '—') ?></td><td><?= e(date('M j, Y', strtotime($member['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </div></div>
    </div>
</div>
<?php page_footer('admin'); ?>
