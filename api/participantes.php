<?php
// ============================================================
// api/participantes.php  –  CRUD de participantes
// ============================================================
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

$validModalidades  = ['Solo','Dúo','Trío','Grupo','Mega'];
$validCategorias   = ['Baby','Infantil','Juvenil','Senior'];

try {
    $pdo = getPDO();

    // ── LIST ──────────────────────────────────────────────
    if ($action === 'list') {
        $stmt = $pdo->query('
            SELECT id_participante, nombre_acto, modalidad, categoria_edad, estilo,
                   DATE_FORMAT(created_at, \'%d/%m/%Y %H:%i\') AS created_at
              FROM participantes
             ORDER BY id_participante ASC
        ');
        jsonResponse(['participantes' => $stmt->fetchAll()]);
    }

    // ── CREATE ────────────────────────────────────────────
    if ($action === 'create') {
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre     = trim($body['nombre_acto']   ?? '');
        $modalidad  = trim($body['modalidad']      ?? '');
        $categoria  = trim($body['categoria_edad'] ?? '');
        $estilo     = trim($body['estilo']         ?? '');

        if ($nombre === '' || $modalidad === '' || $categoria === '' || $estilo === '') {
            jsonResponse(['error' => 'Todos los campos son obligatorios.'], 422);
        }
        if (strlen($nombre) > 120) {
            jsonResponse(['error' => 'El nombre del acto es demasiado largo (máx. 120 caracteres).'], 422);
        }
        if (!in_array($modalidad, $validModalidades, true)) {
            jsonResponse(['error' => 'Modalidad inválida.'], 422);
        }
        if (!in_array($categoria, $validCategorias, true)) {
            jsonResponse(['error' => 'Categoría de edad inválida.'], 422);
        }
        if (strlen($estilo) > 80) {
            jsonResponse(['error' => 'El estilo es demasiado largo (máx. 80 caracteres).'], 422);
        }

        $ins = $pdo->prepare('
            INSERT INTO participantes (nombre_acto, modalidad, categoria_edad, estilo)
            VALUES (?, ?, ?, ?)
        ');
        $ins->execute([$nombre, $modalidad, $categoria, $estilo]);

        jsonResponse(['ok' => true, 'message' => "Participante '{$nombre}' registrado correctamente."]);
    }

    // ── DELETE ────────────────────────────────────────────
    if ($action === 'delete') {
        $body           = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_participante = (int)($body['id_participante'] ?? 0);

        if ($id_participante <= 0) {
            jsonResponse(['error' => 'ID de participante inválido.'], 422);
        }

        $chk = $pdo->prepare('SELECT id_participante FROM participantes WHERE id_participante = ?');
        $chk->execute([$id_participante]);
        if (!$chk->fetch()) {
            jsonResponse(['error' => 'Participante no encontrado.'], 404);
        }

        // Las calificaciones se borran por CASCADE en la FK
        $del = $pdo->prepare('DELETE FROM participantes WHERE id_participante = ?');
        $del->execute([$id_participante]);

        jsonResponse(['ok' => true, 'message' => 'Participante eliminado correctamente.']);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);

} catch (PDOException $e) {
    http_response_code(500);
    jsonResponse(['error' => 'Error del servidor en participantes.']);
}
