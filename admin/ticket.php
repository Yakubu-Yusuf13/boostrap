<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['admin', 'department_admin']);
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$connection = db();
$departments = $connection->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$statuses = ['open', 'in_progress', 'resolved', 'closed'];
$errors = [];

$scope = $user['role'] === 'department_admin' ? ' AND t.department_id = ?' : '';
$lookup = $connection->prepare('SELECT t.*, u.full_name AS student_name, u.email AS student_email, u.matric_number, sd.name AS student_department, d.name AS department_name FROM tickets t JOIN users u ON u.id = t.student_id LEFT JOIN departments sd ON sd.id = u.department_id LEFT JOIN departments d ON d.id = t.department_id WHERE t.id = ?' . $scope . ' LIMIT 1');
$lookup->execute($user['role'] === 'department_admin' ? [$id ?: 0, $user['department_id']] : [$id ?: 0]);
$ticket = $lookup->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Case not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $note = trim((string) ($_POST['note'] ?? ''));
    if ($action === 'update') {
        $newStatus = (string) ($_POST['status'] ?? '');
        if (!in_array($newStatus, $statuses, true)) {
            $errors[] = 'Select a valid case status.';
        }
        if (strlen($note) < 3 || strlen($note) > 5000) {
            $errors[] = 'Write a case update of at least 3 characters (up to 5,000 characters).';
        }
        if (!$errors) {
            $connection->beginTransaction();
            try {
                $change = $connection->prepare('UPDATE tickets SET status = ? WHERE id = ?');
                $change->execute([$newStatus, $ticket['id']]);
                $add = $connection->prepare('INSERT INTO ticket_updates (ticket_id, actor_id, status, note) VALUES (?, ?, ?, ?)');
                $add->execute([$ticket['id'], $user['id'], $newStatus, $note]);
                $connection->commit();
            } catch (Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }
            flash('success', 'Case status and student update saved.');
            header('Location: ' . url('admin/ticket.php?id=' . (int) $ticket['id']));
            exit;
        }
    } elseif ($action === 'route' && $user['role'] === 'admin') {
        $departmentId = filter_var($_POST['department_id'] ?? '', FILTER_VALIDATE_INT);
        $departmentQuery = $connection->prepare('SELECT id, name FROM departments WHERE id = ?');
        $departmentQuery->execute([$departmentId ?: 0]);
        $newDepartment = $departmentQuery->fetch();
        if (!$newDepartment) {
            $errors[] = 'Select a valid department.';
        }
        if (strlen($note) > 5000) {
            $errors[] = 'Keep the routing note under 5,000 characters.';
        }
        if (!$errors) {
            $routeNote = $note !== '' ? $note : 'Your complaint has been routed to ' . $newDepartment['name'] . '.';
            $connection->beginTransaction();
            try {
                $route = $connection->prepare('UPDATE tickets SET department_id = ? WHERE id = ?');
                $route->execute([$newDepartment['id'], $ticket['id']]);
                $add = $connection->prepare('INSERT INTO ticket_updates (ticket_id, actor_id, status, note) VALUES (?, ?, NULL, ?)');
                $add->execute([$ticket['id'], $user['id'], $routeNote]);
                $connection->commit();
            } catch (Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }
            flash('success', 'Case routed to ' . $newDepartment['name'] . '.');
            header('Location: ' . url('admin/ticket.php?id=' . (int) $ticket['id']));
            exit;
        }
    } else {
        http_response_code(403);
        exit('You do not have permission to perform this action.');
    }
}

$history = $connection->prepare('SELECT tu.status, tu.note, tu.created_at, u.full_name AS actor_name FROM ticket_updates tu LEFT JOIN users u ON u.id = tu.actor_id WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC');
$history->execute([$ticket['id']]);
$updates = $history->fetchAll();
require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Case details', 'admin', 'cases');
?>
<p class="mb-3"><a class="hint-text" href="<?= e(url('admin/cases.php')) ?>"><i class="feather icon-arrow-left"></i> Back to cases</a></p>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="detail-grid">
    <div>
        <div class="card"><div class="card-header d-flex align-items-center justify-content-between"><h5><?= e($ticket['subject']) ?></h5><?= status_badge($ticket['status']) ?></div><div class="card-body">
            <p class="ticket-reference mb-3"><?= e($ticket['reference_number']) ?></p>
            <dl class="detail-list"><div><dt>Topic</dt><dd><?= e($ticket['topic']) ?></dd></div><div><dt>Department</dt><dd><?= e($ticket['department_name'] ?: 'Unassigned') ?></dd></div><div><dt>Student</dt><dd><?= e($ticket['student_name']) ?></dd></div><div><dt>Matric number</dt><dd><?= e($ticket['matric_number']) ?></dd></div><div><dt>Student department</dt><dd><?= e($ticket['student_department'] ?: 'Not set') ?></dd></div><div><dt>Submitted</dt><dd><?= e(date('M j, Y · g:i a', strtotime($ticket['created_at']))) ?></dd></div></dl>
            <h6 class="mt-4 mb-2">Student request</h6><div class="ticket-message"><?= e($ticket['message']) ?></div>
        </div></div>
        <div class="card"><div class="card-header"><h5>Student-facing case updates</h5></div><div class="card-body">
            <?php if ($updates): ?><ul class="timeline"><?php foreach ($updates as $update): ?><li><strong><?= $update['status'] ? e(status_label($update['status'])) : 'Update' ?></strong><small><?= e(date('M j, Y · g:i a', strtotime($update['created_at']))) ?><?= $update['actor_name'] ? ' · ' . e($update['actor_name']) : '' ?></small><p><?= e($update['note']) ?></p></li><?php endforeach; ?></ul><?php else: ?><p class="hint-text mb-0">No updates yet.</p><?php endif; ?>
        </div></div>
    </div>
    <div>
        <?php if ($user['role'] === 'admin'): ?>
        <div class="card"><div class="card-header"><h5>Route this case</h5></div><div class="card-body">
            <p class="hint-text">Assign the complaint to the department responsible for handling it.</p>
            <form method="post" action="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="route">
                <div class="form-group"><label for="department_id">Department</label><select class="form-control" id="department_id" name="department_id" required><option value="">Choose department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) $ticket['department_id'] === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="route_note">Note for the student <span class="hint-text">(optional)</span></label><textarea class="form-control" id="route_note" name="note" rows="3" maxlength="5000" placeholder="Add context or next steps"></textarea></div>
                <button class="btn btn-primary btn-block" type="submit">Route case</button>
            </form>
        </div></div>
        <?php endif; ?>
        <div class="card"><div class="card-header"><h5>Update the student</h5></div><div class="card-body">
            <form method="post" action="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="update">
                <div class="form-group"><label for="status">Case status</label><select class="form-control" id="status" name="status" required><?php foreach ($statuses as $option): ?><option value="<?= e($option) ?>" <?= $ticket['status'] === $option ? 'selected' : '' ?>><?= e(status_label($option)) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="note">Update message</label><textarea class="form-control" id="note" name="note" rows="5" maxlength="5000" placeholder="This message will be visible to the student." required></textarea></div>
                <button class="btn btn-primary btn-block" type="submit">Save case update</button>
            </form>
        </div></div>
    </div>
</div>
<?php page_footer('admin'); ?>
