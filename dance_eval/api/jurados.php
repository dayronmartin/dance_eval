<?php
// ============================================================
// api/jurados.php  –  CRUD de usuarios jurado
// ============================================================
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    $pdo = getPDO();

    // ── LIST ──────────────────────────────────────────────
    if ($action === 'list') {
        $stmt = $pdo->query("
            SELECT id_usuario, username, rol,
                   DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at
              FROM usuarios
             ORDER BY rol ASC, id_usuario ASC
        ");
        jsonResponse(['jurados' => $stmt->fetchAll()]);
    }

    // ── CREATE ────────────────────────────────────────────
    if ($action === 'create') {
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');

        if ($username === '' || $password === '') {
            jsonResponse(['error' => 'Usuario y contraseña son obligatorios.'], 422);
        }
        if (strlen($username) < 3 || strlen($username) > 60) {
            jsonResponse(['error' => 'El nombre de usuario debe tener entre 3 y 60 caracteres.'], 422);
        }
        if (strlen($password) < 6) {
            jsonResponse(['error' => 'La contraseña debe tener al menos 6 caracteres.'], 422);
        }

        // Verificar duplicado
        $chk = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE username = ?');
        $chk->execute([$username]);
        if ($chk->fetch()) {
            jsonResponse(['error' => 'El nombre de usuario ya está en uso.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $ins  = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, 'jurado')");
        $ins->execute([$username, $hash]);

        jsonResponse(['ok' => true, 'message' => "Jurado '{$username}' registrado correctamente."]);
    }

    // ── DELETE ────────────────────────────────────────────
    if ($action === 'delete') {
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_usuario = (int)($body['id_usuario'] ?? 0);

        if ($id_usuario <= 0) {
            jsonResponse(['error' => 'ID de usuario inválido.'], 422);
        }

        // No eliminar admins
        $chk = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
        $chk->execute([$id_usuario]);
        $user = $chk->fetch();

        if (!$user) {
            jsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }
        if ($user['rol'] === 'admin') {
            jsonResponse(['error' => 'No puedes eliminar a un administrador.'], 403);
        }
        // Evitar que el admin actual se elimine a sí mismo
        if ($id_usuario === (int)$_SESSION['id_usuario']) {
            jsonResponse(['error' => 'No puedes eliminarte a ti mismo.'], 403);
        }

        $del = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
        $del->execute([$id_usuario]);

        jsonResponse(['ok' => true, 'message' => 'Jurado eliminado correctamente.']);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);

} catch (PDOException $e) {
    http_response_code(500);
    jsonResponse(['error' => 'Error del servidor en jurados.']);
}
