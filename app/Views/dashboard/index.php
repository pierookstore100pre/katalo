<?php
$nombreComercial = session()->get('nombre_comercial') ?? $negocio['nombre_comercial'];
$email           = session()->get('email') ?? '';
$rolNegocio      = session()->get('rol_negocio') ?? 'propietario';
$flashError      = session()->getFlashdata('error');
$flashSuccess    = session()->getFlashdata('success');
$tieneReportes   = ! empty($tiene_reportes);
$esPlanPro       = ! empty($es_plan_pro);
$esPropietario   = $rolNegocio === 'propietario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>Panel · Katalo.pe</title>
    <style>
        :root {
            --primary: #0f766e;
            --primary-dark: #115e59;
            --primary-soft: #ccfbf1;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe4e2;
            --bg: #f4fbf9;
            --radius: 18px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 0%, #ccfbf1 0, transparent 36%),
                radial-gradient(circle at 90% 5%, #d1fae5 0, transparent 30%),
                var(--bg);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.5;
        }
        a { color: var(--primary-dark); font-weight: 600; }
        .shell {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .brand-mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--primary), #14b8a6);
            color: #fff;
            box-shadow: 0 8px 20px rgba(15, 118, 110, 0.22);
        }
        .logout {
            padding: 9px 13px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            text-decoration: none;
            font-size: 0.86rem;
            white-space: nowrap;
        }
        .card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 118, 110, 0.10);
            border-radius: var(--radius);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            padding: 24px 20px;
        }
        .eyebrow {
            margin: 0 0 6px;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.45rem;
            letter-spacing: -0.02em;
        }
        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 0.94rem;
        }
        .alert {
            margin: 16px 0 0;
            padding: 11px 13px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .alert-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }
        .alert-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }
        .meta-list {
            display: grid;
            gap: 0;
            margin: 22px 0 0;
        }
        .meta-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #eef4f2;
            font-size: 0.93rem;
        }
        .meta-item:last-child { border-bottom: 0; }
        .meta-item span:first-child { color: var(--muted); }
        .meta-item span:last-child { text-align: right; font-weight: 650; overflow-wrap: anywhere; }
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .notice {
            margin-top: 18px;
            padding: 13px 14px;
            border: 1px dashed #99f6e4;
            border-radius: 12px;
            background: #f0fdfa;
            color: #115e59;
            font-size: 0.88rem;
        }
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 16px;
        }
        @media (max-width: 480px) {
            .shell { padding: 16px 12px 30px; }
            .card { padding: 20px 16px; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="topbar">
            <a class="brand" href="<?= base_url('dashboard') ?>">
                <span class="brand-mark">K</span>
                <span>Katalo.pe</span>
            </a>
            <a class="logout" href="<?= base_url('auth/logout') ?>">Cerrar sesión</a>
        </div>

        <section class="card">
            <p class="eyebrow">Panel de tu negocio</p>
            <h1><?= esc($nombreComercial) ?></h1>
            <p class="subtitle">Tu cuenta está activa y lista para comenzar.</p>

            <?php if ($flashError): ?>
                <div class="alert alert-error"><?= esc($flashError) ?></div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?= esc($flashSuccess) ?></div>
            <?php endif; ?>

            <div class="meta-list">
                <div class="meta-item">
                    <span>Correo</span>
                    <span><?= esc($email) ?></span>
                </div>
                <div class="meta-item">
                    <span>Rol</span>
                    <span class="badge"><?= esc($rolNegocio) ?></span>
                </div>
                <div class="meta-item">
                    <span>Plan actual</span>
                    <span><?= esc($plan['nombre'] ?? 'Sin plan vigente') ?></span>
                </div>
                <div class="meta-item">
                    <span>Estado de suscripción</span>
                    <span><?= esc($suscripcion['estado'] ?? 'Sin suscripción') ?></span>
                </div>
                <div class="meta-item">
                    <span>Distrito</span>
                    <span>#<?= esc((string) $negocio['distrito_id']) ?></span>
                </div>
            </div>

            <div class="notice">
                Revisa y gestiona los pedidos que llegan desde tu catálogo digital.
            </div>

            <div class="quick-links">
                <a class="logout" href="<?= esc(base_url(($ciudad_slug ?? '') . '/' . $negocio['slug']), 'attr') ?>" target="_blank" rel="noopener noreferrer">Ver mi catálogo</a>
                <a class="logout" href="<?= base_url('negocio/perfil') ?>">Mi perfil</a>
                <a class="logout" href="<?= base_url('negocio/categorias') ?>">Mis categorías</a>
                <a class="logout" href="<?= base_url('negocio/productos') ?>">Mis productos</a>
                <a class="logout" href="<?= base_url('negocio/pedidos') ?>">Mis Pedidos</a>
                <a class="logout" href="<?= base_url('negocio/mi-plan') ?>">Mi Plan</a>
                <?php if ($tieneReportes): ?>
                    <a class="logout" href="<?= base_url('negocio/reportes') ?>">Reportes</a>
                <?php endif; ?>
                <?php if ($esPlanPro && $esPropietario): ?>
                    <a class="logout" href="<?= base_url('negocio/colaboradores') ?>">Colaboradores</a>
                <?php endif; ?>
                <a class="logout" href="<?= base_url('negocio/logs') ?>">Registro de actividad</a>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
