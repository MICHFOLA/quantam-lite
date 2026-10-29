<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function role_home(): string
{
    return 'index.php';
}

function create_reference(PDO $pdo): string
{
    do {
        $reference = 'FCWM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(6)));
        $check = $pdo->prepare('SELECT id FROM tickets WHERE reference_no = ?');
        $check->execute([$reference]);
    } while ($check->fetch());
    return $reference;
}

function status_class(string $status): string
{
    return strtolower(str_replace(' ', '-', $status));
}
