<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['admin', 'department_admin']);
$validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
$status = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$conditions = [];
$parameters = [];
if ($user['role'] === 'department_admin') {
    $conditions[] = 't.department_id = ?';
    $parameters[] = $user['department_id'];
}
if (in_array($status, $validStatuses, true)) {
    $conditions[] = 't.status = ?';
    $parameters[] = $status;
}
if ($search !== '') {
    $conditions[] = '(t.reference_number LIKE ? OR t.subject LIKE ? OR u.full_name LIKE ? OR u.matric_number LIKE ?)';
    $term = '%' . $search . '%';
    array_push($parameters, $term, $term, $term, $term);
}
$sql = 'SELECT t.id, t.reference_number, t.topic, t.subject, t.status, t.created_at, u.full_name AS student_name, u.matric_number, d.name AS department_name FROM tickets t JOIN users u ON u.id = t.student_id LEFT JOIN departments d ON d.id = t.department_id';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY CASE t.status WHEN "open" THEN 1 WHEN "in_progress" THEN 2 WHEN "resolved" THEN 3 ELSE 4 END, t.created_at DESC';
$query = db()->prepare($sql);
$query->execute($parameters);
$tickets = $query->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Department cases', 'admin', 'cases');
?>
<section class="cases-hero">
    <div>
        <span class="cases-hero-kicker">FEDERAL COLLEGE OF WILDLIFE MANAGEMENT</span>
        <h2>Department cases</h2>
        <p>Every request deserves a thoughtful response. Review, route, and resolve student complaints here.</p>
    </div>
</section>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"><h5><?= $user['role'] === 'admin' ? 'All support cases' : e($user['department_name'] ?: 'Department') . ' cases' ?></h5><span class="hint-text"><?= count($tickets) ?> case<?= count($tickets) === 1 ? '' : 's' ?></span></div>
    <div class="card-body border-bottom">
        <form class="row align-items-end" method="get" action="<?= e(url('admin/cases.php')) ?>">
            <div class="col-md-6 form-group mb-md-0"><label for="q">Search cases</label><input class="form-control" id="q" name="q" value="<?= e($search) ?>" placeholder="Reference, subject, student or matric number"></div>
            <div class="col-md-3 form-group mb-md-0"><label for="status">Status</label><select class="form-control" id="status" name="status"><option value="">All statuses</option><?php foreach ($validStatuses as $option): ?><option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(status_label($option)) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3 mt-2 mt-md-0"><button class="btn btn-primary" type="submit">Filter cases</button> <a class="btn btn-light" href="<?= e(url('admin/cases.php')) ?>">Reset</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (!$tickets): ?><div class="empty-state"><i class="feather icon-search"></i>No cases match your filters.</div>
        <?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reference</th><th>Complaint</th><th>Student</th><th>Department</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>
            <?php foreach ($tickets as $ticket): ?><tr><td><a class="ticket-reference" href="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['reference_number']) ?></a></td><td><a class="ticket-subject" href="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['subject']) ?></a><br><span class="hint-text"><?= e($ticket['topic']) ?></span></td><td><?= e($ticket['student_name']) ?><br><span class="hint-text"><?= e($ticket['matric_number']) ?></span></td><td><?= e($ticket['department_name'] ?: 'Unassigned') ?></td><td><?= status_badge($ticket['status']) ?></td><td><?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>">Open</a></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</div>
<?php page_footer('admin'); ?>
