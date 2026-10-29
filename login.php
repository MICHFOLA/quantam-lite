<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id, full_name, email, password_hash, role, department_id FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Email or password is incorrect.';
}

require_once __DIR__ . '/includes/layout.php';
render_header('Sign in', 'login');
?>
<div class="auth-wrap"><div class="auth-card">
    <h2>Welcome back</h2>
    <p>Sign in to manage your student support requests.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group"><label for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="username" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="form-group"><label for="password">Password</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="button" type="submit">Sign in</button>
    </form>
    <p class="auth-links">New to the portal? <a href="register.php">Create a student account</a></p>
</div></div>
<?php render_footer(); ?>
