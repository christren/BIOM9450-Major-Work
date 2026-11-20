<?php
// OncoAtlas: shared PHP setup
// Every PHP page includes this file through require_once.
// It provides the database connection, login checks, form protection,
// small SQL helpers, and activity logging.
//
// Regular website database operations use MySQLi.
// The separate, existing prediction model still requires a PDO connection;
// we retain db() for it without changing the model code.

declare(strict_types=1);

const ROOT_DIR = __DIR__ . '/..';

// 1. Start a PHP session so we can remember which user has logged in.
// The settings help protect the session cookie.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

// 2. Load the MySQL settings from config/config.php.
// "static" means PHP only loads the file once, even if config() is called again.
function config(): array {
    static $settings = null;

    if ($settings === null) {
        $settings = require ROOT_DIR . '/config/config.php';
    }

    return $settings;
}

// 3. MySQLi connection
// The same connection is used by SELECT, INSERT and UPDATE queries.
// It is also shared during mutation-upload transactions.
function mysqli_db(): mysqli {
    static $connection = null;

    if ($connection === null) {
        $settings = config();

        // MySQLi throws mysqli_sql_exception if a query fails.
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $connection = new mysqli(
            $settings['db_host'],
            $settings['db_user'],
            $settings['db_pass'],
            $settings['db_name'],
            (int)$settings['db_port']
        );
        $connection->set_charset('utf8mb4');
    }

    return $connection;
}

// Prepare and run an SQL statement, using ? placeholders.
// This is the same prepare() / bind_param() / execute() process
// We put it in one place to avoid repeating it.
function sql_run(string $sql, array $params = []): mysqli_stmt {
    $stmt = mysqli_db()->prepare($sql);

    if (count($params) > 0) {
        // bind_param types: i = integer, d = decimal, s = string.
        $types = '';
        foreach ($params as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt;
}

// The prediction model requires its existing PDO interface.
// It is isolated in app/model_db.php and is not used by website pages.
require_once __DIR__ . '/model_db.php';

// 4. Display database or form text safely inside HTML.
// htmlspecialchars() stops a name such as "<script>" becoming HTML code.
function h(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Print a dash when an optional database field has no value.
// h() is still used to escape all other values.
function display(mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return h($value);
}

// 5. Check the user saved in $_SESSION when the login form succeeds.
// The role controls which pages a user can access.
function app_user(): ?array {
    return $_SESSION['account'] ?? null;
}

function account_role(): string {
    return (string)(app_user()['role'] ?? '');
}

function signed_in(): bool {
    return app_user() !== null;
}

function is_professional(): bool {
    return account_role() === 'Researcher' || account_role() === 'Oncologist';
}

function is_researcher(): bool {
    return account_role() === 'Researcher';
}

// Send the browser to another page, then stop running this PHP script.
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// This page requires a login. AJAX requests receive JSON instead of a redirect.
function require_login(): void {
    if (signed_in()) {
        return;
    }

    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        json_response(['error' => 'Sign in required'], 401);
    }

    redirect('login.php');
}

// Both oncologists and researchers may view patients and mutations.
function require_professional(): void {
    require_login();

    if (!is_professional()) {
        forbidden();
    }
}

// Advanced analytics are available only to researchers.
function require_researcher(): void {
    require_login();

    if (!is_researcher()) {
        forbidden();
    }
}

function forbidden(): never {
    http_response_code(403);

    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        json_response(['error' => 'Access denied'], 403);
    }

    exit('403 — You do not have permission to view this page.');
}

// 6. Forms use a hidden CSRF token. This checks that a form was submitted
// from this user's session rather than from another website.
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void {
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        throw new RuntimeException('This form expired. Refresh and try again.');
    }
}

// 7. Flash messages appear once, on the next page, after a redirect.
function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['text' => $message, 'type' => $type];
}

function flash_take(): ?array {
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

// 8. AJAX uses JSON. json_encode() converts a PHP array into JSON text.
// exit stops any HTML from being added after the JSON.
function json_response(array $body, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// 9. Save important actions in the AuditLog table.
// An audit problem must not accidentally crash a normal page.
function audit(string $action, ?string $entity = null, ?string $entityId = null): void {
    try {
        $sql = 'INSERT INTO AuditLog (UserID, Action, EntityType, EntityID)
                VALUES (?, ?, ?, ?)';
        $stmt = sql_run($sql, [app_user()['id'] ?? null, $action, $entity, $entityId]);
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Audit error: ' . $error->getMessage());
    }
}

// 10. Read a positive or zero integer from GET or POST inputs.
// This rejects letters, decimals, negatives and values outside the range.
function int_param(mixed $value, int $minimum = 1, int $maximum = 2147483647): ?int {
    if (!is_scalar($value) || !preg_match('/^[0-9]+$/', (string)$value)) {
        return null;
    }

    $number = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $minimum, 'max_range' => $maximum]
    ]);

    return $number === false ? null : $number;
}

// Missing values in cBioPortal are often blank, NA or a period.
// We turn these into SQL NULL instead of storing misleading text.
function clean_nullable(mixed $value): ?string {
    $text = trim((string)($value ?? ''));

    if ($text === '' || in_array(strtoupper($text), ['NA', 'N/A', 'NULL', 'NOT AVAILABLE', '.'], true)) {
        return null;
    }

    return $text;
}

// 11. Three simple MySQLi SELECT helpers.
// query_rows()  -> many rows (e.g. all patients)
// query_one()   -> one row  (e.g. patient with PatientID = 2)
// query_value() -> one value (e.g. COUNT(*) = 150)
//
// Each one uses the same technique: prepare, bind, execute,
// get_result, then fetch_assoc or fetch_all.
function query_rows(string $sql, array $params = []): array {
    $stmt = sql_run($sql, $params);
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function query_one(string $sql, array $params = []): ?array {
    $stmt = sql_run($sql, $params);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function query_value(string $sql, array $params = []): mixed {
    $row = query_one($sql, $params);
    // Like PDO fetchColumn(): false if SELECT returned no rows.
    if ($row === null) {
        return false;
    }
    return reset($row);
}

function require_get(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(['error' => 'GET required'], 405);
    }
}

// Read one optional form field while checking its maximum length.
function nullable_post(string $key, int $max = 255): ?string {
    $value = clean_nullable($_POST[$key] ?? null);

    if ($value !== null && strlen($value) > $max) {
        throw new RuntimeException("$key is too long (max $max characters).");
    }

    return $value;
}

// 12. Patient accounts only see the patient profile linked to their UserID.
function linked_patient_id(): ?int {
    $user = app_user();
    if (!$user) {
        return null;
    }

    $id = query_value('SELECT PatientID FROM Patient WHERE UserID = ?', [$user['id']]);

    return $id === false ? null : (int)$id;
}

function can_view_patient(int $patientId): bool {
    if (is_professional()) {
        return true;
    }

    return account_role() === 'Patient' && linked_patient_id() === $patientId;
}
