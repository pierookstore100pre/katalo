<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = validation_errors();
$ruta    = trim(service('request')->getPath(), '/');
$esDashboard  = $ruta === 'dashboard';
$esPerfil     = str_starts_with($ruta, 'negocio/perfil');
$esCategorias = str_starts_with($ruta, 'negocio/categorias');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title><?= esc($titulo ?? 'Panel') ?> · Katalo.pe</title>
    <style>
        :root {
            --primary: #0f766e;
            --primary-dark: #115e59;
            --primary-soft: #ccfbf1;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe4e2;
            --bg: #f4fbf9;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --success: #166534;
            --success-bg: #f0fdf4;
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
            max-width: 880px;
            margin: 0 auto;
            padding: 20px 14px 40px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            text-decoration: none;
            font-size: 1.28rem;
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

        .panel-nav {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            margin: 0 0 18px;
            padding: 0 0 4px;
            scrollbar-width: none;
        }

        .panel-nav::-webkit-scrollbar { display: none; }

        .panel-nav a {
            flex: 0 0 auto;
            padding: 9px 13px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 650;
        }

        .panel-nav a.active {
            border-color: var(--primary);
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            overflow-wrap: anywhere;
        }

        .alert ul {
            margin: 6px 0 0;
            padding-left: 18px;
        }

        .alert-error {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #fecaca;
        }

        .alert-success {
            color: var(--success);
            background: var(--success-bg);
            border: 1px solid #bbf7d0;
        }

        .card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 118, 110, 0.10);
            border-radius: var(--radius);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            padding: 22px 18px;
        }

        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .eyebrow {
            margin: 0 0 4px;
            color: var(--primary);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 11px 16px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            font: inherit;
            font-size: 0.94rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(15, 118, 110, 0.20);
        }

        .btn:hover { filter: brightness(1.04); }

        .btn-secondary {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--primary-dark);
            box-shadow: none;
        }

        .btn-danger {
            background: #fff;
            border: 1px solid #fecaca;
            color: var(--danger);
            box-shadow: none;
        }

        .btn-small {
            min-height: 34px;
            padding: 6px 10px;
            border-radius: 9px;
            font-size: 0.8rem;
        }

        .field { margin-bottom: 15px; }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            font-weight: 650;
            color: #334155;
        }

        .hint {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.78rem;
        }

        input[type="text"],
        input[type="tel"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.13);
        }

        .input-error {
            border-color: #f87171;
            background: #fffafa;
        }

        .field-error {
            margin: 5px 0 0;
            color: var(--danger);
            font-size: 0.8rem;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 7px 0;
        }

        .checkbox-row input {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        .checkbox-row label {
            margin: 0;
            font-weight: 650;
        }

        .form-actions { margin-top: 20px; }

        .category-list {
            display: grid;
            gap: 9px;
            margin-top: 18px;
        }

        .category-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 12px 12px calc(12px + (var(--nivel, 0) * 18px));
            border: 1px solid #e6efec;
            border-radius: 13px;
            background: #fff;
        }

        .category-main { min-width: 0; }

        .category-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .category-meta {
            margin-top: 2px;
            color: var(--muted);
            font-size: 0.8rem;
            overflow-wrap: anywhere;
        }

        .tree-line {
            display: inline-block;
            width: 12px;
            color: #99f6e4;
            font-weight: 800;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .category-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
            flex: 0 0 auto;
        }

        .category-actions form { display: inline; }

        .link-danger { color: var(--danger); text-decoration: none; }

        .empty-state {
            padding: 30px 18px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state h2 {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: 1.1rem;
        }

        .empty-state p { margin: 0 0 16px; }

        @media (min-width: 560px) {
            .shell { padding: 26px 20px 50px; }
            .card { padding: 26px 24px; }
            .grid-2 {
                grid-template-columns: 1fr 1fr;
                gap: 0 14px;
            }
        }

        @media (max-width: 640px) {
            .page-head { align-items: stretch; flex-direction: column; }
            .page-head .btn { width: 100%; }
            .form-actions {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 20;
                margin: 0;
                padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
                background: rgba(244, 251, 249, 0.95);
                border-top: 1px solid var(--line);
                backdrop-filter: blur(8px);
            }
            .form-actions .btn { width: 100%; max-width: 880px; margin: 0 auto; }
            body { padding-bottom: 86px; }
            .category-row { align-items: flex-start; flex-direction: column; }
            .category-actions { justify-content: flex-start; }
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

        <nav class="panel-nav" aria-label="Menú del panel">
            <a href="<?= base_url('dashboard') ?>" class="<?= $esDashboard ? 'active' : '' ?>">Panel</a>
            <a href="<?= base_url('negocio/perfil') ?>" class="<?= $esPerfil ? 'active' : '' ?>">Mi perfil</a>
            <a href="<?= base_url('negocio/categorias') ?>" class="<?= $esCategorias ? 'active' : '' ?>">Mis categorías</a>
        </nav>

        <?php if ($success !== null): ?>
            <div class="alert alert-success"><?= esc($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error">
                <strong>Revisa los siguientes datos:</strong>
                <ul>
                    <?php foreach ($errors as $mensaje): ?>
                        <li><?= esc($mensaje) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
