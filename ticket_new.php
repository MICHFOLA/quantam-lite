<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student');

$topics = ['Academic records', 'Lectures & examinations', 'Fees & payments', 'Accommodation', 'Facilities & maintenance', 'Student welfare', 'ICT & portal access', 'Other'];
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $topic = trim((string) ($_POST['topic'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
    if (!in_array($topic, $topics, true) || mb_strlen($subject) < 4 || mb_strlen($subject) > 180 || mb_strlen($description) < 15 || !$departmentId) {
        $error = 'Choose a topic and department, enter a short subject, and describe the issue in at least 15 characters.';
    } else {
        $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        if (!$departmentCheck->fetch()) {
            $error = 'Select a valid department.';
        } else {
            $reference = create_reference($pdo);
            $pdo->beginTransaction();
            try {
                $insert = $pdo->prepare('INSERT INTO tickets (reference_no, student_id, department_id, topic, subject, description) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->execute([$reference, current_user()['id'], $departmentId, $topic, $subject, $description]);
                $ticketId = (int) $pdo->lastInsertId();
                $history = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, update_type, message) VALUES (?, ?, ?, ?)');
                $history->execute([$ticketId, current_user()['id'], 'status', 'Complaint submitted. Reference number: ' . $reference]);
                $pdo->commit();
                flash('success', 'Complaint submitted. Keep your reference number: ' . $reference);
                header('Location: ticket_view.php?id=' . $ticketId);
                exit;
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }
    }
}

require_once __DIR__ . '/includes/layout.php';
render_header('Submit a complaint', 'new');
?>
<div class="card-panel form-card">
    <h2>Tell us what happened</h2>
    <p class="text-muted">Choose an issue area and the department that can best help. Your complaint will receive a reference number when submitted.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group"><label for="topic">Issue topic</label><select class="form-control" id="topic" name="topic" required><option value="">Select a topic</option><?php foreach ($topics as $topic): ?><option <?= ($_POST['topic'] ?? '') === $topic ? 'selected' : '' ?>><?= e($topic) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="department_id">Department to contact</label><select class="form-control" id="department_id" name="department_id" required><option value="">Select a department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) ($_POST['department_id'] ?? '') === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select><small class="text-muted">An administrator may route your complaint to another department.</small></div>
        <div class="form-group"><label for="subject">Short subject</label><input class="form-control" id="subject" name="subject" maxlength="180" required value="<?= e($_POST['subject'] ?? '') ?>" placeholder="Summarize the issue"></div>
        <div class="form-group"><label for="description">Describe your issue and the support you need</label><textarea class="form-control" id="description" name="description" minlength="15" required><?= e($_POST['description'] ?? '') ?></textarea></div>
        <button class="button" type="submit">Submit complaint</button>
    </form>
</div>
<?php render_footer(); ?>
