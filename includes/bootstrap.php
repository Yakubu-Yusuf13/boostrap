<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

$applicationPath = str_replace('\\', '/', realpath(dirname(__DIR__)));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']))
    : '';
define('APP_BASE_URL', $documentRoot !== '' && strpos($applicationPath, $documentRoot) === 0
    ? substr($applicationPath, strlen($documentRoot))
    : '');

function url(string $path = ''): string
{
    return rtrim(APP_BASE_URL, '/') . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('FCWM_DB_HOST') ?: '127.0.0.1';
    $port = getenv('FCWM_DB_PORT') ?: '3306';
    $database = getenv('FCWM_DB_NAME') ?: 'fcwm_support';
    $username = getenv('FCWM_DB_USER') ?: 'root';
    $password = getenv('FCWM_DB_PASSWORD') ?: '';
    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        http_response_code(500);
        exit('Invalid FCWM_DB_NAME: use only letters, numbers, and underscores.');
    }

    try {
        $server = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        initialize_database($connection);
        return $connection;
    } catch (PDOException $exception) {
        http_response_code(500);
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Database setup required</title><body style="font:16px/1.6 Arial,sans-serif;max-width:680px;margin:12vh auto;padding:24px;color:#20312b">';
        echo '<h1>FCWM Support Portal could not connect to MySQL</h1><p>Start Apache and MySQL in XAMPP, then reload this page. The portal creates its database and tables automatically when MySQL is available.</p>';
        echo '<p>Default connection: <code>127.0.0.1:3306</code>, database <code>fcwm_support</code>, user <code>root</code>, empty password. To change it, set <code>FCWM_DB_HOST</code>, <code>FCWM_DB_PORT</code>, <code>FCWM_DB_NAME</code>, <code>FCWM_DB_USER</code>, and <code>FCWM_DB_PASSWORD</code>.</p>';
        echo '<p><strong>MySQL error:</strong> ' . e($exception->getMessage()) . '</p></body></html>';
        exit;
    }
}

function initialize_database(PDO $connection): void
{
    $connection->exec("CREATE TABLE IF NOT EXISTS departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        matric_number VARCHAR(50) NULL UNIQUE,
        department_id INT UNSIGNED NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('student','admin','department_admin') NOT NULL DEFAULT 'student',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reference_number VARCHAR(32) NOT NULL UNIQUE,
        student_id INT UNSIGNED NOT NULL,
        topic VARCHAR(100) NOT NULL,
        subject VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        department_id INT UNSIGNED NULL,
        status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tickets_department_status (department_id, status),
        INDEX idx_tickets_student (student_id),
        CONSTRAINT fk_tickets_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_tickets_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS ticket_updates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT UNSIGNED NOT NULL,
        actor_id INT UNSIGNED NULL,
        status ENUM('open','in_progress','resolved','closed') NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_updates_ticket (ticket_id, created_at),
        CONSTRAINT fk_updates_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        CONSTRAINT fk_updates_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $departments = [
        'Wildlife Management', 'Forestry Technology', 'Fisheries Technology',
        'Environmental Management', 'Science Laboratory Technology',
        'Computer Science', 'Student Affairs', 'Registry', 'ICT', 'Bursary', 'Works',
    ];
    $insert = $connection->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
    foreach ($departments as $department) {
        $insert->execute([$department]);
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your form session expired. Go back, reload the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $notice = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $notice['type'] === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $class . '" role="alert">' . e($notice['message']) . '</div>';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(array $roles = []): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        header('Location: ' . url('login.php'));
        exit;
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
    return $user;
}

function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function status_badge(string $status): string
{
    $class = [
        'open' => 'badge-open',
        'in_progress' => 'badge-progress',
        'resolved' => 'badge-resolved',
        'closed' => 'badge-closed',
    ][$status] ?? 'badge-open';
    return '<span class="status-badge ' . $class . '">' . e(status_label($status)) . '</span>';
}

db();
