<?php
declare(strict_types=1);

function render_header(string $title, string $active = ''): void
{
    $user = current_user();
    $role = $user['role'] ?? 'guest';
    $roleName = match ($role) {
        'admin' => 'Administrator',
        'department' => 'Department Desk',
        'student' => 'Student Portal',
        default => 'Complaint Portal',
    };
    $links = match ($role) {
        'admin' => [
            ['index.php', 'Overview', 'dashboard'],
            ['tickets.php', 'All Complaints', 'tickets'],
            ['staff.php', 'Departments & Accounts', 'staff'],
        ],
        'department' => [
            ['index.php', 'Department Overview', 'dashboard'],
            ['tickets.php', 'Department Cases', 'tickets'],
        ],
        'student' => [
            ['index.php', 'My Dashboard', 'dashboard'],
            ['ticket_new.php', 'Submit a Complaint', 'new'],
            ['tickets.php', 'My Complaints', 'tickets'],
            ['track.php', 'Track a Reference', 'track'],
        ],
        default => [
            ['login.php', 'Sign in', 'login'],
            ['register.php', 'Create account', 'register'],
            ['track.php', 'Track a Reference', 'track'],
        ],
    };
    $flashMessage = take_flash();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | FCWM Student Support</title>
    <link rel="icon" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/complaints.css">
</head>
<body class="portal portal-<?= e($role) ?>">
<div class="portal-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark">F</span><span>FCWM<span class="brand-sub">STUDENT SUPPORT</span></span></a>
        <div class="sidebar-label"><?= e($roleName) ?></div>
        <nav class="side-nav" aria-label="Main navigation">
            <?php foreach ($links as [$href, $label, $key]): ?>
                <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>"><span class="nav-dot"></span><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="help-card"><strong>Need help?</strong><span>Submit a complaint and our support team will follow up.</span></div>
            <?php if ($user): ?><a class="signout-link" href="logout.php">Sign out <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <div><div class="eyebrow"><?= e($roleName) ?></div><h1><?= e($title) ?></h1></div>
            <div class="topbar-user">
                <?php if ($user): ?>
                    <span class="user-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span>
                    <span><strong><?= e($user['full_name']) ?></strong><small><?= e(ucfirst($role)) ?></small></span>
                <?php else: ?>
                    <a class="button button-outline" href="login.php">Sign in</a>
                <?php endif; ?>
            </div>
        </header>
        <section class="page-content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
            <?php endif; ?>
    <?php
}

function render_footer(): void
{
    $role = current_user()['role'] ?? 'guest';
    ?>
        </section>
        <footer class="page-footer"><span>Federal College of Wildlife Management</span><span><?= e(ucfirst($role)) ?> support portal &middot; <?= date('Y') ?></span></footer>
    </main>
</div>
</body>
</html>
    <?php
}
