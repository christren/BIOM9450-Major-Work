<?php
declare(strict_types=1);

// Shared setup: sessions, MySQL, access control, forms and audit logging.
const ROOT_DIR = __DIR__ . '/..';
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function config(): array {
    static $settings;
    if ($settings === null) $settings = require ROOT_DIR . '/config/config.php';
    return $settings;
}
function db(): PDO {
    static $connection;
    if ($connection === null) {
        $c = config();
        $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4";
        $connection = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $connection;
}
function h(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function display(mixed $value): string {return ($value === null || $value === '') ? '—' : h($value);}
function app_user(): ?array { return $_SESSION['account'] ?? null; }
function account_role(): string { return (string)(app_user()['role'] ?? ''); }
function is_professional(): bool { return in_array(account_role(), ['Researcher','Oncologist'], true); }
function is_researcher(): bool { return account_role() === 'Researcher'; }
function signed_in(): bool { return app_user() !== null; }
function require_login(): void {
    if (!signed_in()) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) json_response(['error'=>'Sign in required'], 401);
        redirect('login.php');
    }
}
function require_professional(): void {
    require_login();
    if (!is_professional()) forbidden();
}
function require_researcher(): void {
    require_login();
    if (!is_researcher()) forbidden();
}
function forbidden(): never {
    http_response_code(403);
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) json_response(['error'=>'Access denied'],403);
    exit('403 — You do not have permission to view this page.');
}
function redirect(string $url): never {header('Location: ' . $url);exit;}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return (string) $_SESSION['csrf_token'];
}
function csrf_field(): string {return '<input type="hidden" name="csrf_token" value="'.h(csrf_token()).'">';}
function verify_csrf(): void {
    $incoming = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($incoming) || !hash_equals(csrf_token(),$incoming)) throw new RuntimeException('This form expired. Refresh and try again.');
}
function flash(string $message, string $type='success'): void {$_SESSION['flash'] = ['text'=>$message,'type'=>$type];}
function flash_take(): ?array {$f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f;}
function json_response(array $body, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function audit(string $action, ?string $entity=null, ?string $entityId=null): void {
    // Audit errors are logged to the PHP server log, without exposing them to the browser.
    try {
        $stmt=db()->prepare('INSERT INTO AuditLog(UserID,Action,EntityType,EntityID) VALUES (?,?,?,?)');
        $stmt->execute([app_user()['id']??null,$action,$entity,$entityId]);
    } catch (Throwable $e) {error_log('Audit error: '.$e->getMessage());}
}
function int_param(mixed $value, int $minimum=1, int $maximum=2147483647): ?int {
    if (!is_scalar($value) || !preg_match('/^\d+$/', (string)$value)) return null;
    $i = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>$minimum,'max_range'=>$maximum]]);
    return $i === false ? null : $i;
}
function clean_nullable(mixed $value): ?string {
    $s=trim((string)($value??''));
    return $s === '' || in_array(strtoupper($s),['NA','N/A','NULL','NOT AVAILABLE','.'],true) ? null : $s;
}
function query_rows(string $sql, array $params=[]): array {
    $st=db()->prepare($sql);$st->execute($params);return $st->fetchAll();
}
function query_one(string $sql, array $params=[]): ?array {
    $st=db()->prepare($sql);$st->execute($params);$row=$st->fetch();return $row ?: null;
}
function query_value(string $sql, array $params=[]): mixed {
    $st=db()->prepare($sql);$st->execute($params);return $st->fetchColumn();
}
function require_get(): void {if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['error'=>'GET required'],405);}
function nullable_post(string $key, int $max=255): ?string {
    $value=clean_nullable($_POST[$key]??null);
    if ($value !== null && strlen($value) > $max) throw new RuntimeException("$key is too long (max $max characters).");
    return $value;
}
// Only patients assigned to a patient account may see its own research profile.
function linked_patient_id(): ?int {
    $id=query_value('SELECT PatientID FROM Patient WHERE UserID=?', [app_user()['id']]);
    return $id===false ? null : (int)$id;
}
function can_view_patient(int $patientId): bool {
    return is_professional() || (account_role()==='Patient' && linked_patient_id()===$patientId);
}
