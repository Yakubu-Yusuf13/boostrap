<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = current_user();
if ($user) {
    header('Location: ' . url($user['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'));
    exit;
}

$reference = trim((string) ($_GET['ref'] ?? ''));
auth_header('Support when you need it');
?>
<div class="landing-mark"><span class="brand-mark">FC</span></div>
<figure class="landing-campus">
    <img src="<?= e(url('assets/images/fcwm-campus.png')) ?>" alt="Entrance gate of the Federal College of Wildlife Management">
    <figcaption>Welcome to Federal College of Wildlife Management</figcaption>
</figure>
<h2 class="landing-title">Your voice matters.<br><span>We’re here to help.</span></h2>
<p class="landing-copy">Submit a concern, request support from the right department, and keep track of every update with your personal reference number.</p>
<div class="landing-actions">
    <a class="btn btn-primary btn-block" href="<?= e(url('register.php')) ?>">Create student account</a>
    <a class="btn btn-outline-success btn-block" href="<?= e(url('login.php')) ?>">Sign in to your account</a>
</div>
<div class="landing-track">
    <div><i class="feather icon-search"></i><strong>Already have a reference?</strong></div>
    <p>Check the latest status of a submitted complaint.</p>
    <form class="track-form" action="<?= e(url('track.php')) ?>" method="get">
        <input class="form-control" name="ref" value="<?= e($reference) ?>" placeholder="FCWM-2026-..." aria-label="Ticket reference" required>
        <button class="btn btn-primary" type="submit">Track</button>
    </form>
</div>
<div class="auth-links"><span>Federal College of Wildlife Management</span><a href="<?= e(url('setup.php')) ?>">First-time setup</a></div>
<?php auth_footer(); ?>
