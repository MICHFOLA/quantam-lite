<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $matricNo = strtoupper(trim((string) ($_POST['matric_no'] ?? '')));
    $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
    $password = (string) ($_POST['password'] ?? '');

    if (mb_strlen($fullName) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $matricNo === '' || strlen($password) < 8 || !$departmentId) {
        $error = 'Complete all fields with a valid email address and a password of at least 8 characters.';
    } else {
        $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        if (!$departmentCheck->fetch()) {
            $error = 'Select a valid department.';
        } else {
            try {
                $insert = $pdo->prepare('INSERT INTO users (full_name, email, matric_no, password_hash, role, department_id) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->execute([$fullName, $email, $matricNo, password_hash($password, PASSWORD_DEFAULT), 'student', $departmentId]);
                flash('success', 'Your student account is ready. Sign in to submit a complaint.');
                header('Location: login.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That email address or matriculation number is already registered.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/layout.php';
render_header('Create student account', 'register');
?>
<div class="auth-wrap"><div class="auth-card">
    <h2>Create your account</h2>
    <p>Use your student details to access the complaint portal.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group"><label for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" autocomplete="name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
        <div class="form-group"><label for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="form-group"><label for="matric_no">Matriculation number</label><input class="form-control" id="matric_no" name="matric_no" required value="<?= e($_POST['matric_no'] ?? '') ?>"></div>
        <div class="form-group"><label for="department_id">Department</label><select class="form-control" id="department_id" name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) ($_POST['department_id'] ?? '') === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="password">Password</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><small class="text-muted">Use at least 8 characters.</small></div>
        <button class="button" type="submit">Create account</button>
    </form>
    <p class="auth-links">Already registered? <a href="login.php">Sign in</a></p>
</div></div>
<?php render_footer(); ?>
