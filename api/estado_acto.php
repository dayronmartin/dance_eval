<?php
// api/estado_acto.php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();

    // Si entra un POST (El administrador presionó el botón Play/Stop)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validación silenciosa para API (Sin redirecciones)
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id_participante'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = :val WHERE clave = 'acto_en_curso'");
        $stmt->execute([':val' => (string)$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Si entra un GET (Los teléfonos de los jueces y penalizadores preguntando)
    // Dejamos que cualquiera con sesión activa pueda leer quién está en tarima
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['acto_en_curso' => 0]);
        exit;
    }

    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'acto_en_curso'");
    $activo = (int)$stmt->fetchColumn();
    
    echo json_encode(['acto_en_curso' => $activo]);

} catch (Exception $e) {
    echo json_encode(['acto_en_curso' => 0]);
}
?>