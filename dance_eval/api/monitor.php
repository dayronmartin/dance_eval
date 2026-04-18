<?php
// ============================================================
// api/monitor.php  –  Datos para el monitor en vivo
// ============================================================
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Candado silencioso para APIs: Si no hay sesión, devolvemos datos vacíos sin redirigir
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['participantes' => [], 'jurados' => []]);
    exit;
}

try {
    $pdo = getPDO();

    // Todos los participantes
    $stmtP = $pdo->query('
        SELECT id_participante, nombre_acto, modalidad, categoria_edad, estilo
          FROM participantes
         ORDER BY id_participante ASC
    ');
    $participantes = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // Todos los jurados (excluir admins y penalizadores)
    $stmtJ = $pdo->query("
        SELECT id_usuario, username
          FROM usuarios
         WHERE rol = 'jurado'
         ORDER BY id_usuario ASC
    ");
    $jurados = $stmtJ->fetchAll(PDO::FETCH_ASSOC);

    // Quién votó a quién: traer todos los pares (id_participante, id_usuario)
    $stmtC = $pdo->query('
        SELECT id_participante, id_usuario
          FROM calificaciones
    ');
    $califs = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    // Indexar por participante → lista de id_usuario que ya votaron
    $votosPorPartic = [];
    foreach ($califs as $c) {
        $votosPorPartic[$c['id_participante']][] = (string)$c['id_usuario'];
    }

    // Enriquecer participantes con quiénes votaron
    $result = array_map(function($p) use ($votosPorPartic) {
        $p['votaron'] = $votosPorPartic[$p['id_participante']] ?? [];
        return $p;
    }, $participantes);

    echo json_encode([
        'participantes' => $result,
        'jurados'       => $jurados,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar el monitor: ' . $e->getMessage()]);
}
?>