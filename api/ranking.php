<?php
// ============================================================
// api/ranking.php  –  Ranking con Descuento de Penalizaciones
// ============================================================
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

$cat = trim($_GET['cat'] ?? '');
$mod = trim($_GET['mod'] ?? '');

try {
    $pdo = getPDO();

    $sql = '
        SELECT
            p.id_participante,
            p.nombre_acto,
            p.modalidad,
            p.categoria_edad,
            p.estilo,
            -- Promedios de los jurados
            IFNULL(AVG(c.nota_tecnica), 0)        AS avg_tecnica,
            IFNULL(AVG(c.nota_criterio_2), 0)     AS avg_artistica, 
            IFNULL(AVG(c.nota_vestuario), 0)      AS avg_musical,   
            IFNULL(AVG(c.nota_musicalizacion), 0) AS avg_escena,    
            
            -- Calculamos el puntaje bruto (sin multas)
            IFNULL(ROUND(
                (AVG(c.nota_tecnica) * 0.40) +
                (AVG(c.nota_criterio_2) * 0.30) +
                (AVG(c.nota_vestuario) * 0.15) +
                (AVG(c.nota_musicalizacion) * 0.15), 2
            ), 0) AS puntaje_bruto,

            -- Traemos la suma de penalizaciones de la otra tabla
            (SELECT IFNULL(SUM(puntos_descuento), 0) 
             FROM penalizaciones 
             WHERE id_participante = p.id_participante) AS total_penalizacion,

            -- Verificamos si tiene una bandera de descalificado
            (SELECT COUNT(*) 
             FROM penalizaciones 
             WHERE id_participante = p.id_participante AND descalificado = 1) AS es_descalificado,

            COUNT(c.id_calificacion) AS num_jurados
          FROM participantes p
          LEFT JOIN calificaciones c ON c.id_participante = p.id_participante
          WHERE 1=1
    ';

    $params = [];
    if ($cat !== '') { $sql .= ' AND p.categoria_edad = :cat'; $params[':cat'] = $cat; }
    if ($mod !== '') { $sql .= ' AND p.modalidad = :mod'; $params[':mod'] = $mod; }

    $sql .= ' GROUP BY p.id_participante, p.nombre_acto, p.modalidad, p.categoria_edad, p.estilo';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawRanking = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos la lógica final para cada fila
    $finalRanking = array_map(function($r) {
        $bruto = (float)$r['puntaje_bruto'];
        $multa = (float)$r['total_penalizacion'];
        
        // Si está descalificado, su nota es 0
        if ((int)$r['es_descalificado'] > 0) {
            $r['puntaje_final'] = 0.00;
            $r['status_label'] = 'DESCALIFICADO';
        } else {
            // Restamos la penalización y aseguramos que no sea menor a 0
            $r['puntaje_final'] = max(0, $bruto - $multa);
            $r['status_label'] = $multa > 0 ? "-$multa pts" : 'Limpio';
        }
        return $r;
    }, $rawRanking);

    // Ordenamos por puntaje final de mayor a menor
    usort($finalRanking, fn($a, $b) => $b['puntaje_final'] <=> $a['puntaje_final']);

    echo json_encode(['ranking' => $finalRanking], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}