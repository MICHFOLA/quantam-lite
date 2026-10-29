<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'department') {
        $name = trim((string) ($_POST['department_name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $error = 'Enter a department name between 2 and 120 characters.';
        } else {
            try {
                $pdo->prepare('INSERT INTO departments (name) VALUES (?)')->execute([$name]);
                flash('success', 'Department added.');
                header('Location: staff.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That department already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'account') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($fullName) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !$departmentId) {
            $error = 'Enter a name, valid email, department, and password with at least 8 characters.';
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, department_id) VALUES (?, ?, ?, 'department', ?)");
                $insert->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $departmentId]);
                flash('success', 'Department account created.');
                header('Location: staff.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That email is already in use, or the selected department is invalid.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $statement->execute([current_user()['id']]);
        $account = $statement->fetch();
        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Your new password must be at least 8 characters.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), current_user()['id']]);
            flash('success', 'Your password has been changed.');
            header('Location: staff.php');
            exit;
        }
    } else {
        http_response_code(400);
        exit('Invalid admin action.');
    }
}

$departments = $pdo->query('SELECT d.id, d.name, COUNT(DISTINCT u.id) AS accounts FROM departments d LEFT JOIN users u ON u.department_id = d.id AND u.role = \'department\' GROUP BY d.id, d.name ORDER BY d.name')->fetchAll();
$accounts = $pdo->query("SELECT u.full_name, u.email, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.role = 'department' ORDER BY d.name, u.full_name")->fetchAll();

require_once __DIR__ . '/includes/layout.php';
render_header('Departments & accounts', 'staff');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="row">
    <div class="col-lg-6"><div class="card-panel">
        <h2>Add a department</h2>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="department">
            <div class="form-group"><label for="department_name">Department name</label><input class="form-control" id="department_name" name="department_name" maxlength="120" required></div>
            <button class="button" type="submit">Add department</button>
        </form>
    </div></div>
    <div class="col-lg-6"><div class="card-panel">
        <h2>Create a department account</h2>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="account">
            <div class="form-group"><label for="full_name">Staff name</label><input class="form-control" id="full_name" name="full_name" required></div>
            <div class="form-group"><label for="email">Staff email</label><input class="form-control" id="email" name="email" type="email" required></div>
            <div class="form-group"><label for="department_id">Department</label><select class="form-control" id="department_id" name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>"><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="password">Temporary password</label><input class="form-control" id="password" name="password" type="password" minlength="8" required></div>
            <button class="button" type="submit">Create staff account</button>
        </form>
    </div></div>
</div>
<div class="card-panel">
    <h2>Departments</h2>
    <?php if (!$departments): ?><div class="empty-state">No departments yet.</div><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Department</th><th>Staff accounts</th></tr></thead><tbody><?php foreach ($departments as $department): ?><tr><td><?= e($department['name']) ?></td><td><?= (int) $department['accounts'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<div class="card-panel">
    <h2>Department accounts</h2>
    <?php if (!$accounts): ?><div class="empty-state">No department accounts created yet.</div><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Department</th></tr></thead><tbody><?php foreach ($accounts as $account): ?><tr><td><?= e($account['full_name']) ?></td><td><?= e($account['email']) ?></td><td><?= e($account['department_name'] ?? 'No department') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<div class="card-panel form-card">
    <h2>Change your administrator password</h2>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="password">
        <div class="form-group"><label for="current_password">Current password</label><input class="form-control" id="current_password" name="current_password" type="password" required></div>
        <div class="form-group"><label for="new_password">New password</label><input class="form-control" id="new_password" name="new_password" type="password" minlength="8" required></div>
        <button class="button" type="submit">Change password</button>
    </form>
</div>
<?php render_footer(); ?>
