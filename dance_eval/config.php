<?php
// ============================================================
// config.php  –  Conexión PDO centralizada
// ============================================================
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'dance_eval');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SESSION_NAME', 'dance_eval_sess');
define('BASE_PATH', __DIR__);

/**
 * Devuelve una instancia PDO (singleton por petición).
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción: loguear el error y no exponer detalles
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }

    return $pdo;
}

// ── Sesión segura ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // true en HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Helpers de autenticación ──────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['id_usuario'], $_SESSION['rol']);
}

function requireLogin(string $rol = ''): void
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
    if ($rol !== '' && $_SESSION['rol'] !== $rol) {
        header('Location: index.php');
        exit;
    }
}

// ── Helper JSON ────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function getNombreEvento(): string {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'nombre_evento'");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 'Dance Eval';
}
