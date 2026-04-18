<?php
// ============================================================
// panel_admin.php  –  Dashboard Administrador
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireLogin('admin');

$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$nombreEvento = htmlspecialchars(getNombreEvento());
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nombreEvento ?> — Panel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --accent:   #e63b6f;
            --accent2:  #ff8c42;
            --accent3:  #3be6b0;
            --surface:  #0c0c11;
            --card:     #13131a;
            --card2:    #1a1a24;
            --border:   rgba(255,255,255,.07);
            --text-dim: rgba(255,255,255,.45);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.6rem;
            letter-spacing: .08em;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        .topbar-meta { font-size: .8rem; color: var(--text-dim); }
        .live-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--accent3);
            margin-right: .4rem;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .4; transform: scale(.7); }
        }

        /* ── Nav Tabs ── */
        .nav-tabs .nav-link {
            color: var(--text-dim);
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            font-size: .82rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            padding: .75rem 1.2rem;
            transition: color .2s, border-color .2s;
        }
        .nav-tabs .nav-link:hover   { color: #fff; }
        .nav-tabs .nav-link.active  {
            color: var(--accent) !important;
            background: transparent !important;
            border-bottom-color: var(--accent) !important;
        }
        .nav-tabs { border-bottom: 1px solid var(--border); }

        /* ── Cards ── */
        .dash-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.4rem;
        }
        .dash-card-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.1rem;
            letter-spacing: .1em;
            color: var(--text-dim);
            margin-bottom: 1rem;
        }

        /* ── Tables ── */
        .table { font-size: .875rem; }
        .table thead th {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-dim);
            border-bottom-color: var(--border);
            background: var(--card2);
            padding: .65rem 1rem;
        }
        .table tbody td { padding: .7rem 1rem; vertical-align: middle; }
        .table-hover tbody tr:hover td { background: rgba(255,255,255,.03); }

        /* ── Badge jurado ── */
        .badge-voted   { background: rgba(59,230,176,.15); color: var(--accent3); border: 1px solid rgba(59,230,176,.3); }
        .badge-pending { background: rgba(255,255,255,.05); color: var(--text-dim); border: 1px solid var(--border); }

        /* ── Score bar ── */
        .score-bar-wrap { display: flex; align-items: center; gap: .6rem; }
        .score-bar {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: rgba(255,255,255,.08);
            overflow: hidden;
        }
        .score-bar-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            transition: width .4s ease;
        }
        .score-val { font-variant-numeric: tabular-nums; font-weight: 600; min-width: 2.8rem; text-align: right; }

        /* ── Rank badge ── */
        .rank-badge {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.1rem;
            width: 32px; height: 32px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .rank-1 { background: linear-gradient(135deg,#f6c745,#e6a800); color: #000; }
        .rank-2 { background: linear-gradient(135deg,#bfc8d0,#9aa5ad); color: #000; }
        .rank-3 { background: linear-gradient(135deg,#cd7f32,#9e5c1a); color: #fff; }
        .rank-n { background: rgba(255,255,255,.07); color: var(--text-dim); }

        /* ── Forms ── */
        .form-label-sm {
            font-size: .75rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--text-dim);
        }
        .form-control, .form-select {
            background: rgba(255,255,255,.04) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: #fff !important;
            font-size: .875rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(230,59,111,.18) !important;
        }
        .form-control::placeholder { color: rgba(255,255,255,.2) !important; }
        .form-select option { background: #1a1a24; }

        .btn-accent {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none; border-radius: 10px; color: #fff;
            font-weight: 500; letter-spacing: .03em;
            transition: opacity .2s, transform .1s;
        }
        .btn-accent:hover  { opacity: .88; transform: translateY(-1px); color: #fff; }
        .btn-accent:active { transform: translateY(0); }

        .btn-outline-dim {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-dim);
            border-radius: 8px;
            font-size: .8rem;
            transition: border-color .2s, color .2s;
        }
        .btn-outline-dim:hover { border-color: rgba(255,255,255,.3); color: #fff; }

        /* ── Alert toast ── */
        #toast-container {
            position: fixed;
            bottom: 1.5rem; right: 1.5rem;
            z-index: 9999;
            display: flex; flex-direction: column; gap: .5rem;
        }
        .toast-msg {
            padding: .7rem 1.2rem;
            border-radius: 10px;
            font-size: .85rem;
            animation: slideIn .25s ease;
            max-width: 320px;
        }
        .toast-ok  { background: rgba(59,230,176,.15); border: 1px solid rgba(59,230,176,.35); color: var(--accent3); }
        .toast-err { background: rgba(230,59,111,.15); border: 1px solid rgba(230,59,111,.35); color: #ff8fa5; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(1.5rem); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ── Misc ── */
        .update-flash { animation: flash .4s ease; }
        @keyframes flash {
            0%   { background: rgba(230,59,111,.08); }
            100% { background: transparent; }
        }
        .spinner-sm {
            display: inline-block; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.15);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .logout-btn {
            background: rgba(230,59,111,.12);
            border: 1px solid rgba(230,59,111,.25);
            color: var(--accent);
            border-radius: 8px;
            font-size: .8rem;
            padding: .3rem .85rem;
            transition: background .2s;
        }
        .logout-btn:hover { background: rgba(230,59,111,.22); color: var(--accent); }

        .filter-bar { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .filter-btn {
            padding: .3rem .9rem;
            border-radius: 20px;
            font-size: .78rem;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-dim);
            cursor: pointer;
            transition: all .2s;
        }
        .filter-btn.active, .filter-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-dim);
            font-size: .9rem;
        }
        .empty-state .bi { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .3; }
        .text-accent3 { color: var(--accent3); }
        .text-accent2 { color: var(--accent2); }
        .text-accent  { color: var(--accent); }

        /* Contenedor de la gráfica */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <h1 class="topbar-brand mb-0" id="header-brand-name"><?= $nombreEvento ?></h1>
        <span class="topbar-meta d-none d-md-inline">
            <span class="live-dot"></span>En vivo
        </span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-meta">
            <i class="bi bi-shield-fill me-1 text-accent"></i><?= $username ?>
        </span>
        <a href="logout.php" class="logout-btn text-decoration-none">
            <i class="bi bi-box-arrow-right me-1"></i>Salir
        </a>
    </div>
</header>

<main class="container-fluid py-4 px-3 px-md-4">

    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-monitor"  type="button"><i class="bi bi-activity me-1"></i>Monitor en Vivo</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-ranking"  type="button"><i class="bi bi-trophy me-1"></i>Ranking</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-jurados"  type="button"><i class="bi bi-people me-1"></i>Jurados</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-partics"  type="button"><i class="bi bi-person-plus me-1"></i>Participantes</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-ajustes"  type="button"><i class="bi bi-gear me-1"></i>Ajustes</button></li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-monitor">
            <div class="dash-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <p class="dash-card-title mb-0">Monitor en Vivo</p>
                    <span class="topbar-meta" id="monitor-timer"><span class="spinner-sm"></span></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Acto</th>
                                <th>Modalidad</th>
                                <th>Categoría</th>
                                <th>Estilo</th>
                                <th>Jurados</th>
                                <th>Votos</th>
                                <th>Control</th>
                            </tr>
                        </thead>
                        <tbody id="monitor-body">
                            <tr><td colspan="8" class="text-center py-4 text-muted"><span class="spinner-sm me-2"></span>Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-ranking">
            <div class="dash-card">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <p class="dash-card-title mb-0">Ranking Final</p>
                    <div>
                        <a href="api/reporte_ganadores.php" target="_blank" class="btn btn-success btn-sm me-2">
                            <i class="bi bi-printer me-1"></i>Generar Acta
                        </a>
                        <button class="btn btn-outline-dim btn-sm" id="btn-refresh-rank">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                    </div>
                </div>

                <div class="filter-bar" id="rank-filters-mod">
                    <button class="filter-btn active" data-mod="">Todas las Modalidades</button>
                    <button class="filter-btn" data-mod="Solo">Solo</button>
                    <button class="filter-btn" data-mod="Dúo">Dúo</button>
                    <button class="filter-btn" data-mod="Trío">Trío</button>
                    <button class="filter-btn" data-mod="Mini">Mini</button>
                    <button class="filter-btn" data-mod="Mega">Mega</button>
                </div>
                
                <div class="filter-bar" id="rank-filters-cat">
                    <button class="filter-btn active" data-cat="">Todas las Edades</button>
                    <button class="filter-btn" data-cat="Baby">Baby</button>
                    <button class="filter-btn" data-cat="Infantil">Infantil</button>
                    <button class="filter-btn" data-cat="Junior">Junior</button>
                    <button class="filter-btn" data-cat="Senior">Senior</button>
                    <button class="filter-btn" data-cat="Master">Master</button>
                    <button class="filter-btn" data-cat="Mixta">Mixta</button>
                </div>

                <div class="chart-container">
                    <canvas id="rankingChart"></canvas>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pos.</th>
                                <th>Acto</th>
                                <th>Categoría</th>
                                <th>Modalidad</th>
                                <th>Estilo</th>
                                <th>Técnica</th>
                                <th>Artística</th>
                                <th>Musical.</th>
                                <th>Escena</th>
                                <th>Puntaje Final</th>
                                <th>Jurados</th>
                            </tr>
                        </thead>
                        <tbody id="ranking-body">
                            <tr><td colspan="11" class="text-center py-4 text-muted"><span class="spinner-sm me-2"></span>Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-jurados">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="dash-card h-100">
                        <p class="dash-card-title">Registrar Jurado</p>
                        <div id="jurado-form-msg"></div>
                        <div class="mb-3">
                            <label class="form-label-sm">Usuario</label>
                            <input type="text" id="j-username" class="form-control" placeholder="ej. jurado_ana">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-sm">Contraseña</label>
                            <input type="password" id="j-password" class="form-control" placeholder="mínimo 6 caracteres">
                        </div>
                        <button class="btn btn-accent w-100" id="btn-add-jurado">
                            <i class="bi bi-person-plus me-1"></i>Registrar Jurado
                        </button>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="dash-card">
                        <p class="dash-card-title">Jurados Registrados</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Registrado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="jurados-list">
                                    <tr><td colspan="5" class="text-center py-4 text-muted"><span class="spinner-sm me-2"></span>Cargando…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-partics">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="dash-card h-100">
                        <p class="dash-card-title">Registrar Participante</p>
                        <div id="part-form-msg"></div>
                        <div class="mb-3">
                            <label class="form-label-sm">Nombre del Acto</label>
                            <input type="text" id="p-nombre" class="form-control" placeholder="ej. Ballet Estrella">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-sm">Modalidad</label>
                            <select id="p-modalidad" class="form-select">
                                <option value="">-- selecciona --</option>
                                <option>Solo</option><option>Dúo</option>
                                <option>Trío</option><option>Mini</option><option>Mega</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-sm">Categoría Edad</label>
                            <select id="p-categoria" class="form-select">
                                <option value="">-- selecciona --</option>
                                <option>Baby</option><option>Infantil</option>
                                <option>Junior</option><option>Senior</option><option>Master</option><option>Mixta</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-sm">Estilo</label>
                            <input type="text" id="p-estilo" class="form-control" placeholder="ej. Ballet, Hip-Hop, Jazz…">
                        </div>
                        <button class="btn btn-accent w-100" id="btn-add-partic">
                            <i class="bi bi-music-note-beamed me-1"></i>Registrar Acto
                        </button>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="dash-card">
                        <p class="dash-card-title">Participantes Registrados</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Acto</th>
                                        <th>Modalidad</th>
                                        <th>Categoría</th>
                                        <th>Estilo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="partics-list">
                                    <tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-sm me-2"></span>Cargando…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-ajustes">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="dash-card">
                        <p class="dash-card-title">Configuración Global</p>
                        <div id="ajustes-form-msg"></div>
                        <div class="mb-4">
                            <label class="form-label-sm">Nombre de la Competencia / Evento</label>
                            <input type="text" id="cfg-nombre-evento" class="form-control" value="<?= $nombreEvento ?>" placeholder="Ej: Dance Fest 2026">
                            <small class="text-dim mt-2 d-block">Este nombre aparecerá en la pantalla de los jueces y en las actas impresas.</small>
                        </div>
                        <button class="btn btn-accent w-100" id="btn-save-ajustes">
                            <i class="bi bi-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background-color: var(--card); border: 1px solid var(--border);">
      <div class="modal-header" style="border-bottom: 1px solid var(--border);">
        <h5 class="modal-title" style="font-family: 'Bebas Neue', cursive; letter-spacing: 2px;">
            Desglose de Notas: <span id="detalle-nombre-acto" class="text-accent"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Jurado</th>
                        <th>Técnica (40%)</th>
                        <th>Artística (30%)</th>
                        <th>Musicalidad (20%)</th>
                        <th>Escena (10%)</th>
                    </tr>
                </thead>
                <tbody id="detalle-body">
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============================================================
// panel_admin.js
// ============================================================

const $ = id => document.getElementById(id);
let monitorInterval = null;
let rankCategory    = '';
let rankModalidad   = '';
let barChart = null;

function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `toast-msg toast-${type}`;
    el.innerHTML = `<i class="bi bi-${type === 'ok' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}`;
    $('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

async function api(url, data = null) {
    const opts = data
        ? { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) }
        : { method: 'GET' };
    const r = await fetch(url, opts);
    return r.json();
}

// ── TAB MONITOR ──
async function loadMonitor() {
    try {
        const [data, statusData] = await Promise.all([
            api('api/monitor.php'),
            fetch('api/estado_acto.php').then(r => r.json())
        ]);
        
        const tbody = $('monitor-body');
        let actoEnCurso = statusData.acto_en_curso;

        if (!data.participantes || data.participantes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">No hay participantes registrados.</div></td></tr>`;
            return;
        }

        tbody.innerHTML = data.participantes.map((p, i) => {
            const juradosBadges = data.jurados.map(j => {
                const voted = p.votaron.includes(j.id_usuario.toString());
                return `<span class="badge ${voted ? 'badge-voted' : 'badge-pending'} me-1">${voted ? '✓' : '○'} ${escHtml(j.username)}</span>`;
            }).join('');

            const totalJurados = data.jurados.length;
            const totalVotos   = p.votaron.length;

            let btnControl = '';
            if (actoEnCurso === p.id_participante) {
                btnControl = `<button class="btn btn-sm btn-danger fw-bold" onclick="setActo(0)"><i class="bi bi-stop-circle me-1"></i>Cerrar Votación</button>`;
            } else if (actoEnCurso === 0) {
                btnControl = `<button class="btn btn-sm btn-success fw-bold" onclick="setActo(${p.id_participante})"><i class="bi bi-play-circle me-1"></i>Habilitar</button>`;
            } else {
                btnControl = `<button class="btn btn-sm btn-outline-secondary" disabled>En Espera</button>`;
            }

            return `<tr>
                <td><span class="text-dim">${i+1}</span></td>
                <td><strong>${escHtml(p.nombre_acto)}</strong></td>
                <td><span class="badge bg-secondary">${escHtml(p.modalidad)}</span></td>
                <td>${escHtml(p.categoria_edad)}</td>
                <td class="text-accent2">${escHtml(p.estilo)}</td>
                <td>${juradosBadges || '<span class="text-muted">—</span>'}</td>
                <td><span class="text-accent3 fw-bold">${totalVotos}/${totalJurados}</span></td>
                <td>${btnControl}</td>
            </tr>`;
        }).join('');
        
        const now = new Date();
        $('monitor-timer').innerHTML = `<span class="text-accent3"><i class="bi bi-clock me-1"></i>Actualizado ${now.toLocaleTimeString('es')}</span>`;
    } catch(e) { console.error(e); }
}

async function setActo(id) {
    await api('api/estado_acto.php', { id_participante: id });
    loadMonitor(); 
}
function startMonitor() {
    loadMonitor();
    monitorInterval = setInterval(loadMonitor, 3000);
}
function stopMonitor() {
    clearInterval(monitorInterval);
    monitorInterval = null;
}

// ── TAB RANKING ──
async function loadRanking() {
    const tbody = $('ranking-body');
    tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4"><span class="spinner-sm me-2"></span>Cargando…</td></tr>`;
    try {
        const url = `api/ranking.php?cat=${encodeURIComponent(rankCategory)}&mod=${encodeURIComponent(rankModalidad)}`;
        const data = await api(url);
        
        if (data.error) {
            tbody.innerHTML = `<tr><td colspan="11" class="text-danger fw-bold text-center py-4">${escHtml(data.error)}</td></tr>`;
            return;
        }

        if (!data.ranking || data.ranking.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state"><i class="bi bi-trophy"></i>Sin datos de ranking aún.</div></td></tr>`;
            if(barChart) barChart.destroy();
            return;
        }

        tbody.innerHTML = data.ranking.map((r, i) => {
            const pos = i + 1;
            const cls = pos === 1 ? 'rank-1' : pos === 2 ? 'rank-2' : pos === 3 ? 'rank-3' : 'rank-n';
            const pct = (parseFloat(r.puntaje_final) / 10 * 100).toFixed(1);
            return `<tr>
                <td><span class="rank-badge ${cls}">${pos}</span></td>
                <td><strong>${escHtml(r.nombre_acto)}</strong></td>
                <td><span class="badge bg-secondary">${escHtml(r.categoria_edad)}</span></td>
                <td>${escHtml(r.modalidad)}</td>
                <td class="text-accent2">${escHtml(r.estilo)}</td>
                <td>${fmtNote(r.avg_tecnica)}</td>
                <td>${fmtNote(r.avg_artistica)}</td>
                <td>${fmtNote(r.avg_musical)}</td>
                <td>${fmtNote(r.avg_escena)}</td>
                <td>
                    <div class="score-bar-wrap">
                        <div class="score-bar"><div class="score-bar-fill" style="width:${pct}%"></div></div>
                        <span class="score-val text-accent">${parseFloat(r.puntaje_final).toFixed(2)}</span>
                    </div>
                </td>
               <td>
                    <button class="btn btn-outline-dim btn-sm" onclick="verDetalles(${r.id_participante}, '${escHtml(r.nombre_acto)}')">
                        <i class="bi bi-eye me-1"></i>Ver ${r.num_jurados}
                    </button>
                </td>
            </tr>`;
        }).join('');

        const labels = data.ranking.map(r => r.nombre_acto);
        const scores = data.ranking.map(r => parseFloat(r.puntaje_final).toFixed(2));

        if(barChart) { barChart.destroy(); }

        const ctx = document.getElementById('rankingChart').getContext('2d');
        barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: 'Puntaje Final', data: scores, backgroundColor: '#e63b6f', borderRadius: 5, borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 10, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.5)' } },
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 11 } } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#13131a', titleColor: '#e63b6f', bodyColor: '#fff', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1 }
                }
            }
        });
    } catch(e) { console.error(e); }
}

