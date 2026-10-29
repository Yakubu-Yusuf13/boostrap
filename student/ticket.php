<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['student']);
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$query = db()->prepare('SELECT t.*, d.name AS department_name FROM tickets t LEFT JOIN departments d ON d.id = t.department_id WHERE t.id = ? AND t.student_id = ? LIMIT 1');
$query->execute([$id ?: 0, $user['id']]);
$ticket = $query->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Complaint not found.');
}
$history = db()->prepare('SELECT tu.status, tu.note, tu.created_at, u.full_name AS actor_name FROM ticket_updates tu LEFT JOIN users u ON u.id = tu.actor_id WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC');
$history->execute([$ticket['id']]);
$updates = $history->fetchAll();

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Complaint details', 'student', 'tickets');
?>
<p class="mb-3"><a class="hint-text" href="<?= e(url('student/tickets.php')) ?>"><i class="feather icon-arrow-left"></i> Back to my complaints</a></p>
<div class="detail-grid">
    <div class="card"><div class="card-header d-flex align-items-center justify-content-between"><h5><?= e($ticket['subject']) ?></h5><?= status_badge($ticket['status']) ?></div><div class="card-body">
        <p class="ticket-reference mb-3"><?= e($ticket['reference_number']) ?></p>
        <dl class="detail-list"><div><dt>Topic</dt><dd><?= e($ticket['topic']) ?></dd></div><div><dt>Department</dt><dd><?= e($ticket['department_name'] ?: 'Awaiting assignment') ?></dd></div><div><dt>Submitted</dt><dd><?= e(date('M j, Y · g:i a', strtotime($ticket['created_at']))) ?></dd></div></dl>
        <h6 class="mt-4 mb-2">Your request</h6><div class="ticket-message"><?= e($ticket['message']) ?></div>
    </div></div>
    <div class="card"><div class="card-header"><h5>Case updates</h5></div><div class="card-body">
        <?php if ($updates): ?><ul class="timeline"><?php foreach ($updates as $update): ?><li><strong><?= $update['status'] ? e(status_label($update['status'])) : 'Update' ?></strong><small><?= e(date('M j, Y · g:i a', strtotime($update['created_at']))) ?><?= $update['actor_name'] ? ' · ' . e($update['actor_name']) : '' ?></small><p><?= e($update['note']) ?></p></li><?php endforeach; ?></ul><?php else: ?><p class="hint-text mb-0">No updates yet.</p><?php endif; ?>
        <a class="btn btn-outline-success btn-sm mt-4" href="<?= e(url('track.php?ref=' . rawurlencode($ticket['reference_number']))) ?>">Public tracking page</a>
    </div></div>
</div>
<?php page_footer('student'); ?>
