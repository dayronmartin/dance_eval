<?php
// api/reporte_ganadores.php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
requireLogin('admin'); // Protegemos la ruta para que solo tú puedas imprimir

try {
    $pdo = getPDO(); // <-- Esta es la llave que faltaba

    // Consulta SQL con Window Functions para sacar el Top 3 por categoría y modalidad
    $sql = "
        WITH RankingCategorias AS (
            SELECT 
                p.nombre_acto,
                p.categoria_edad,
                p.modalidad,
                p.estilo,
                AVG((c.nota_tecnica * 0.40) + (c.nota_artistica * 0.30) + (c.nota_musical * 0.20) + (c.nota_escena * 0.10)) AS puntaje_final,
                DENSE_RANK() OVER (
                    PARTITION BY p.categoria_edad, p.modalidad 
                    ORDER BY AVG((c.nota_tecnica * 0.40) + (c.nota_artistica * 0.30) + (c.nota_musical * 0.20) + (c.nota_escena * 0.10)) DESC
                ) as posicion
            FROM participantes p
            JOIN calificaciones c ON p.id_participante = c.id_participante
            GROUP BY p.id_participante, p.nombre_acto, p.categoria_edad, p.modalidad, p.estilo
        )
        SELECT * FROM RankingCategorias 
        WHERE posicion <= 3 
        ORDER BY categoria_edad ASC, modalidad ASC, posicion ASC;
    ";

    $stmt = $pdo->query($sql);
    $ganadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta Oficial de Ganadores - Dance Eval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@300;400;700&display=swap');

    body { 
        background-color: #fff; 
        color: #1a1a1a; 
        font-family: 'Montserrat', sans-serif; 
        line-height: 1.6;
    }

    /* Contenedor principal con margen de seguridad para impresión */
    .acta-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px;
        border: 1px solid #eee;
    }

    /* Encabezado elegante */
    .acta-header { 
        text-align: center; 
        margin-bottom: 50px; 
        position: relative;
    }

    .acta-header h1 { 
        font-family: 'Playfair Display', serif; 
        font-size: 2.5rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 5px;
        color: #000;
    }

    .acta-header h3 { 
        font-weight: 300;
        letter-spacing: 5px;
        font-size: 1rem;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 20px;
    }

    .line-separator {
        width: 100px;
        height: 2px;
        background: #000;
        margin: 20px auto;
    }

    /* Etiquetas de Categoría */
    .category-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.4rem;
        background: #1a1a1a;
        color: #fff;
        padding: 8px 20px;
        display: inline-block;
        margin-top: 30px;
        margin-bottom: 15px;
    }

    /* Estilo de la Tabla */
    .table { 
        border-collapse: collapse !important;
        margin-bottom: 30px;
    }
    
    .table th { 
        background-color: #f2f2f2 !important; 
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        padding: 12px !important;
        border: 1px solid #ddd !important;
    }

    .table td { 
        padding: 15px !important;
        border: 1px solid #ddd !important;
        vertical-align: middle;
    }

    .pos-cell {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .pts-badge {
        font-weight: 700;
        font-size: 1.2rem;
        color: #000;
    }

    /* Sección de Firmas */
    .firma-box { 
        margin-top: 100px; 
        text-align: center; 
    }

    .firma-linea { 
        border-top: 1px solid #000; 
        width: 220px; 
        margin: 0 auto;
    }
    
    .firma-text {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 10px;
        font-weight: 700;
    }

    /* Ajustes para Impresora */
    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
        .acta-container { border: none; width: 100%; max-width: 100%; padding: 0; }
        @page { size: portrait; margin: 1.5cm; }
    }
</style>
</head>
<body class="p-5">

    <div class="no-print text-end mb-4">
        <button onclick="window.print()" class="btn btn-primary btn-lg">🖨️ Imprimir Acta Oficial</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg">Cerrar</button>
    </div>

   <?php $nombreEvento = getNombreEvento(); ?>
    <div class="acta-header">
        <h1><?= htmlspecialchars($nombreEvento) ?></h1>
        <h3>ACTA OFICIAL DE RESULTADOS</h3>
        <div class="line-separator"></div>
        <p>Sistema de Evaluación Técnica | Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <?php if (count($ganadores) > 0): ?>
        
        <?php 
        // Agrupamos visualmente los resultados iterando el array
        $categoria_actual = ''; 
        foreach ($ganadores as $fila): 
            $grupo_actual = $fila['categoria_edad'] . ' - ' . $fila['modalidad'];
            
            // Si cambiamos de categoría, cerramos la tabla anterior e iniciamos una nueva
            if ($categoria_actual !== $grupo_actual): 
                if ($categoria_actual !== '') echo "</tbody></table><br>";
                $categoria_actual = $grupo_actual;
        ?>
                <h4 class="mt-4 mb-3 fw-bold text-uppercase">Categoría: <?= htmlspecialchars($grupo_actual) ?></h4>
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th width="10%">Posición</th>
                            <th width="50%">Participante / Agrupación</th>
                            <th width="20%">Estilo</th>
                            <th width="20%">Puntaje Final</th>
                        </tr>
                    </thead>
                    <tbody>
        <?php endif; ?>
        
                    <tr>
                        <td class="text-center fw-bold">
                            <?php 
                                if($fila['posicion'] == 1) echo "🥇 1er Lugar";
                                elseif($fila['posicion'] == 2) echo "🥈 2do Lugar";
                                elseif($fila['posicion'] == 3) echo "🥉 3er Lugar";
                            ?>
                        </td>
                        <td class="fw-bold fs-5"><?= htmlspecialchars($fila['nombre_acto']) ?></td>
                        <td><?= htmlspecialchars($fila['estilo']) ?></td>
                        <td class="text-center fw-bold fs-5"><?= number_format((float)$fila['puntaje_final'], 2) ?> pts</td>
                    </tr>
        <?php endforeach; ?>
        </tbody></table>

    <?php else: ?>
        <div class="alert alert-warning text-center border border-dark">
            Aún no hay suficientes calificaciones registradas para generar el acta de ganadores.
        </div>
    <?php endif; ?>

    <div class="row firma-box">
        <div class="col-6">
            <div class="firma-linea"></div>
            <p class="fw-bold mt-2">Director del Evento</p>
        </div>
        <div class="col-6">
            <div class="firma-linea"></div>
            <p class="fw-bold mt-2">Comisario Técnico / Juez Principal</p>
        </div>
    </div>

</body>
</html>