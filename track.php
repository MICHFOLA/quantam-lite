<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$reference = strtoupper(trim((string) ($_GET['ref'] ?? $_POST['reference_no'] ?? '')));
$ticket = null;
if ($reference !== '') {
    $statement = $pdo->prepare('SELECT t.reference_no, t.topic, t.subject, t.description, t.status, t.created_at, t.updated_at, d.name AS department_name FROM tickets t LEFT JOIN departments d ON d.id = t.department_id WHERE t.reference_no = ?');
    $statement->execute([$reference]);
    $ticket = $statement->fetch() ?: null;
}

require_once __DIR__ . '/includes/layout.php';
render_header('Track a complaint', 'track');
?>
<div class="card-panel form-card">
    <h2>Track with your reference number</h2>
    <p class="text-muted">Enter the reference number you received after submitting your complaint.</p>
    <form method="get"><div class="form-group"><label for="ref">Reference number</label><input class="form-control" id="ref" name="ref" value="<?= e($reference) ?>" placeholder="FCWM-2026-XXXXXXXX" required></div><button class="button" type="submit">Check status</button></form>
</div>
<?php if ($reference !== ''): ?>
    <?php if (!$ticket): ?><div class="alert alert-warning">No complaint was found for that reference number. Check it and try again.</div>
    <?php else: ?>
        <div class="card-panel">
            <span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
            <h2 class="mt-3"><?= e($ticket['subject']) ?></h2>
            <div class="ticket-meta"><div><small>Reference</small><strong><?= e($ticket['reference_no']) ?></strong></div><div><small>Topic</small><strong><?= e($ticket['topic']) ?></strong></div><div><small>Department</small><strong><?= e($ticket['department_name'] ?? 'Not assigned') ?></strong></div></div>
            <p><strong>Submitted:</strong> <?= e(date('M j, Y', strtotime($ticket['created_at']))) ?> &nbsp; <strong>Last updated:</strong> <?= e(date('M j, Y', strtotime($ticket['updated_at']))) ?></p>
            <p class="text-muted mb-0">Sign in to view the complaint details and full conversation, or add an update.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php render_footer(); ?>
