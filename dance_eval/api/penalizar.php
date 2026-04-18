<?php
// api/penalizar.php
declare(strict_types=1);

// Definimos que la respuesta siempre será JSON para que JS no se rompa
header('Content-Type: application/json; charset=utf-8');

// Desactivamos errores visuales que ensucian la respuesta
error_reporting(0);
ini_set('display_errors', '0');

try {
    // 1. Cargar configuración (Ruta absoluta para evitar fallos de carpeta)
    $configPath = dirname(__DIR__) . '/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('No se encuentra config.php en: ' . $configPath);
    }
    require_once $configPath;

    // 2. Verificar sesión sin redirección HTML
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión expirada. Por favor, recarga la página.');
    }

    // 3. Leer datos enviados
    $input = file_get_contents('php://input');
    $body = json_decode($input, true);

    if (!$body || !isset($body['id_participante'])) {
        throw new Exception('Datos de penalización incompletos.');
    }

    $id_p   = (int)$body['id_participante'];
    $penals = $body['penalizaciones'] ?? [];
    $desc   = (int)($body['descalificado'] ?? 0);

    $pdo = getPDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Evitar duplicados
    $stmt = $pdo->prepare("SELECT id_penalizacion FROM penalizaciones WHERE id_participante = ?");
    $stmt->execute([$id_p]);
    if ($stmt->fetch()) {
        throw new Exception('Este acto ya fue sancionado anteriormente.');
    }

    $pdo->beginTransaction();

    $ins = $pdo->prepare("INSERT INTO penalizaciones (id_participante, motivo, puntos_descuento, descalificado) VALUES (?, ?, ?, ?)");

    if ($desc === 1) {
        $motivo = $body['motivo_descalificacion'] ?? 'Descalificación Directa';
        $ins->execute([$id_p, "DESCALIFICADO: $motivo", 0, 1]);
    } else {
        foreach ($penals as $p) {
            $ins->execute([$id_p, $p['motivo'], (float)$p['puntos'], 0]);
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Sanción registrada correctamente.']);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}