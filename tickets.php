<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student', 'admin', 'department');

$user = current_user();
$where = '';
$params = [];
if ($user['role'] === 'student') {
    $where = 'WHERE t.student_id = ?';
    $params[] = $user['id'];
} elseif ($user['role'] === 'department') {
    $where = 'WHERE t.department_id = ?';
    $params[] = $user['department_id'];
}
$statement = $pdo->prepare("SELECT t.id, t.reference_no, t.topic, t.subject, t.status, t.created_at, t.updated_at,
    u.full_name AS student_name, u.matric_no, d.name AS department_name
    FROM tickets t JOIN users u ON u.id = t.student_id
    LEFT JOIN departments d ON d.id = t.department_id {$where}
    ORDER BY t.updated_at DESC");
$statement->execute($params);
$tickets = $statement->fetchAll();

require_once __DIR__ . '/includes/layout.php';
render_header(match ($user['role']) {
    'admin' => 'All complaints',
    'department' => 'Department cases',
    default => 'My complaints',
}, 'tickets');
?>
<div class="card-panel">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0"><?= $user['role'] === 'student' ? 'Your requests' : 'Complaint queue' ?></h2><?php if ($user['role'] === 'student'): ?><a class="button" href="ticket_new.php">New complaint</a><?php endif; ?></div>
    <?php if (!$tickets): ?><div class="empty-state">There are no complaints in this list.</div><?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Reference</th><?php if ($user['role'] !== 'student'): ?><th>Student</th><th>Matric no.</th><?php endif; ?><th>Issue</th><th>Department</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody><?php foreach ($tickets as $ticket): ?><tr>
            <td><a href="ticket_view.php?id=<?= (int) $ticket['id'] ?>"><?= e($ticket['reference_no']) ?></a></td>
            <?php if ($user['role'] !== 'student'): ?><td><?= e($ticket['student_name']) ?></td><td><?= e($ticket['matric_no']) ?></td><?php endif; ?>
            <td><?= e($ticket['subject']) ?><small class="d-block text-muted"><?= e($ticket['topic']) ?></small></td>
            <td><?= e($ticket['department_name'] ?? 'Unassigned') ?></td>
            <td><span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></td>
            <td><?= e(date('M j, Y', strtotime($ticket['updated_at']))) ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</div>
<?php render_footer(); ?>
