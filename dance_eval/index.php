<?php
// ============================================================
// index.php  –  Login unificado
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$nombreEvento = getNombreEvento();

// Si ya está autenticado, redirigir a su panel correspondiente
if (isLoggedIn()) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: panel_admin.php');
    } elseif ($_SESSION['rol'] === 'penalizador') {
        header('Location: panel_penalizador.php');
    } else {
        header('Location: panel_jurado.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            $pdo  = getPDO();
            $stmt = $pdo->prepare('SELECT id_usuario, password, rol FROM usuarios WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['rol']        = $user['rol'];
                $_SESSION['username']   = $username;

                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    header('Location: panel_admin.php');
                } elseif ($user['rol'] === 'penalizador') {
                    header('Location: panel_penalizador.php');
                } else {
                    header('Location: panel_jurado.php');
                }
                exit;
            } else {
                $error = 'Credenciales incorrectas. Intenta de nuevo.';
            }
        } catch (PDOException $e) {
            $error = 'Error del servidor. Inténtalo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nombreEvento) ?> — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #e63b6f;
            --accent2: #ff8c42;
            --surface: #0f0f14;
            --card-bg: #16161e;
            --border: rgba(255,255,255,0.07);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 80%, rgba(230,59,111,.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 10%, rgba(255,140,66,.12) 0%, transparent 55%);
            z-index: 0;
            animation: bgShift 8s ease-in-out infinite alternate;
        }
        @keyframes bgShift {
            from { transform: scale(1); }
            to   { transform: scale(1.08) translateX(2%); }
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 3.2rem;
            letter-spacing: .08em;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin: 0;
        }
        .brand-sub {
            font-size: .78rem;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: rgba(255,255,255,.4);
            margin-top: .25rem;
        }

        .card-login {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 2.4rem 2rem;
            box-shadow: 0 30px 80px rgba(0,0,0,.5);
        }

        .form-label { font-size: .8rem; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.5); }

        .form-control {
            background: rgba(255,255,255,.04) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: #fff !important;
            padding: .7rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(230,59,111,.2) !important;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none;
            border-radius: 10px;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.1rem;
            letter-spacing: .12em;
            color: #fff;
            padding: .75rem;
            width: 100%;
            transition: opacity .2s, transform .1s;
        }
        .btn-login:hover  { opacity: .9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .alert-error {
            background: rgba(230,59,111,.12);
            border: 1px solid rgba(230,59,111,.35);
            border-radius: 10px;
            color: #ff8fa5;
            font-size: .88rem;
            padding: .7rem 1rem;
        }

        .input-icon { position: relative; }
        .input-icon .bi {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.25);
            pointer-events: none;
        }
        .input-icon .form-control { padding-right: 2.5rem; }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 1.5rem 0;
        }

        .hint {
            font-size: .75rem;
            color: rgba(255,255,255,.25);
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="brand">
       <h1 class="text-center mb-4 text-accent" style="font-family: 'Bebas Neue', cursive; letter-spacing: 2px;">
    <?= htmlspecialchars($nombreEvento) ?>
</h1>
        <p class="brand-sub">Sistema de Evaluación en Tiempo Real</p>
    </div>

    <div class="card-login">
        <?php if ($error): ?>
            <div class="alert-error mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">Usuario</label>
                <div class="input-icon">
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="nombre de usuario" autocomplete="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <i class="bi bi-person"></i>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-icon">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" autocomplete="current-password">
                    <i class="bi bi-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-lightning-fill me-1"></i> Ingresar al Sistema
            </button>
        </form>

        <div class="divider"></div>
        <p class="hint">Acceso de jurado según registro</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>