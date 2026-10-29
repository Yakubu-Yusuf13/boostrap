<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$reference = strtoupper(trim((string) ($_GET['ref'] ?? $_POST['reference_number'] ?? '')));
$ticket = null;
$updates = [];
if ($reference !== '') {
    $query = db()->prepare('SELECT t.id, t.reference_number, t.topic, t.subject, t.status, t.created_at, t.updated_at, d.name AS department_name FROM tickets t LEFT JOIN departments d ON d.id = t.department_id WHERE t.reference_number = ? LIMIT 1');
    $query->execute([$reference]);
    $ticket = $query->fetch() ?: null;
    if ($ticket) {
        $history = db()->prepare('SELECT tu.status, tu.note, tu.created_at FROM ticket_updates tu WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC');
        $history->execute([$ticket['id']]);
        $updates = $history->fetchAll();
    }
}

auth_header('Track a complaint');
?>
<p class="auth-intro">Enter the reference number you received when you submitted your complaint.</p>
<form method="get" action="<?= e(url('track.php')) ?>" class="mb-4">
    <div class="form-group"><label for="ref">Complaint reference number</label><input class="form-control" id="ref" name="ref" value="<?= e($reference) ?>" placeholder="FCWM-2026-..." required></div>
    <button class="btn btn-primary btn-block" type="submit">Check status</button>
</form>
<?php if ($reference !== '' && !$ticket): ?>
    <div class="alert alert-warning" role="alert">No complaint was found with that reference number. Check it and try again.</div>
<?php elseif ($ticket): ?>
    <div class="tracking-card">
        <div class="tracking-heading"><span class="ticket-reference"><?= e($ticket['reference_number']) ?></span><?= status_badge($ticket['status']) ?></div>
        <h2><?= e($ticket['subject']) ?></h2>
        <p class="tracking-topic"><?= e($ticket['topic']) ?> · <?= e($ticket['department_name'] ?: 'Awaiting department assignment') ?></p>
        <dl class="detail-list">
            <div><dt>Current status</dt><dd><?= status_badge($ticket['status']) ?></dd></div>
            <div><dt>Submitted</dt><dd><?= e(date('M j, Y · g:i a', strtotime($ticket['created_at']))) ?></dd></div>
            <div><dt>Last updated</dt><dd><?= e(date('M j, Y · g:i a', strtotime($ticket['updated_at']))) ?></dd></div>
        </dl>
        <h3 class="tracking-history-title">Case updates</h3>
        <?php if ($updates): ?>
            <ul class="timeline">
                <?php foreach ($updates as $update): ?>
                    <li>
                        <strong><?= $update['status'] ? e(status_label($update['status'])) : 'Update' ?></strong>
                        <small><?= e(date('M j, Y · g:i a', strtotime($update['created_at']))) ?></small>
                        <p><?= e($update['note']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="hint-text mb-0">Your request has been received. There are no additional updates yet.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
<div class="auth-links"><a href="<?= e(url('index.php')) ?>">Back to portal</a><a href="<?= e(url('login.php')) ?>">Student sign in</a></div>
<?php auth_footer(); ?>
