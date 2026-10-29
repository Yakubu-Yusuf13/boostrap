<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login(['student']);

$topics = [
    'Academic matters', 'Fees and bursary', 'ICT and e-learning',
    'Facilities and maintenance', 'Student affairs and welfare',
    'Safety and security', 'Records and registration', 'Other request',
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $topic = trim((string) ($_POST['topic'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    if (!in_array($topic, $topics, true)) {
        $errors[] = 'Select a valid complaint topic.';
    }
    if ($subject === '' || strlen($subject) > 180) {
        $errors[] = 'Enter a short subject (up to 180 characters).';
    }
    if (strlen($message) < 10 || strlen($message) > 10000) {
        $errors[] = 'Describe your request in at least 10 characters (up to 10,000 characters).';
    }
    if (!$errors) {
        $reference = 'FCWM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(8)));
        $connection = db();
        $connection->beginTransaction();
        try {
            $create = $connection->prepare("INSERT INTO tickets (reference_number, student_id, topic, subject, message) VALUES (?, ?, ?, ?, ?)");
            $create->execute([$reference, $user['id'], $topic, $subject, $message]);
            $ticketId = (int) $connection->lastInsertId();
            $update = $connection->prepare('INSERT INTO ticket_updates (ticket_id, actor_id, status, note) VALUES (?, ?, ?, ?)');
            $update->execute([$ticketId, $user['id'], 'open', 'Complaint submitted. The support team will review and route your request.']);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
        header('Location: ' . url('student/ticket-created.php?ref=' . rawurlencode($reference)));
        exit;
    }
}

require_once dirname(__DIR__) . '/includes/layout.php';
page_header('Create a complaint', 'student', 'new-ticket');
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5>Tell us how we can help</h5></div>
            <div class="card-body">
                <p class="hint-text mb-4">Choose the topic that best fits your request and include enough detail for the support team to help.</p>
                <?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form method="post" action="<?= e(url('student/new-ticket.php')) ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="topic">Complaint topic</label>
                        <select class="form-control" id="topic" name="topic" required>
                            <option value="">Select a topic</option>
                            <?php foreach ($topics as $topic): ?><option value="<?= e($topic) ?>" <?= ($_POST['topic'] ?? '') === $topic ? 'selected' : '' ?>><?= e($topic) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label for="subject">Subject</label><input class="form-control" id="subject" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" maxlength="180" placeholder="A short summary of your request" required></div>
                    <div class="form-group"><label for="message">Describe your request</label><textarea class="form-control" id="message" name="message" maxlength="10000" rows="6" placeholder="Share the details the support team will need…" required><?= e($_POST['message'] ?? '') ?></textarea><small class="form-text text-muted">Avoid including passwords or other sensitive account information.</small></div>
                    <button class="btn btn-primary" type="submit"><i class="feather icon-send"></i> Submit complaint</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-body"><span class="metric-icon"><i class="feather icon-info"></i></span><h5 class="mt-3">What happens next?</h5><p class="hint-text mb-0" style="line-height:1.8">You’ll receive a unique reference number after submission. The support team routes each complaint to the appropriate department. Use your reference to track progress at any time.</p></div></div>
        <div class="card"><div class="card-body"><h5>Student details</h5><dl class="detail-list mt-3"><div><dt>Matric number</dt><dd><?= e($user['matric_number']) ?></dd></div><div><dt>Department</dt><dd><?= e($user['department_name'] ?: 'Not assigned') ?></dd></div></dl></div></div>
    </div>
</div>
<?php page_footer('student'); ?>
