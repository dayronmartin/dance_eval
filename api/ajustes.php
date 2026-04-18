<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre_evento = trim($data['nombre_evento'] ?? '');

    if ($nombre_evento === '') {
        echo json_encode(['ok' => false, 'error' => 'El nombre no puede estar vacío.']);
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = :valor WHERE clave = 'nombre_evento'");
        $stmt->execute([':valor' => $nombre_evento]);
        echo json_encode(['ok' => true, 'message' => 'Nombre del evento actualizado.']);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'error' => 'Error al guardar en BD.']);
    }
}
?>