$('rank-filters-mod').addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;
    $('rank-filters-mod').querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    rankModalidad = btn.dataset.mod;
    loadRanking();
});

$('rank-filters-cat').addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;
    $('rank-filters-cat').querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    rankCategory = btn.dataset.cat;
    loadRanking();
});

$('btn-refresh-rank').addEventListener('click', () => loadRanking());

async function verDetalles(idParticipante, nombreActo) {
    $('detalle-nombre-acto').innerText = nombreActo;
    $('detalle-body').innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><span class="spinner-sm me-2"></span>Buscando notas...</td></tr>`;
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    modal.show();

    try {
        const data = await api(`api/detalles.php?id=${idParticipante}`);
        const tbody = $('detalle-body');
        
        if (!data.detalles || data.detalles.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Aún no hay votos registrados.</td></tr>`;
            return;
        }

        tbody.innerHTML = data.detalles.map(d => `
            <tr>
                <td><i class="bi bi-person-circle me-2 text-accent3"></i><strong>${escHtml(d.username)}</strong></td>
                <td>${fmtNote(d.nota_tecnica)}</td>
                <td>${fmtNote(d.nota_artistica)}</td>
                <td>${fmtNote(d.nota_musical)}</td>
                <td>${fmtNote(d.nota_escena)}</td>
            </tr>
        `).join('');

    } catch(e) { $('detalle-body').innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error de conexión.</td></tr>`; }
}

// ── TAB JURADOS ──
async function loadJurados() {
    try {
        const data = await api('api/jurados.php?action=list');
        const tbody = $('jurados-list');
        if (!data.jurados || data.jurados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="bi bi-people"></i>No hay jurados registrados.</div></td></tr>`;
            return;
        }
        tbody.innerHTML = data.jurados.map(j => `
            <tr>
                <td class="text-muted">${j.id_usuario}</td>
                <td><i class="bi bi-person-circle me-2 text-accent"></i>${escHtml(j.username)}</td>
                <td><span class="badge ${j.rol === 'admin' ? 'bg-danger' : 'badge-voted'}">${j.rol}</span></td>
                <td class="text-muted" style="font-size:.8rem">${j.created_at}</td>
                <td>
                    ${j.rol !== 'admin' ? `<button class="btn btn-outline-dim btn-sm" onclick="deleteJurado(${j.id_usuario}, '${escHtml(j.username)}')">
                        <i class="bi bi-trash3"></i>
                    </button>` : '<span class="text-muted">—</span>'}
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error(e); }
}

$('btn-add-jurado').addEventListener('click', async () => {
    const u = $('j-username').value.trim();
    const p = $('j-password').value.trim();
    const msgEl = $('jurado-form-msg');
    msgEl.innerHTML = '';
    if (!u || !p) { showFormMsg(msgEl, 'Completa todos los campos.', 'err'); return; }
    if (p.length < 6) { showFormMsg(msgEl, 'La contraseña debe tener al menos 6 caracteres.', 'err'); return; }

    const btn = $('btn-add-jurado');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-sm me-2"></span>Registrando…';

    try {
        const res = await api('api/jurados.php?action=create', { username: u, password: p });
        if (res.ok) {
            showFormMsg(msgEl, res.message, 'ok');
            $('j-username').value = ''; $('j-password').value = '';
            loadJurados();
            toast(res.message);
        } else { showFormMsg(msgEl, res.error, 'err'); }
    } catch(e) { showFormMsg(msgEl, 'Error de red.', 'err'); } 
    finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Registrar Jurado'; }
});

async function deleteJurado(id, name) {
    if (!confirm(`¿Eliminar al jurado "${name}"? Se borrarán sus calificaciones.`)) return;
    try {
        const res = await api('api/jurados.php?action=delete', { id_usuario: id });
        if (res.ok) { toast(res.message); loadJurados(); loadMonitor(); }
        else toast(res.error, 'err');
    } catch(e) { toast('Error de red.', 'err'); }
}

// ── TAB PARTICIPANTES ──
async function loadPartics() {
    try {
        const data = await api('api/participantes.php?action=list');
        const tbody = $('partics-list');
        if (!data.participantes || data.participantes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="bi bi-music-note-list"></i>No hay participantes aún.</div></td></tr>`;
            return;
        }
        tbody.innerHTML = data.participantes.map(p => `
            <tr>
                <td class="text-muted">${p.id_participante}</td>
                <td><strong>${escHtml(p.nombre_acto)}</strong></td>
                <td><span class="badge bg-secondary">${escHtml(p.modalidad)}</span></td>
                <td>${escHtml(p.categoria_edad)}</td>
                <td class="text-accent2">${escHtml(p.estilo)}</td>
                <td>
                    <button class="btn btn-outline-dim btn-sm" onclick="deletePartic(${p.id_participante}, '${escHtml(p.nombre_acto)}')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error(e); }
}

$('btn-add-partic').addEventListener('click', async () => {
    const nombre    = $('p-nombre').value.trim();
    const modalidad = $('p-modalidad').value;
    const categoria = $('p-categoria').value;
    const estilo    = $('p-estilo').value.trim();
    const msgEl     = $('part-form-msg');
    msgEl.innerHTML = '';

    if (!nombre || !modalidad || !categoria || !estilo) { showFormMsg(msgEl, 'Completa todos los campos.', 'err'); return; }

    const btn = $('btn-add-partic');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-sm me-2"></span>Registrando…';

    try {
        const res = await api('api/participantes.php?action=create', { nombre_acto: nombre, modalidad, categoria_edad: categoria, estilo });
        if (res.ok) {
            showFormMsg(msgEl, res.message, 'ok');
            $('p-nombre').value = ''; $('p-modalidad').value = ''; $('p-categoria').value = ''; $('p-estilo').value = '';
            loadPartics();
            toast(res.message);
        } else { showFormMsg(msgEl, res.error, 'err'); }
    } catch(e) { showFormMsg(msgEl, 'Error de red.', 'err'); } 
    finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-music-note-beamed me-1"></i>Registrar Acto'; }
});

async function deletePartic(id, name) {
    if (!confirm(`¿Eliminar el participante "${name}"? Se borrarán todas sus calificaciones.`)) return;
    try {
        const res = await api('api/participantes.php?action=delete', { id_participante: id });
        if (res.ok) { toast(res.message); loadPartics(); loadMonitor(); }
        else toast(res.error, 'err');
    } catch(e) { toast('Error de red.', 'err'); }
}

// ── TAB AJUSTES ──
$('btn-save-ajustes').addEventListener('click', async () => {
    const nombre = $('cfg-nombre-evento').value.trim();
    const msgEl = $('ajustes-form-msg');
    msgEl.innerHTML = '';
    
    if (!nombre) { showFormMsg(msgEl, 'El nombre del evento no puede estar vacío.', 'err'); return; }
    
    const btn = $('btn-save-ajustes');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-sm me-2"></span>Guardando...';
    
    try {
        const res = await api('api/ajustes.php', { nombre_evento: nombre });
        if (res.ok) {
            showFormMsg(msgEl, res.message, 'ok');
            toast(res.message);
            $('header-brand-name').innerText = nombre;
            document.title = nombre + ' — Panel Admin';
        } else {
            showFormMsg(msgEl, res.error, 'err');
        }
    } catch(e) {
        showFormMsg(msgEl, 'Error de conexión al guardar.', 'err');
    } finally {
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar Cambios';
    }
});

// ── Helpers ──
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtNote(v) {
    if (v === null || v === undefined) return '<span class="text-muted">—</span>';
    return `<span style="font-variant-numeric:tabular-nums">${parseFloat(v).toFixed(2)}</span>`;
}
function showFormMsg(el, msg, type) {
    el.innerHTML = `<div class="toast-msg toast-${type} mb-3">${msg}</div>`;
    setTimeout(() => el.innerHTML = '', 4000);
}

// ── Tab switching ──
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', e => {
        const target = e.target.getAttribute('data-bs-target');
        if (target === '#tab-monitor') { startMonitor(); } 
        else {
            stopMonitor();
            if (target === '#tab-ranking')  loadRanking(); 
            if (target === '#tab-jurados')  loadJurados();
            if (target === '#tab-partics')  loadPartics();
        }
    });
    btn.addEventListener('hide.bs.tab', e => {
        if (e.target.getAttribute('data-bs-target') === '#tab-monitor') stopMonitor();
    });
});

startMonitor();
loadJurados();
loadPartics();
// --- AUTO-ACTUALIZACIÓN EN TIEMPO REAL ---
setInterval(() => {
    const rankingTab = document.getElementById('tab-ranking');
    // CORRECCIÓN: Bootstrap usa 'active' para la pestaña visible
    if (rankingTab && rankingTab.classList.contains('active')) {
        loadRanking(); 
    }
}, 3000);
</script>
</body>
</html>