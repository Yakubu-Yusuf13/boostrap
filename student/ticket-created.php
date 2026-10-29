<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['student']);
$reference = strtoupper(trim((string) ($_GET['ref'] ?? '')));
$lookup = db()->prepare('SELECT id, reference_number, subject FROM tickets WHERE reference_number = ? AND student_id = ? LIMIT 1');
$lookup->execute([$reference, $user['id']]);
$ticket = $lookup->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Complaint not found.');
}

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Complaint submitted', 'student', 'tickets');
?>
<div class="card text-center">
    <div class="card-body py-5">
        <span class="metric-icon mx-auto"><i class="feather icon-check"></i></span>
        <h2 class="mt-3">Your request is with us.</h2>
        <p class="hint-text">Keep this reference number to check for updates.</p>
        <div class="reference-highlight"><?= e($ticket['reference_number']) ?></div>
        <p class="ticket-subject mb-4"><?= e($ticket['subject']) ?></p>
        <a class="btn btn-primary" href="<?= e(url('track.php?ref=' . rawurlencode($ticket['reference_number']))) ?>">Track this complaint</a>
        <a class="btn btn-outline-secondary ml-2" href="<?= e(url('student/dashboard.php')) ?>">Go to dashboard</a>
    </div>
</div>
<?php page_footer('student'); ?>
