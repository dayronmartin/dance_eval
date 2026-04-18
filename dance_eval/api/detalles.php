<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['detalles' => []]);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT u.username, 
               c.nota_tecnica, 
               c.nota_criterio_2 AS nota_artistica, 
               c.nota_vestuario AS nota_musical, 
               c.nota_musicalizacion AS nota_escena
        FROM calificaciones c
        JOIN usuarios u ON c.id_usuario = u.id_usuario
        WHERE c.id_participante = :id
    ");
    $stmt->execute([':id' => $id]);
    echo json_encode(['detalles' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
}
?>