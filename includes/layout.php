<?php
function page_header(string $title, string $area, string $active = ''): void
{
    $user = current_user();
    $isAdmin = $area === 'admin';
    $name = $user['full_name'] ?? 'Portal user';
    $initial = strtoupper(substr($name, 0, 1));
    $brandMark = $isAdmin
        ? '<img class="brand-mark brand-mark-seal" src="' . e(url('assets/images/fcwm-seal.png')) . '" alt="Federal College of Wildlife Management seal">'
        : '<span class="brand-mark">FC</span>';
    $links = $isAdmin ? [
        ['dashboard', 'admin/dashboard.php', 'Dashboard', 'icon-home'],
        ['cases', 'admin/cases.php', 'Department cases', 'icon-inbox'],
    ] : [
        ['dashboard', 'student/dashboard.php', 'My dashboard', 'icon-home'],
        ['new-ticket', 'student/new-ticket.php', 'Create a ticket', 'icon-plus-circle'],
        ['tickets', 'student/tickets.php', 'My complaints', 'icon-file-text'],
        ['track', 'track.php', 'Track a complaint', 'icon-search'],
    ];
    if ($isAdmin && $user && $user['role'] === 'admin') {
        $links[] = ['staff', 'admin/staff.php', 'Department admins', 'icon-users'];
    }
    $areaTitle = $isAdmin ? 'Support administration' : 'Student support';
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | FCWM Support Portal</title>
    <link rel="icon" href="<?= e(url('dist/assets/images/favicon.ico')) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= e(url('dist/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/portal.css')) ?>">
</head>
<body class="portal-body <?= $isAdmin ? 'admin-area' : 'student-area' ?>">
<div class="portal-shell">
    <aside class="portal-sidebar">
        <a class="portal-brand" href="<?= e(url($isAdmin ? 'admin/dashboard.php' : 'student/dashboard.php')) ?>">
            <?= $brandMark ?>
            <span><strong>FCWM</strong><small>Support Portal</small></span>
        </a>
        <div class="sidebar-profile">
            <span class="profile-avatar"><?= e($initial) ?></span>
            <span><strong><?= e($name) ?></strong><small><?= $isAdmin ? e(status_label($user['role'])) : 'Student account' ?></small></span>
        </div>
        <nav class="portal-nav" aria-label="Main navigation">
            <span class="nav-caption"><?= $isAdmin ? 'CASE MANAGEMENT' : 'STUDENT MENU' ?></span>
            <?php foreach ($links as $link): ?>
                <a class="<?= $active === $link[0] ? 'active' : '' ?>" href="<?= e(url($link[1])) ?>">
                    <i class="feather <?= e($link[3]) ?>"></i><span><?= e($link[2]) ?></span>
                </a>
            <?php endforeach; ?>
            <span class="nav-caption nav-caption-bottom">ACCOUNT</span>
            <a href="<?= e(url('logout.php')) ?>"><i class="feather icon-log-out"></i><span>Sign out</span></a>
        </nav>
        <div class="sidebar-note">
            <span class="note-icon"><i class="feather icon-life-buoy"></i></span>
            <strong><?= $isAdmin ? 'Here to help' : 'Need assistance?' ?></strong>
            <p><?= $isAdmin ? 'Keep students informed with clear case updates.' : 'Submit a ticket and follow every update in one place.' ?></p>
        </div>
        <div class="sidebar-footer">FEDERAL COLLEGE OF WILDLIFE MANAGEMENT</div>
    </aside>
    <main class="portal-main">
        <header class="portal-header">
            <div class="header-title">
                <span><?= $areaTitle ?></span>
                <h1><?= e($title) ?></h1>
            </div>
            <div class="header-user">
                <?php if ($isAdmin): ?>
                    <img class="header-avatar header-avatar-seal" src="<?= e(url('assets/images/fcwm-seal.png')) ?>" alt="College seal">
                <?php else: ?>
                    <span class="header-avatar"><?= e($initial) ?></span>
                <?php endif; ?>
                <span><strong><?= e($name) ?></strong><small><?= $isAdmin ? 'Support team' : 'Student' ?></small></span>
                <a class="header-signout" href="<?= e(url('logout.php')) ?>" aria-label="Sign out"><i class="feather icon-log-out"></i></a>
            </div>
        </header>
        <section class="portal-content">
            <?php show_flash(); ?>
<?php
}

function page_footer(string $area): void
{
    ?>
        </section>
        <footer class="portal-footer">
            <span><?= $area === 'admin' ? 'FCWM Support Desk · Staff workspace' : 'FCWM Student Services · Student workspace' ?></span>
            <span>Federal College of Wildlife Management</span>
        </footer>
    </main>
</div>
<script src="<?= e(url('dist/assets/js/vendor-all.min.js')) ?>"></script>
<script src="<?= e(url('dist/assets/js/plugins/bootstrap.min.js')) ?>"></script>
<script src="<?= e(url('dist/assets/js/ripple.js')) ?>"></script>
<script src="<?= e(url('dist/assets/js/pcoded.min.js')) ?>"></script>
</body>
</html>
<?php
}

function auth_header(string $title): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | FCWM Support Portal</title>
    <link rel="icon" href="<?= e(url('dist/assets/images/favicon.ico')) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= e(url('dist/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/portal.css')) ?>">
</head>
<body class="auth-page">
<div class="auth-brand"><span class="brand-mark">FC</span><span><strong>FCWM</strong><small>Federal College of Wildlife Management</small></span></div>
<main class="auth-card">
    <div class="auth-kicker">STUDENT &amp; SUPPORT SERVICES</div>
    <h1><?= e($title) ?></h1>
    <p class="auth-intro">The official student complaint and support portal.</p>
    <?php show_flash(); ?>
<?php
}

function auth_footer(): void
{
    ?>
</main>
<footer class="auth-footer">Federal College of Wildlife Management · Student Support</footer>
</body>
</html>
<?php
}
