<?php
// ============================================================
// api/calificaciones.php  –  Envío de calificaciones (jurado)
// ============================================================
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('jurado');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body)) {
    jsonResponse(['error' => 'Cuerpo de la solicitud inválido.'], 400);
}

$id_participante     = (int)($body['id_participante'] ?? 0);
$nota_tecnica        = isset($body['nota_tecnica'])        ? (float)$body['nota_tecnica']        : null;
$nota_criterio_2     = isset($body['nota_criterio_2'])     ? (float)$body['nota_criterio_2']     : null;
$nota_vestuario      = isset($body['nota_vestuario'])      ? (float)$body['nota_vestuario']      : null;
$nota_musicalizacion = isset($body['nota_musicalizacion']) ? (float)$body['nota_musicalizacion'] : null;
$id_usuario          = (int)$_SESSION['id_usuario'];

// ── Validaciones ──────────────────────────────────────────
if ($id_participante <= 0) {
    jsonResponse(['error' => 'Participante inválido.'], 422);
}

$notas = [
    'nota_tecnica'        => $nota_tecnica, 
    'nota_criterio_2'     => $nota_criterio_2,
    'nota_vestuario'      => $nota_vestuario, 
    'nota_musicalizacion' => $nota_musicalizacion
];

foreach ($notas as $campo => $val) {
    if ($val === null || $val < 1.0 || $val > 10.0) {
        jsonResponse(['error' => "El campo '{$campo}' es inválido. Debe estar entre 1 y 10."], 422);
    }
}

try {
    $pdo = getPDO();

    // Verificar que el participante existe
    $chk = $pdo->prepare('SELECT id_participante FROM participantes WHERE id_participante = ?');
    $chk->execute([$id_participante]);
    if (!$chk->fetch()) {
        jsonResponse(['error' => 'El participante no existe.'], 404);
    }

    // Verificar que el jurado no haya votado ya
    $chkVoto = $pdo->prepare('
        SELECT id_calificacion FROM calificaciones
         WHERE id_participante = ? AND id_usuario = ?
    ');
    $chkVoto->execute([$id_participante, $id_usuario]);
    if ($chkVoto->fetch()) {
        jsonResponse(['error' => 'Ya has evaluado a este participante. Solo se permite una calificación por jurado.'], 409);
    }

    // Insertar calificación
    $ins = $pdo->prepare('
        INSERT INTO calificaciones
               (id_participante, id_usuario, nota_tecnica, nota_criterio_2, nota_vestuario, nota_musicalizacion)
        VALUES (:id_p, :id_u, :tec, :crit2, :vest, :musi)
    ');
    $ins->execute([
        ':id_p'  => $id_participante,
        ':id_u'  => $id_usuario,
        ':tec'   => round($nota_tecnica,        2),
        ':crit2' => round($nota_criterio_2,     2),
        ':vest'  => round($nota_vestuario,      2),
        ':musi'  => round($nota_musicalizacion, 2),
    ]);

    // Calcular puntaje actual del participante para retornar
    $pts = $pdo->prepare('
        SELECT ROUND(
                 AVG(nota_tecnica)        * 0.40 +
                 AVG(nota_criterio_2)     * 0.30 +
                 AVG(nota_vestuario)      * 0.15 +
                 AVG(nota_musicalizacion) * 0.15, 2
               ) AS puntaje_parcial
          FROM calificaciones
         WHERE id_participante = ?
    ');
    $pts->execute([$id_participante]);
    $row = $pts->fetch();

    jsonResponse([
        'ok'              => true,
        'message'         => '¡Calificación enviada con éxito!',
        'puntaje_parcial' => $row['puntaje_parcial'],
    ]);

} catch (PDOException $e) {
    // UNIQUE KEY violation (race condition)
    if ((string)$e->getCode() === '23000') {
        jsonResponse(['error' => 'Ya has evaluado a este participante.'], 409);
    }
    http_response_code(500);
    jsonResponse(['error' => 'Error del servidor al guardar la calificación.']);
}