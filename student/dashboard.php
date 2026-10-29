<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['student']);

$countsQuery = db()->prepare("SELECT status, COUNT(*) AS total FROM tickets WHERE student_id = ? GROUP BY status");
$countsQuery->execute([$user['id']]);
$counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
foreach ($countsQuery->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$recentQuery = db()->prepare('SELECT t.id, t.reference_number, t.subject, t.topic, t.status, t.created_at, d.name AS department_name FROM tickets t LEFT JOIN departments d ON d.id = t.department_id WHERE t.student_id = ? ORDER BY t.created_at DESC LIMIT 5');
$recentQuery->execute([$user['id']]);
$recent = $recentQuery->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('My dashboard', 'student', 'dashboard');
?>
<div class="welcome-panel">
    <div><h2>Welcome, <?= e(explode(' ', trim($user['full_name']))[0]) ?>.</h2><p>Tell us what you need help with. Your support requests and their progress will stay together here.</p></div>
    <a class="btn" href="<?= e(url('student/new-ticket.php')) ?>"><i class="feather icon-plus"></i> New complaint</a>
</div>
<div class="metric-grid">
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-inbox"></i></span><div><small>Total complaints</small><strong><?= array_sum($counts) ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-clock"></i></span><div><small>Open</small><strong><?= $counts['open'] ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-loader"></i></span><div><small>In progress</small><strong><?= $counts['in_progress'] ?></strong></div></div>
    <div class="metric-card"><span class="metric-icon"><i class="feather icon-check-circle"></i></span><div><small>Resolved</small><strong><?= $counts['resolved'] ?></strong></div></div>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"><h5>Recent complaints</h5><a class="btn btn-sm btn-outline-success" href="<?= e(url('student/tickets.php')) ?>">View all</a></div>
    <div class="card-body p-0">
        <?php if (!$recent): ?>
            <div class="empty-state"><i class="feather icon-inbox"></i>You haven’t submitted a complaint yet.<br><a href="<?= e(url('student/new-ticket.php')) ?>">Create your first support ticket</a></div>
        <?php else: ?>
            <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reference</th><th>Complaint</th><th>Department</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
                <?php foreach ($recent as $ticket): ?>
                    <tr><td><a class="ticket-reference" href="<?= e(url('student/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['reference_number']) ?></a></td><td><a class="ticket-subject" href="<?= e(url('student/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['subject']) ?></a></td><td><?= e($ticket['department_name'] ?: 'Unassigned') ?></td><td><?= status_badge($ticket['status']) ?></td><td><?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>
<?php page_footer('student'); ?>
