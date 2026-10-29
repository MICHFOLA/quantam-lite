<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'fcwm_complaints';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

if (!preg_match('/\A[a-zA-Z0-9_]{1,64}\z/', $dbName)) {
    http_response_code(500);
    exit('DB_NAME must be a valid MySQL database name containing only letters, numbers, and underscores.');
}

try {
    $server = new PDO(
        "mysql:host={$dbHost};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and check config/database.php settings. Details: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    matric_no VARCHAR(60) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'department') NOT NULL DEFAULT 'student',
    department_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(24) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    topic VARCHAR(100) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Submitted', 'In Progress', 'Awaiting Student', 'Resolved', 'Closed') NOT NULL DEFAULT 'Submitted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_tickets_department_status (department_id, status),
    INDEX idx_tickets_student (student_id)
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS ticket_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    update_type ENUM('comment', 'status', 'assignment') NOT NULL DEFAULT 'comment',
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_updates_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_updates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_updates_ticket (ticket_id, created_at)
) ENGINE=InnoDB");

$departments = [
    'Wildlife Management',
    'Forestry Technology',
    'Fisheries Technology',
    'Environmental Management',
    'Computer Science',
    'Agricultural Business',
    'Registry',
    'Student Affairs',
    'ICT Support',
];
$insertDepartment = $pdo->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
foreach ($departments as $departmentName) {
    $insertDepartment->execute([$departmentName]);
}

$adminEmail = 'admin@fcwm.edu.ng';
$checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
$checkAdmin->execute([$adminEmail]);
if (!$checkAdmin->fetch()) {
    $insertAdmin = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $insertAdmin->execute([
        'System Administrator',
        $adminEmail,
        password_hash('Admin@12345', PASSWORD_DEFAULT),
        'admin',
    ]);
}
