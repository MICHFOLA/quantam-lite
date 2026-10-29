<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student', 'admin', 'department');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Complaint not found.');
}
$statement = $pdo->prepare('SELECT t.*, u.full_name AS student_name, u.email AS student_email, u.matric_no, d.name AS department_name FROM tickets t JOIN users u ON u.id = t.student_id LEFT JOIN departments d ON d.id = t.department_id WHERE t.id = ?');
$statement->execute([$id]);
$ticket = $statement->fetch();
$user = current_user();
$allowed = $ticket && (
    $user['role'] === 'admin'
    || ($user['role'] === 'student' && (int) $ticket['student_id'] === (int) $user['id'])
    || ($user['role'] === 'department' && (int) $ticket['department_id'] === (int) $user['department_id'])
);
if (!$allowed) {
    http_response_code($ticket ? 403 : 404);
    exit($ticket ? 'You do not have permission to access this complaint.' : 'Complaint not found.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'comment') {
        $message = trim((string) ($_POST['message'] ?? ''));
        if (mb_strlen($message) < 2 || mb_strlen($message) > 4000) {
            $error = 'Enter a message between 2 and 4,000 characters.';
        } else {
            $pdo->beginTransaction();
            try {
                $insert = $pdo->prepare("INSERT INTO ticket_updates (ticket_id, user_id, update_type, message) VALUES (?, ?, 'comment', ?)");
                $insert->execute([$id, $user['id'], $message]);
                $pdo->prepare('UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$id]);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
            flash('success', 'Your update has been added.');
            header('Location: ticket_view.php?id=' . $id);
            exit;
        }
    } elseif ($action === 'status' && in_array($user['role'], ['admin', 'department'], true)) {
        $newStatus = (string) ($_POST['status'] ?? '');
        $allowedStatuses = $user['role'] === 'admin'
            ? ['Submitted', 'In Progress', 'Awaiting Student', 'Resolved', 'Closed']
            : ['In Progress', 'Awaiting Student', 'Resolved'];
        if (!in_array($newStatus, $allowedStatuses, true) || ($user['role'] === 'department' && $ticket['status'] === 'Closed')) {
            $error = 'Choose a valid status.';
        } else {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
                $history = $pdo->prepare("INSERT INTO ticket_updates (ticket_id, user_id, update_type, message) VALUES (?, ?, 'status', ?)");
                $history->execute([$id, $user['id'], 'Status changed to ' . $newStatus . '.']);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
            flash('success', 'Complaint status updated.');
            header('Location: ticket_view.php?id=' . $id);
            exit;
        }
    } elseif ($action === 'assign' && $user['role'] === 'admin') {
        $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
        $departmentCheck = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        $department = $departmentCheck->fetch();
        if (!$department) {
            $error = 'Choose a valid department.';
        } else {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE tickets SET department_id = ? WHERE id = ?')->execute([$departmentId, $id]);
                $history = $pdo->prepare("INSERT INTO ticket_updates (ticket_id, user_id, update_type, message) VALUES (?, ?, 'assignment', ?)");
                $history->execute([$id, $user['id'], 'Complaint routed to ' . $department['name'] . '.']);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
            flash('success', 'Complaint routed to ' . $department['name'] . '.');
            header('Location: ticket_view.php?id=' . $id);
            exit;
        }
    } else {
        http_response_code(400);
        exit('Invalid complaint action.');
    }
}

$departments = $user['role'] === 'admin' ? $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll() : [];
$historyStatement = $pdo->prepare('SELECT tu.update_type, tu.message, tu.created_at, u.full_name, u.role FROM ticket_updates tu JOIN users u ON u.id = tu.user_id WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC');
$historyStatement->execute([$id]);
$history = $historyStatement->fetchAll();

require_once __DIR__ . '/includes/layout.php';
render_header($ticket['reference_no'], 'tickets');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card-panel">
    <div class="d-flex justify-content-between align-items-start flex-wrap"><div><span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span><h2 class="mt-3 mb-1"><?= e($ticket['subject']) ?></h2><span class="text-muted"><?= e($ticket['topic']) ?></span></div><?php if ($user['role'] !== 'student'): ?><a class="button button-outline" href="tickets.php">Back to queue</a><?php endif; ?></div>
    <div class="ticket-meta">
        <div><small>Reference number</small><strong><?= e($ticket['reference_no']) ?></strong></div>
        <div><small>Submitted by</small><strong><?= e($ticket['student_name']) ?> (<?= e($ticket['matric_no']) ?>)</strong></div>
        <div><small>Assigned department</small><strong><?= e($ticket['department_name'] ?? 'Not assigned') ?></strong></div>
    </div>
    <div class="card-panel mb-0"><h3>Complaint details</h3><p class="mb-0" style="white-space:pre-wrap"><?= e($ticket['description']) ?></p></div>
</div>
<?php if ($user['role'] === 'admin' || $user['role'] === 'department'): ?>
<div class="card-panel">
    <h2>Case actions</h2>
    <div class="row">
        <?php if ($user['role'] === 'admin'): ?>
        <div class="col-md-6"><form method="post" class="mb-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="assign"><div class="form-group"><label for="department_id">Route to department</label><div class="inline-form"><select class="form-control" id="department_id" name="department_id" required><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $ticket['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select><button class="button" type="submit">Route case</button></div></div></form></div>
        <?php endif; ?>
        <?php if ($user['role'] === 'admin' || $ticket['status'] !== 'Closed'): ?>
        <div class="col-md-6"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><div class="form-group"><label for="status">Update status</label><div class="inline-form"><select class="form-control" id="status" name="status" required><?php $statuses = $user['role'] === 'admin' ? ['Submitted', 'In Progress', 'Awaiting Student', 'Resolved', 'Closed'] : ['In Progress', 'Awaiting Student', 'Resolved']; foreach ($statuses as $status): ?><option <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><button class="button" type="submit">Save status</button></div></div></form></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<div class="card-panel">
    <h2>Updates & conversation</h2>
    <?php if (!$history): ?><div class="empty-state">No updates yet.</div><?php else: ?><div class="mb-4"><?php foreach ($history as $item): ?><div class="timeline-item"><strong><?= e($item['full_name']) ?></strong> <span class="text-muted">&middot; <?= e(ucfirst($item['role'])) ?> &middot; <?= e(date('M j, Y g:i a', strtotime($item['created_at']))) ?></span><p><?= e($item['message']) ?></p></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="comment"><div class="form-group"><label for="message">Add an update</label><textarea class="form-control" id="message" name="message" maxlength="4000" required placeholder="Add a question or provide more information..."></textarea></div><button class="button" type="submit">Post update</button></form>
</div>
<?php render_footer(); ?>
