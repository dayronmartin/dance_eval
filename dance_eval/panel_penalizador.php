<?php
// ============================================================
// panel_penalizador.php  –  Interfaz del Jurado de Penalización
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Validamos que exista una sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$nombreEvento = getNombreEvento();
$username   = htmlspecialchars($_SESSION['username'] ?? 'Mesa de Penalización');
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?= htmlspecialchars($nombreEvento) ?> — Penalización</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --surface: #0c0c11; --card: #13131a; --card2: #1a1a24; --border: rgba(255,255,255,.07); --text-dim: rgba(255,255,255,.45); --danger: #e63b3b; --warning: #ff8c42; }
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'DM Sans', sans-serif; background: var(--surface); min-height: 100dvh; color: #fff; }
        .topbar { background: var(--card); border-bottom: 1px solid var(--danger); padding: .8rem 1.2rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; box-shadow: 0 4px 20px rgba(230,59,59,.1); }
        .topbar-brand { font-family: 'Bebas Neue', cursive; font-size: 1.5rem; letter-spacing: .08em; color: var(--danger); }
        .logout-btn { background: rgba(230,59,59,.12); border: 1px solid rgba(230,59,59,.25); color: var(--danger); border-radius: 8px; font-size: .78rem; padding: .3rem .8rem; text-decoration: none; }
        .main-wrap { max-width: 600px; margin: 0 auto; padding: 1.5rem 1rem 4rem; }
        #pantalla-bloqueo { position: fixed; inset: 0; z-index: 9999; background: rgba(12, 12, 17, 0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem; backdrop-filter: blur(10px); transition: opacity 0.3s ease; }
        
        .info-card { background: var(--card2); border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1.2rem; text-align: center; margin-bottom: 1.5rem; }
        .acto-nombre { font-family: 'Bebas Neue', cursive; font-size: 2rem; letter-spacing: 1px; color: #fff; margin: 0; }
        .acto-meta { font-size: .85rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; }

        .section-title { font-family: 'Bebas Neue', cursive; color: var(--text-dim); font-size: 1.2rem; letter-spacing: 2px; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: .5rem; }
        
        .penalty-btn { display: flex; justify-content: space-between; align-items: center; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; width: 100%; text-align: left; margin-bottom: .75rem; color: #fff; transition: all .2s; }
        .penalty-btn:active { transform: scale(0.98); }
        .penalty-btn.active { background: rgba(255,140,66,.15); border-color: var(--warning); }
        .penalty-btn.active .p-icon { color: var(--warning); }
        .p-text { font-size: .9rem; font-weight: 500; }
        .p-pts { font-family: 'Bebas Neue', cursive; font-size: 1.2rem; color: var(--warning); }
        
        .btn-descalificar { background: rgba(230,59,59,.1); border: 1px solid var(--danger); color: var(--danger); width: 100%; border-radius: 12px; padding: 1rem; font-family: 'Bebas Neue', cursive; font-size: 1.3rem; letter-spacing: 1px; margin-bottom: .75rem; transition: all .2s; }
        .btn-descalificar.active { background: var(--danger); color: #fff; box-shadow: 0 0 20px rgba(230,59,59,.4); }

        .btn-submit { background: #fff; border: none; border-radius: 16px; color: #000; font-family: 'Bebas Neue', cursive; font-size: 1.4rem; letter-spacing: 2px; padding: 1.1rem; width: 100%; margin-top: 1.5rem; transition: opacity .2s; }
        .btn-submit:disabled { opacity: 0.5; }
        .hidden { display: none !important; }
        
        .total-box { text-align: center; margin-top: 1rem; font-size: .9rem; color: var(--text-dim); }
        .total-val { font-family: 'Bebas Neue', cursive; font-size: 2rem; color: var(--warning); display: block; }
    </style>
</head>
<body>

<div id="pantalla-bloqueo">
    <i class="bi bi-shield-lock-fill" style="font-size: 4rem; color: var(--text-dim);"></i>
    <h2 class="mt-3" style="font-family: 'Bebas Neue', cursive; letter-spacing: 2px; color: var(--text-dim);">ESPERANDO ACTO</h2>
    <p style="color: var(--text-dim);">El panel de penalización se activará cuando la mesa técnica habilite un participante.</p>
</div>

<header class="topbar">
    <div class="topbar-brand"><i class="bi bi-exclamation-triangle-fill me-2"></i>MESA PENALIZADORA</div>
    <a href="logout.php" class="logout-btn">Salir</a>
</header>

<main class="main-wrap">

    <div id="info-card" class="info-card">
        <p class="acto-meta" id="lbl-meta">MODALIDAD • CATEGORÍA</p>
        <h2 class="acto-nombre" id="lbl-nombre">Nombre del Acto</h2>
        <input type="hidden" id="id-participante" value="">
    </div>

    <div id="panel-castigos">
        
        <div id="alert-msg"></div>

        <h3 class="section-title text-warning mt-4"><i class="bi bi-dash-circle-fill me-2"></i>Deducciones (Puntos)</h3>
        
        <button class="penalty-btn toggle-penalty" data-motivo="Categoría Incorrecta" data-puntos="0.5">
            <span class="p-text"><i class="bi bi-person-badge p-icon me-2 text-dim"></i>Categoría Incorrecta (5%)</span>
            <span class="p-pts">-0.5</span>
        </button>
        <button class="penalty-btn toggle-penalty" data-motivo="Uso de Pirotecnia/Fuego/Agua/Papelillos" data-puntos="0.5">
            <span class="p-text"><i class="bi bi-fire p-icon me-2 text-dim"></i>Pirotecnia/Agua/Papelillos (5%)</span>
            <span class="p-pts">-0.5</span>
        </button>

        <button class="penalty-btn toggle-penalty" data-motivo="Desnudez en vestuario" data-puntos="0.3">
            <span class="p-text"><i class="bi bi-gender-ambiguous p-icon me-2 text-dim"></i>Desnudez (3%)</span>
            <span class="p-pts">-0.3</span>
        </button>
        <button class="penalty-btn toggle-penalty" data-motivo="Exceder tiempo límite (>5 seg)" data-puntos="0.3">
            <span class="p-text"><i class="bi bi-stopwatch p-icon me-2 text-dim"></i>Exceder tiempo (3%)</span>
            <span class="p-pts">-0.3</span>
        </button>
        <button class="penalty-btn toggle-penalty" data-motivo="Grosería en pista musical" data-puntos="0.3">
            <span class="p-text"><i class="bi bi-music-note-list p-icon me-2 text-dim"></i>Grosería en Audio (3%)</span>
            <span class="p-pts">-0.3</span>
        </button>
        <button class="penalty-btn toggle-penalty" data-motivo="Gestos vulgares" data-puntos="0.3">
            <span class="p-text"><i class="bi bi-emoji-angry p-icon me-2 text-dim"></i>Gestos vulgares (3%)</span>
            <span class="p-pts">-0.3</span>
        </button>

        <button class="penalty-btn toggle-penalty" data-motivo="Recaudos tardíos" data-puntos="0.2">
            <span class="p-text"><i class="bi bi-folder-x p-icon me-2 text-dim"></i>Recaudos tardíos (2%)</span>
            <span class="p-pts">-0.2</span>
        </button>

        <div class="total-box mb-4">
            DEDUCCIÓN TOTAL AL PUNTAJE FINAL:
            <span class="total-val" id="total-deduccion">-0.0</span>
        </div>

        <h3 class="section-title text-danger mt-5"><i class="bi bi-sign-stop-fill me-2"></i>Descalificación Inmediata</h3>
        
        <button class="btn-descalificar toggle-desc" data-motivo="Plagio Coreográfico"><i class="bi bi-copy me-2"></i>Plagio</button>
        <button class="btn-descalificar toggle-desc" data-motivo="Ausencia al momento del llamado"><i class="bi bi-person-dash me-2"></i>Ausencia al Llamado</button>
        <button class="btn-descalificar toggle-desc" data-motivo="Tema Político o Religioso"><i class="bi bi-bank me-2"></i>Tema Político / Religioso</button>

        <button id="btn-enviar" class="btn-submit">CONFIRMAR PENALIZACIÓN</button>
        <div class="text-center mt-3"><a href="#" onclick="limpiarPanel()" class="text-dim" style="font-size: .8rem;">Limpiar selección sin enviar</a></div>

    </div>

</main>

<script>
const $ = id => document.getElementById(id);
let penalizacionesActivas = [];
let motivoDescalificacion = null;
let currentActoId = 0;

// Seleccionar/Deseleccionar Deducciones
document.querySelectorAll('.toggle-penalty').forEach(btn => {
    btn.addEventListener('click', function() {
        if(motivoDescalificacion) return; // Si está descalificado, no deja sumar puntos

        this.classList.toggle('active');
        const motivo = this.dataset.motivo;
        const puntos = parseFloat(this.dataset.puntos);

        if (this.classList.contains('active')) {
            penalizacionesActivas.push({ motivo, puntos });
        } else {
            penalizacionesActivas = penalizacionesActivas.filter(p => p.motivo !== motivo);
        }
        actualizarTotal();
    });
});

// Seleccionar Descalificación
document.querySelectorAll('.toggle-desc').forEach(btn => {
    btn.addEventListener('click', function() {
        // Desmarcar todo lo demás
        document.querySelectorAll('.toggle-desc').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.toggle-penalty').forEach(b => b.classList.remove('active'));
        penalizacionesActivas = [];
        actualizarTotal();

        this.classList.add('active');
        motivoDescalificacion = this.dataset.motivo;
    });
});

function actualizarTotal() {
    let total = penalizacionesActivas.reduce((sum, p) => sum + p.puntos, 0);
    $('total-deduccion').innerText = '-' + total.toFixed(1);
}

function limpiarPanel() {
    document.querySelectorAll('.toggle-penalty, .toggle-desc').forEach(b => b.classList.remove('active'));
    penalizacionesActivas = [];
    motivoDescalificacion = null;
    actualizarTotal();
    $('alert-msg').innerHTML = '';
}

// Sincronización en Vivo
setInterval(async () => {
    try {
        const r = await fetch('api/estado_acto.php');
        const res = await r.json();
        const activo = res.acto_en_curso;
        
        const lock = $('pantalla-bloqueo');
        
        if (activo === 0) {
            lock.style.opacity = '1'; lock.style.pointerEvents = 'all';
            currentActoId = 0;
            limpiarPanel();
        } else {
            lock.style.opacity = '0'; lock.style.pointerEvents = 'none';
            if (currentActoId !== activo) {
                currentActoId = activo;
                // Cargar datos del participante
                const [rMonitor] = await Promise.all([fetch('api/monitor.php').then(r=>r.json())]);
                const partic = rMonitor.participantes.find(p => p.id_participante == activo);
                if(partic) {
                    $('id-participante').value = partic.id_participante;
                    $('lbl-nombre').innerText = partic.nombre_acto;
                    $('lbl-meta').innerText = `${partic.modalidad} • ${partic.categoria_edad}`;
                }
            }
        }
    } catch(e) {}
}, 2000);

// Enviar a Base de Datos
$('btn-enviar').addEventListener('click', async () => {
    const id = parseInt($('id-participante').value);
    if (!id || id === 0) return;

    if (penalizacionesActivas.length === 0 && !motivoDescalificacion) {
        alert("Debes seleccionar al menos una infracción para penalizar.");
        return;
    }

    if (!confirm("¿Estás seguro de enviar esta sanción? Este acto no se puede deshacer.")) return;

    $('btn-enviar').disabled = true;
    $('btn-enviar').innerText = 'PROCESANDO...';

    const payload = {
        id_participante: id,
        penalizaciones: penalizacionesActivas,
        descalificado: motivoDescalificacion ? 1 : 0,
        motivo_descalificacion: motivoDescalificacion
    };

    try {
        const r = await fetch('api/penalizar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await r.json();

        if (data.ok) {
            $('alert-msg').innerHTML = `<div class="alert alert-danger text-center fw-bold border-danger">${data.message}</div>`;
            setTimeout(() => {
                $('alert-msg').innerHTML = '';
                limpiarPanel();
            }, 4000);
        } else {
            alert(data.error);
        }
    } catch (e) {
        alert('Error de red al enviar sanción.');
    } finally {
        $('btn-enviar').disabled = false;
        $('btn-enviar').innerText = 'CONFIRMAR PENALIZACIÓN';
    }
});
</script>
</body>
</html>