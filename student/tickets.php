<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['student']);
$query = db()->prepare('SELECT t.id, t.reference_number, t.topic, t.subject, t.status, t.created_at, t.updated_at, d.name AS department_name FROM tickets t LEFT JOIN departments d ON d.id = t.department_id WHERE t.student_id = ? ORDER BY t.created_at DESC');
$query->execute([$user['id']]);
$tickets = $query->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('My complaints', 'student', 'tickets');
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"><h5>All complaints</h5><a class="btn btn-primary btn-sm" href="<?= e(url('student/new-ticket.php')) ?>"><i class="feather icon-plus"></i> New complaint</a></div>
    <div class="card-body p-0">
        <?php if (!$tickets): ?>
            <div class="empty-state"><i class="feather icon-inbox"></i>No complaints yet. <a href="<?= e(url('student/new-ticket.php')) ?>">Create one now</a>.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Reference</th><th>Subject / topic</th><th>Assigned department</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr><td><a class="ticket-reference" href="<?= e(url('student/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['reference_number']) ?></a></td><td><a class="ticket-subject" href="<?= e(url('student/ticket.php?id=' . (int) $ticket['id'])) ?>"><?= e($ticket['subject']) ?></a><br><span class="hint-text"><?= e($ticket['topic']) ?></span></td><td><?= e($ticket['department_name'] ?: 'Awaiting assignment') ?></td><td><?= status_badge($ticket['status']) ?></td><td><?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('track.php?ref=' . rawurlencode($ticket['reference_number']))) ?>">Track</a></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>
<?php page_footer('student'); ?>
