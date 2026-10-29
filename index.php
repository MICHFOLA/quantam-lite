<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (!current_user()) {
    render_header('Student support portal', 'dashboard');
    ?>
    <div class="welcome-panel"><h2>Student support, made simple.</h2><p>Raise a complaint, follow its progress, and communicate with the team responsible for helping you.</p></div>
    <div class="stat-grid">
        <div class="stat-card"><span>01 &middot; Tell us</span><strong>Submit</strong><span>Describe the issue and choose a topic.</span></div>
        <div class="stat-card"><span>02 &middot; Get routed</span><strong>Assigned</strong><span>Support requests go to the right team.</span></div>
        <div class="stat-card"><span>03 &middot; Stay informed</span><strong>Track</strong><span>Use your reference number to check updates.</span></div>
        <div class="stat-card"><span>04 &middot; Start here</span><strong>Welcome</strong><span><a href="register.php">Create an account</a> or <a href="login.php">sign in</a>.</span></div>
    </div>
    <div class="card-panel"><h2>Already have a complaint reference?</h2><p>Check the latest status without signing in.</p><a class="button" href="track.php">Track a complaint</a></div>
    <?php
    render_footer();
    exit;
}

$user = current_user();
$role = $user['role'];
$params = [];
$where = '';
if ($role === 'student') {
    $where = 'WHERE t.student_id = ?';
    $params[] = $user['id'];
} elseif ($role === 'department') {
    $where = 'WHERE t.department_id = ?';
    $params[] = $user['department_id'];
}
$countQuery = "SELECT COUNT(*) AS total,
    SUM(t.status IN ('Submitted', 'In Progress', 'Awaiting Student')) AS open_count,
    SUM(t.status = 'Resolved') AS resolved_count
    FROM tickets t {$where}";
$countStatement = $pdo->prepare($countQuery);
$countStatement->execute($params);
$counts = $countStatement->fetch();
$recentStatement = $pdo->prepare("SELECT t.id, t.reference_no, t.topic, t.subject, t.status, t.created_at, d.name AS department_name
    FROM tickets t LEFT JOIN departments d ON d.id = t.department_id {$where}
    ORDER BY t.updated_at DESC LIMIT 8");
$recentStatement->execute($params);
$recentTickets = $recentStatement->fetchAll();

render_header(match ($role) {
    'admin' => 'Administrator overview',
    'department' => 'Department overview',
    default => 'My dashboard',
}, 'dashboard');
?>
<div class="welcome-panel">
    <h2><?= $role === 'student' ? 'Hello, ' . e($user['full_name']) : ($role === 'admin' ? 'Support operations at a glance' : 'Your department case queue') ?></h2>
    <p><?= $role === 'student' ? 'Your support requests and their latest updates are all in one place.' : ($role === 'admin' ? 'Review new complaints and route each case to the right department.' : 'Review and update complaints assigned to your department.') ?></p>
</div>
<?php if ($role === 'student'): ?>
    <div class="stat-grid">
        <div class="stat-card"><span>Total complaints</span><strong><?= (int) ($counts['total'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Being handled</span><strong><?= (int) ($counts['open_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Resolved</span><strong><?= (int) ($counts['resolved_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Quick action</span><strong><a href="ticket_new.php" class="button">New complaint</a></strong></div>
    </div>
<?php else: ?>
    <div class="stat-grid">
        <div class="stat-card"><span><?= $role === 'admin' ? 'All complaints' : 'Assigned cases' ?></span><strong><?= (int) ($counts['total'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Open cases</span><strong><?= (int) ($counts['open_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Resolved cases</span><strong><?= (int) ($counts['resolved_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Queue</span><strong><a href="tickets.php">View cases &rarr;</a></strong></div>
    </div>
<?php endif; ?>
<div class="card-panel">
    <h2><?= $role === 'student' ? 'Recent complaints' : 'Recently updated cases' ?></h2>
    <?php if (!$recentTickets): ?>
        <div class="empty-state">No complaints to show yet. <?= $role === 'student' ? '<a href="ticket_new.php">Submit your first complaint</a>.' : '' ?></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Reference</th><th>Issue</th><th>Department</th><th>Status</th><th>Created</th></tr></thead>
            <tbody><?php foreach ($recentTickets as $ticket): ?>
                <tr>
                    <td><a href="ticket_view.php?id=<?= (int) $ticket['id'] ?>"><?= e($ticket['reference_no']) ?></a></td>
                    <td><?= e($ticket['subject']) ?><small class="d-block text-muted"><?= e($ticket['topic']) ?></small></td>
                    <td><?= e($ticket['department_name'] ?? 'Not assigned') ?></td>
                    <td><span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></td>
                    <td><?= e(date('M j, Y', strtotime($ticket['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
