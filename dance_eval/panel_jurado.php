<?php
// ============================================================
// panel_jurado.php  –  Interfaz de calificación para Jurado
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireLogin('jurado');
$nombreEvento = getNombreEvento();

$username   = htmlspecialchars($_SESSION['username'] ?? 'Jurado');
$id_usuario = (int)$_SESSION['id_usuario'];

// Cargar lista de participantes con estado (ya votó o no)
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('
        SELECT p.id_participante, p.nombre_acto, p.modalidad, p.categoria_edad, p.estilo,
               IF(c.id_calificacion IS NOT NULL, 1, 0) AS ya_voto
          FROM participantes p
          LEFT JOIN calificaciones c
               ON c.id_participante = p.id_participante AND c.id_usuario = :uid
         ORDER BY p.id_participante ASC
    ');
    $stmt->execute([':uid' => $id_usuario]);
    $participantes = $stmt->fetchAll();
} catch (PDOException $e) {
    $participantes = [];
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Dance Score — Jurado</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --accent:   #e63b6f;
            --accent2:  #ff8c42;
            --accent3:  #3be6b0;
            --accent4:  #9d4edd; /* Nuevo color morado para Vestuario */
            --surface:  #0c0c11;
            --card:     #13131a;
            --card2:    #1a1a24;
            --border:   rgba(255,255,255,.07);
            --text-dim: rgba(255,255,255,.45);
        }

        * { -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            min-height: 100dvh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 40% at 50% 0%, rgba(230,59,111,.10) 0%, transparent 65%);
            z-index: 0;
            pointer-events: none;
        }

        .topbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: .8rem 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-brand {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            letter-spacing: .08em;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .logout-btn {
            background: rgba(230,59,111,.12);
            border: 1px solid rgba(230,59,111,.25);
            color: var(--accent);
            border-radius: 8px;
            font-size: .78rem;
            padding: .3rem .8rem;
            text-decoration: none;
            transition: background .2s;
        }
        .logout-btn:hover { background: rgba(230,59,111,.22); color: var(--accent); }

        .main-wrap {
            position: relative;
            z-index: 1;
            max-width: 560px;
            margin: 0 auto;
            padding: 1.5rem 1rem 4rem;
        }

        /* ── Participant Selector ── */
        .select-label {
            font-size: .72rem; letter-spacing: .12em; text-transform: uppercase;
            color: var(--text-dim); margin-bottom: .5rem;
        }
        .participant-select {
            background: var(--card) !important;
            border: 1px solid var(--border) !important;
            border-radius: 14px !important;
            color: #fff !important;
            font-size: 1rem;
            padding: .85rem 1rem;
            cursor: pointer;
            transition: border-color .2s;
            width: 100%;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='rgba(255,255,255,0.4)' d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 1rem center !important;
            background-size: 14px !important;
            padding-right: 2.5rem !important;
        }
        .participant-select:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(230,59,111,.18) !important;
            outline: none;
        }
        .participant-select option { background: #1a1a24; padding: .5rem; }

        /* ── Info card ── */
        .info-card {
            background: var(--card2);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.2rem;
            display: flex; gap: 1rem; flex-wrap: wrap;
            margin-top: .75rem;
        }
        .info-pill {
            font-size: .75rem; padding: .25rem .7rem;
            border-radius: 20px;
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.6);
        }
        .voted-badge {
            background: rgba(59,230,176,.12);
            border: 1px solid rgba(59,230,176,.3);
            color: var(--accent3);
            border-radius: 8px;
            padding: .4rem .9rem;
            font-size: .8rem;
            font-weight: 600;
        }

        /* ── Scoring section ── */
        .scoring-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem 1.2rem;
            margin-top: 1.25rem;
        }
        .scoring-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.15rem;
            letter-spacing: .08em;
            color: var(--text-dim);
            margin-bottom: 1.2rem;
        }

        /* ── Slider row ── */
        .slider-row {
            margin-bottom: 1.5rem;
        }
        .slider-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: .4rem;
        }
        .slider-label {
            font-size: .82rem; font-weight: 600;
            display: flex; align-items: center; gap: .5rem;
        }
        .slider-icon {
            width: 28px; height: 28px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .85rem;
        }
        
        /* ── Colores VMD 2026 ── */
        .si-tecnica { background: rgba(230,59,111,.15); color: var(--accent); }
        .si-crit2   { background: rgba(255,140,66,.15);  color: var(--accent2); }
        .si-vest    { background: rgba(157,78,221,.15); color: var(--accent4); }
        .si-musi    { background: rgba(59,230,176,.15);  color: var(--accent3); }

        .slider-value {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.6rem;
            line-height: 1;
            min-width: 2.5rem;
            text-align: right;
        }
        .sv-tecnica { color: var(--accent); }
        .sv-crit2   { color: var(--accent2); }
        .sv-vest    { color: var(--accent4); }
        .sv-musi    { color: var(--accent3); }

        .weight-hint {
            font-size: .68rem; color: var(--text-dim); margin-left: .3rem;
        }

        /* Custom range slider */
        input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: rgba(255,255,255,.08);
            outline: none;
            cursor: pointer;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.4);
            transition: transform .1s;
        }
        input[type=range]:active::-webkit-slider-thumb { transform: scale(1.2); }
        input[type=range]::-moz-range-thumb {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.4);
        }

        .range-tecnica { accent-color: var(--accent); }
        .range-crit2   { accent-color: var(--accent2); }
        .range-vest    { accent-color: var(--accent4); }
        .range-musi    { accent-color: var(--accent3); }

        .range-labels {
            display: flex; justify-content: space-between;
            font-size: .65rem; color: var(--text-dim);
            margin-top: .2rem;
        }

        /* ── Preview score ── */
        .preview-score {
            background: var(--card2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: .9rem 1.2rem;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.2rem;
        }
        .preview-label { font-size: .8rem; color: var(--text-dim); }
        .preview-val {
            font-family: 'Bebas Neue', cursive;
            font-size: 2rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .preview-formula { font-size: .7rem; color: var(--text-dim); }

        /* ── Submit button ── */
        .btn-submit {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none; border-radius: 16px;
            color: #fff; font-family: 'Bebas Neue', cursive;
            font-size: 1.4rem; letter-spacing: .12em;
            padding: 1.1rem;
            width: 100%;
            transition: opacity .2s, transform .1s;
            box-shadow: 0 8px 30px rgba(230,59,111,.3);
        }
        .btn-submit:hover  { opacity: .9; transform: translateY(-2px); color: #fff; }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: .5; transform: none; }

        /* ── Messages ── */
        .alert-success-custom {
            background: rgba(59,230,176,.12);
            border: 1px solid rgba(59,230,176,.3);
            border-radius: 12px;
            color: var(--accent3);
            padding: 1rem 1.2rem;
            font-weight: 600;
            text-align: center;
            animation: fadeIn .3s ease;
        }
        .alert-error-custom {
            background: rgba(230,59,111,.12);
            border: 1px solid rgba(230,59,111,.3);
            border-radius: 12px;
            color: #ff8fa5;
            padding: .8rem 1.2rem;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

        .spinner-sm {
            display: inline-block; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Stats overview ── */
        .stats-bar {
            display: flex; gap: .75rem; flex-wrap: wrap;
            padding: .9rem 1.2rem;
            background: var(--card2);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 1.2rem;
        }
        .stat-item { text-align: center; flex: 1; min-width: 60px; }
        .stat-val {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            display: block;
        }
        .stat-lbl { font-size: .65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: .08em; }

        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div id="pantalla-bloqueo" style="position: fixed; inset: 0; z-index: 9999; background: rgba(12, 12, 17, 0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem; backdrop-filter: blur(10px); transition: opacity 0.3s ease;">
    <i class="bi bi-lock-fill" style="font-size: 4rem; color: var(--accent);"></i>
    <h2 class="mt-3" style="font-family: 'Bebas Neue', cursive; letter-spacing: 2px;">VOTACIÓN CERRADA</h2>
    <p style="color: var(--text-dim);">Espera a que la mesa técnica habilite el acto en tarima para poder calificar.</p>
</div>

<header class="topbar">
<div class="topbar-brand"><?= htmlspecialchars($nombreEvento) ?></div>
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:.78rem; color:var(--text-dim);">
            <i class="bi bi-person-circle me-1 text-accent"></i><?= $username ?>
        </span>
        <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
    </div>
</header>

<main class="main-wrap">

    <?php
    $total    = count($participantes);
    $votados  = count(array_filter($participantes, fn($p) => $p['ya_voto']));
    $pendientes = $total - $votados;
    ?>
    <div class="stats-bar mb-3">
        <div class="stat-item">
            <span class="stat-val" style="color:var(--accent)"><?= $total ?></span>
            <span class="stat-lbl">Total</span>
        </div>
        <div class="stat-item">
            <span class="stat-val" style="color:var(--accent3)"><?= $votados ?></span>
            <span class="stat-lbl">Evaluados</span>
        </div>
        <div class="stat-item">
            <span class="stat-val" style="color:var(--accent2)"><?= $pendientes ?></span>
            <span class="stat-lbl">Pendientes</span>
        </div>
    </div>

    <p class="select-label">Selecciona el participante a evaluar</p>
    <select id="sel-participante" class="participant-select">
        <option value="">— Elige un acto —</option>
        <?php foreach ($participantes as $p): ?>
        <option value="<?= $p['id_participante'] ?>"
                data-nombre="<?= htmlspecialchars($p['nombre_acto']) ?>"
                data-modalidad="<?= htmlspecialchars($p['modalidad']) ?>"
                data-categoria="<?= htmlspecialchars($p['categoria_edad']) ?>"
                data-estilo="<?= htmlspecialchars($p['estilo']) ?>"
                data-votado="<?= $p['ya_voto'] ? '1' : '0' ?>">
            <?= htmlspecialchars($p['nombre_acto']) ?> <?= $p['ya_voto'] ? '✓' : '' ?>
            (<?= htmlspecialchars($p['modalidad']) ?> · <?= htmlspecialchars($p['categoria_edad']) ?>)
        </option>
        <?php endforeach; ?>
    </select>

    <div id="info-card" class="info-card hidden"></div>

    <div id="msg-ya-voto" class="alert-success-custom mt-3 hidden">
        <i class="bi bi-patch-check-fill me-2"></i>
        Ya has evaluado a este participante. Sólo puedes votar una vez por acto.
    </div>

    <div id="scoring-panel" class="hidden">

        <div class="scoring-card">
            <p class="scoring-title"><i class="bi bi-sliders me-2"></i>Evaluación VMD</p>

            <div id="score-msg" class="mb-3"></div>

            <div class="slider-row">
                <div class="slider-header">
                    <div class="slider-label">
                        <span class="slider-icon si-tecnica"><i class="bi bi-gear-fill"></i></span>
                        Técnica <span class="weight-hint">× 40%</span>
                    </div>
                    <span class="slider-value sv-tecnica" id="val-tecnica">5</span>
                </div>
                <input type="range" id="sl-tecnica" class="range-tecnica" min="1" max="10" step="0.5" value="5">
                <div class="range-labels"><span>1</span><span>5</span><span>10</span></div>
            </div>

            <div class="slider-row">
                <div class="slider-header">
                    <div class="slider-label">
                        <span class="slider-icon si-crit2" id="icon-criterio-2"><i class="bi bi-lightbulb-fill"></i></span>
                        <span id="text-criterio-2">Creatividad <span class="weight-hint">× 30%</span></span>
                    </div>
                    <span class="slider-value sv-crit2" id="val-criterio-2">5</span>
                </div>
                <input type="range" id="sl-criterio-2" class="range-crit2" min="1" max="10" step="0.5" value="5">
                <div class="range-labels"><span>1</span><span>5</span><span>10</span></div>
            </div>

            <div class="slider-row">
                <div class="slider-header">
                    <div class="slider-label">
                        <span class="slider-icon si-vest"><i class="bi bi-person-bounding-box"></i></span>
                        Vestuario <span class="weight-hint">× 15%</span>
                    </div>
                    <span class="slider-value sv-vest" id="val-vestuario">5</span>
                </div>
                <input type="range" id="sl-vestuario" class="range-vest" min="1" max="10" step="0.5" value="5">
                <div class="range-labels"><span>1</span><span>5</span><span>10</span></div>
            </div>

            <div class="slider-row">
                <div class="slider-header">
                    <div class="slider-label">
                        <span class="slider-icon si-musi"><i class="bi bi-music-note-beamed"></i></span>
                        Musicalización <span class="weight-hint">× 15%</span>
                    </div>
                    <span class="slider-value sv-musi" id="val-musicalizacion">5</span>
                </div>
                <input type="range" id="sl-musicalizacion" class="range-musi" min="1" max="10" step="0.5" value="5">
                <div class="range-labels"><span>1</span><span>5</span><span>10</span></div>
            </div>

            <div class="preview-score">
                <div>
                    <div class="preview-label">Puntaje estimado</div>
                    <div class="preview-formula">T×0.4 + C2×0.3 + V×0.15 + M×0.15</div>
                </div>
                <span class="preview-val" id="preview-score">5.00</span>
            </div>

            <button id="btn-submit" class="btn-submit">
                <i class="bi bi-send-fill me-2"></i>Enviar Calificación
            </button>
        </div>

    </div>

    <?php if ($total === 0): ?>
    <div style="text-align:center; padding:3rem 1rem; color:var(--text-dim);">
        <i class="bi bi-music-note-list" style="font-size:2.5rem; display:block; margin-bottom:.75rem; opacity:.3;"></i>
        No hay participantes registrados aún. Espera a que el administrador los registre.
    </div>
    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
// panel_jurado.js  (inline)
// ============================================================

const $ = id => document.getElementById(id);

const sel       = $('sel-participante');
const infoCard  = $('info-card');
const yaVoto    = $('msg-ya-voto');
const panel     = $('scoring-panel');
const btnSubmit = $('btn-submit');
const scoreMsg  = $('score-msg');

// ── Nuevos Sliders y Pesos VMD 2026 ──
const sliders = [
    { id: 'sl-tecnica',        valId: 'val-tecnica',        weight: 0.40 },
    { id: 'sl-criterio-2',     valId: 'val-criterio-2',     weight: 0.30 },
    { id: 'sl-vestuario',      valId: 'val-vestuario',      weight: 0.15 },
    { id: 'sl-musicalizacion', valId: 'val-musicalizacion', weight: 0.15 },
];

// ── Actualizar display de slider y preview ─────────────────
function updateSliderDisplay() {
    let score = 0;
    sliders.forEach(s => {
        const val = parseFloat($(s.id).value);
        $(s.valId).textContent = val % 1 === 0 ? val.toFixed(0) : val.toFixed(1);
        score += val * s.weight;
    });
    $('preview-score').textContent = score.toFixed(2);
}

sliders.forEach(s => {
    $(s.id).addEventListener('input', updateSliderDisplay);
});

// ── Cambio de participante ─────────────────────────────────
sel.addEventListener('change', () => {
    scoreMsg.innerHTML = '';
    const opt = sel.options[sel.selectedIndex];
    const id  = sel.value;

    if (!id) {
        infoCard.classList.add('hidden');
        yaVoto.classList.add('hidden');
        panel.classList.add('hidden');
        return;
    }

    // ── LÓGICA VMD 2026: Camaleón de Modalidades ──
    const modalidad = opt.dataset.modalidad.toLowerCase();
    const lblCrit2 = $('text-criterio-2');
    const iconCrit2 = $('icon-criterio-2');
    
    if (modalidad === 'solo' || modalidad === 'solos') {
        lblCrit2.innerHTML = 'Creatividad <span class="weight-hint">× 30%</span>';
        iconCrit2.innerHTML = '<i class="bi bi-lightbulb-fill"></i>';
    } else {
        // Dúos, Tríos, Grupos...
        lblCrit2.innerHTML = 'Sincronización <span class="weight-hint">× 30%</span>';
        iconCrit2.innerHTML = '<i class="bi bi-people-fill"></i>';
    }

    // Info card
    infoCard.classList.remove('hidden');
    infoCard.innerHTML = `
        <span class="info-pill"><i class="bi bi-music-note me-1"></i>${escHtml(opt.dataset.modalidad)}</span>
        <span class="info-pill"><i class="bi bi-calendar me-1"></i>${escHtml(opt.dataset.categoria)}</span>
        <span class="info-pill"><i class="bi bi-palette me-1"></i>${escHtml(opt.dataset.estilo)}</span>
    `;

    const votado = opt.dataset.votado === '1';

    if (votado) {
        yaVoto.classList.remove('hidden');
        panel.classList.add('hidden');
    } else {
        yaVoto.classList.add('hidden');
        panel.classList.remove('hidden');
        // Reset sliders
        sliders.forEach(s => { $(s.id).value = 5; });
        updateSliderDisplay();
    }
});

// ── Submit ─────────────────────────────────────────────────
btnSubmit.addEventListener('click', async () => {
    const id = sel.value;
    if (!id) {
        showMsg('Selecciona un participante primero.', 'err');
        return;
    }

    // ── Nombres actualizados para la API ──
    const notas = {
        id_participante:     parseInt(id),
        nota_tecnica:        parseFloat($('sl-tecnica').value),
        nota_criterio_2:     parseFloat($('sl-criterio-2').value),
        nota_vestuario:      parseFloat($('sl-vestuario').value),
        nota_musicalizacion: parseFloat($('sl-musicalizacion').value),
    };

    // Validate
    if (Object.values(notas).some(v => typeof v === 'number' && (isNaN(v) || v < 1 || v > 10))) {
        showMsg('Valores inválidos. Todas las notas deben estar entre 1 y 10.', 'err');
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-sm me-2"></span>Enviando…';

    try {
        const r = await fetch('api/calificaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(notas),
        });
        const data = await r.json();

        if (data.ok) {
            showMsg(`<i class="bi bi-patch-check-fill me-2"></i>${data.message}`, 'ok');
            panel.classList.add('hidden');
            yaVoto.classList.remove('hidden');

            // Marcar la opción como ya votada
            sel.options[sel.selectedIndex].dataset.votado = '1';
            sel.options[sel.selectedIndex].text += ' ✓';

            // Scroll al top suavemente
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Reload stats después de 1.5s para reflejar cambio
            setTimeout(() => location.reload(), 2000);
        } else {
            showMsg(data.error || 'Error al enviar la calificación.', 'err');
        }
    } catch (e) {
        showMsg('Error de red. Verifica tu conexión.', 'err');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-send-fill me-2"></i>Enviar Calificación';
    }
});

// ── Helpers ────────────────────────────────────────────────
function showMsg(msg, type) {
    scoreMsg.innerHTML = `<div class="alert-${type === 'ok' ? 'success' : 'error'}-custom mb-3">${msg}</div>`;
    if (type !== 'ok') setTimeout(() => scoreMsg.innerHTML = '', 4000);
}
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Init
updateSliderDisplay();

// Sincronización en vivo con el Administrador
setInterval(async () => {
    try {
        const r = await fetch('api/estado_acto.php');
        const res = await r.json();
        const activo = res.acto_en_curso;

        const lock = $('pantalla-bloqueo');

        if (activo === 0) {
            // Si no hay acto, bajamos la cortina
            lock.style.opacity = '1';
            lock.style.pointerEvents = 'all';
        } else {
            // Si el admin habilitó un acto, subimos la cortina
            lock.style.opacity = '0';
            lock.style.pointerEvents = 'none';

            // Forzamos al dropdown a seleccionar al participante correcto automáticamente
            const sel = $('sel-participante');
            if (sel.value != activo) {
                sel.value = activo;
                sel.dispatchEvent(new Event('change')); // Actualiza la pantalla
            }
        }
    } catch(e) { }
}, 2000);
</script>
</body>
</html>