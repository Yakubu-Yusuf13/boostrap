<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['admin', 'department_admin']);

$where = '';
$parameters = [];
if ($user['role'] === 'department_admin') {
    $where = ' WHERE department_id = ?';
    $parameters[] = $user['department_id'];
}
$countsQuery = db()->prepare("SELECT status, COUNT(*) AS total FROM tickets{$where} GROUP BY status");
$countsQuery->execute($parameters);
$counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
foreach ($countsQuery->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$scopeQuery = db()->prepare("SELECT t.id, t.reference_number, t.subject, t.topic, t.status, t.created_at, u.full_name AS student_name, u.matric_number, d.name AS department_name FROM tickets t JOIN users u ON u.id = t.student_id LEFT JOIN departments d ON d.id = t.department_id{$where} ORDER BY t.created_at DESC LIMIT 8");
$scopeQuery->execute($parameters);
$recent = $scopeQuery->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Support dashboard', 'admin', 'dashboard');
?>
<div class="welcome-panel">
    <div><h2><?= $user['role'] === 'admin' ? 'Support operations at a glance.' : 'Your department case desk.' ?></h2><p><?= $user['role'] === 'admin' ? 'Review incoming complaints and route each case to the team best placed to help.' : 'Review and update complaints assigned to ' . e($user['department_name'] ?: 'your department') . '.' ?></p></div>
    <a class="btn" href="<?= e(url('admin/cases.php')) ?>"><i class="feather icon-inbox"></i> View cases</a>
</div>
<div class="metric-grid">
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-inbox"></i></span><div><small>Total cases</small><strong><?= array_sum($counts) ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-clock"></i></span><div><small>Open</small><strong><?= $counts['open'] ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-loader"></i></span><div><small>In progress</small><strong><?= $counts['in_progress'] ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-check-circle"></i></span><div><small>Resolved</small><strong><?= $counts['resolved'] + $counts['closed'] ?></strong></div></div>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"><h5>Recently submitted cases</h5><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/cases.php')) ?>">All cases</a></div>
    <div class="card-body p-0">
        <?php if (!$recent): ?><div class="empty-state"><i class="feather icon-inbox"></i>No cases to display.</div>
        <?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reference</th><th>Complaint</th><th>Student</th><th>Department</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
            <?php foreach ($recent as $ticket): ?><tr><td><a class="ticket-reference" href="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['reference_number']) ?></a></td><td><a class="ticket-subject" href="<?= e(url('admin/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['subject']) ?></a><br><span class="hint-text"><?= e($ticket['topic']) ?></span></td><td><?= e($ticket['student_name']) ?><br><span class="hint-text"><?= e($ticket['matric_number']) ?></span></td><td><?= e($ticket['department_name'] ?: 'Unassigned') ?></td><td><?= status_badge($ticket['status']) ?></td><td><?= e(date('M j', strtotime($ticket['created_at']))) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </div>
</div>
<?php page_footer('admin'); ?>
