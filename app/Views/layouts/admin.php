<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = validation_errors();
$ruta    = trim(service('request')->getPath(), '/');

$menu = [
    ['ruta' => 'admin/dashboard', 'label' => 'Dashboard', 'icono' => '◧'],
    ['ruta' => 'admin/reportes', 'label' => 'Reportes', 'icono' => '▤'],
    ['ruta' => 'admin/ciudades', 'label' => 'Ciudades', 'icono' => '◉'],
    ['ruta' => 'admin/distritos', 'label' => 'Distritos', 'icono' => '⌖'],
    ['ruta' => 'admin/negocios', 'label' => 'Negocios', 'icono' => '▣'],
    ['ruta' => 'admin/moderacion', 'label' => 'Moderación', 'icono' => '⚑'],
    ['ruta' => 'admin/planes', 'label' => 'Planes', 'icono' => '◆'],
    ['ruta' => 'admin/pagos', 'label' => 'Pagos', 'icono' => 'S/'],
    ['ruta' => 'admin/configuracion', 'label' => 'Configuración', 'icono' => '⚙'],
    ['ruta' => 'admin/logs', 'label' => 'Registro de actividad', 'icono' => '≡'],
];

$esActivo = static function (string $base) use ($ruta): bool {
    return $ruta === $base || str_starts_with($ruta, $base . '/');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b1220">
    <title><?= esc($titulo ?? 'Administración') ?> · Katalo.pe Admin</title>
    <style>
        :root {
            --sidebar: #0b1220;
            --sidebar-soft: #131f35;
            --sidebar-line: #22304a;
            --accent: #38bdf8;
            --accent-soft: #e0f2fe;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f1f5f9;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --success: #166534;
            --success-bg: #f0fdf4;
            --warning: #92400e;
            --warning-bg: #fef3c7;
            --radius: 16px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.5;
        }

        a { color: #0369a1; font-weight: 600; }

        .admin-shell { display: flex; min-height: 100vh; }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            width: 264px;
            padding: 20px 14px;
            background: linear-gradient(180deg, #0b1220, #101b30);
            color: #cbd5e1;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
        }

        .sidebar.open { transform: translateX(0); }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px 18px;
            color: #f8fafc;
            text-decoration: none;
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 11px;
            background: linear-gradient(145deg, #0284c7, #38bdf8);
            color: #fff;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.25);
        }

        .brand small {
            display: block;
            color: var(--accent);
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: grid;
            gap: 4px;
            margin-top: 6px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #b6c2d4;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .sidebar-nav a:hover { background: var(--sidebar-soft); color: #f8fafc; }

        .sidebar-nav a.active {
            background: rgba(56, 189, 248, 0.14);
            color: #e0f2fe;
            box-shadow: inset 3px 0 0 var(--accent);
        }

        .nav-icon {
            display: grid;
            place-items: center;
            width: 22px;
            font-size: 0.86rem;
            color: var(--accent);
        }

        .sidebar-foot {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--sidebar-line);
        }

        .sidebar-foot a {
            display: block;
            padding: 9px 12px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .sidebar-foot a:hover { background: var(--sidebar-soft); color: #fff; }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            z-index: 30;
            background: rgba(2, 6, 23, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
        }

        .sidebar-overlay.open { opacity: 1; visibility: visible; }

        .admin-main {
            flex: 1;
            min-width: 0;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(8px);
        }

        .menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            font-size: 1.15rem;
            cursor: pointer;
        }

        .topbar-title {
            flex: 1;
            min-width: 0;
            font-size: 0.96rem;
            font-weight: 750;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .logout {
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            text-decoration: none;
            font-size: 0.82rem;
            white-space: nowrap;
        }

        .admin-content {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 20px 14px 44px;
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            overflow-wrap: anywhere;
        }

        .alert ul { margin: 6px 0 0; padding-left: 18px; }

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
            padding: 20px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: #fff;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
        }

        .card + .card { margin-top: 18px; }

        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .eyebrow {
            margin: 0 0 4px;
            color: #0284c7;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        h1 { margin: 0 0 4px; font-size: 1.4rem; letter-spacing: -0.02em; }
        h2 { margin: 0 0 10px; font-size: 1.05rem; }

        .subtitle { margin: 0; color: var(--muted); font-size: 0.9rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 9px 14px;
            border: 0;
            border-radius: 11px;
            background: linear-gradient(145deg, #0284c7, #0369a1);
            color: #fff;
            font: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.2);
        }

        .btn:hover { filter: brightness(1.05); }

        .btn-secondary {
            background: #fff;
            border: 1px solid var(--line);
            color: #0369a1;
            box-shadow: none;
        }

        .btn-danger {
            background: #fff;
            border: 1px solid #fecaca;
            color: var(--danger);
            box-shadow: none;
        }

        .btn-success {
            background: #fff;
            border: 1px solid #bbf7d0;
            color: var(--success);
            box-shadow: none;
        }

        .btn-small {
            min-height: 32px;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.78rem;
        }

        .actions { display: flex; flex-wrap: wrap; gap: 6px; }
        .actions form { display: inline; }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 16px;
            padding: 14px;
            border: 1px solid #e6edf5;
            border-radius: 14px;
            background: #f8fafc;
        }

        .filter-field label {
            display: block;
            margin-bottom: 4px;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 8px;
        }

        input[type="text"],
        input[type="search"],
        input[type="number"],
        input[type="date"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            padding: 8px 11px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 0.92rem;
            outline: none;
        }

        textarea { min-height: 88px; resize: vertical; }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.13);
        }

        .field { margin-bottom: 14px; }
        .field label { display: block; margin-bottom: 5px; font-size: 0.86rem; font-weight: 700; color: #334155; }
        .hint { display: block; margin-top: 4px; color: var(--muted); font-size: 0.76rem; }

        .grid-2 { display: grid; grid-template-columns: 1fr; gap: 0 14px; }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
        }

        .checkbox-row input { width: 19px; height: 19px; accent-color: #0284c7; }
        .checkbox-row label { margin: 0; font-weight: 650; }

        .form-actions { margin-top: 20px; }

        .table-wrap { width: 100%; overflow-x: auto; }

        .data-table { width: 100%; border-collapse: collapse; }

        .data-table thead { display: none; }
        .data-table tbody { display: block; }

        .data-table tr {
            display: block;
            margin-bottom: 10px;
            padding: 12px;
            border: 1px solid #e6edf5;
            border-radius: 13px;
            background: #fff;
        }

        .data-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
            border: 0;
            font-size: 0.85rem;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .data-table td::before {
            content: attr(data-label);
            flex: 0 0 auto;
            color: var(--muted);
            font-weight: 700;
            text-align: left;
        }

        .data-table td[data-label="Acciones"]::before { display: none; }
        .data-table td[data-label="Acciones"] { padding-top: 9px; }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .badge-active { background: #dcfce7; color: #166534; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-info { background: var(--accent-soft); color: #075985; }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            padding: 15px 14px;
            border: 1px solid #e6edf5;
            border-radius: 14px;
            background: linear-gradient(145deg, #f8fafc, #ffffff);
        }

        .stat-label {
            margin: 0;
            color: var(--muted);
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stat-value {
            margin: 4px 0 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            overflow-wrap: anywhere;
        }

        .stat-value.small { font-size: 1.15rem; }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .empty-state {
            padding: 28px 16px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state h2 { margin: 0 0 6px; color: var(--ink); font-size: 1.05rem; }
        .empty-state p { margin: 0 0 14px; }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
        }

        .pagination li a,
        .pagination li span {
            display: inline-block;
            min-width: 34px;
            padding: 6px 9px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #0369a1;
            text-align: center;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 650;
        }

        .pagination li.active a,
        .pagination li.active span {
            border-color: #0284c7;
            background: #0284c7;
            color: #fff;
        }

        .pagination li.disabled span { color: #cbd5e1; }

        .meta-list { display: grid; gap: 0; margin: 0; }

        .meta-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 0.9rem;
        }

        .meta-item:last-child { border-bottom: 0; }
        .meta-item span:first-child { color: var(--muted); }
        .meta-item span:last-child { text-align: right; font-weight: 650; overflow-wrap: anywhere; }

        .notice {
            margin: 14px 0;
            padding: 12px 14px;
            border: 1px dashed #bae6fd;
            border-radius: 12px;
            background: #f0f9ff;
            color: #075985;
            font-size: 0.86rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 20px 0 10px;
        }

        .section-title:first-of-type { margin-top: 0; }

        @media (min-width: 620px) {
            .filter-form { grid-template-columns: repeat(2, 1fr); }
            .filter-actions { grid-column: 1 / -1; }
            .grid-2 { grid-template-columns: 1fr 1fr; }
            .stat-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (min-width: 980px) {
            .sidebar { transform: translateX(0); }
            .sidebar-overlay { display: none; }
            .admin-main { margin-left: 264px; }
            .menu-toggle { display: none; }
            .admin-content { padding: 26px 24px 54px; }
            .card { padding: 24px 22px; }
            .filter-form { grid-template-columns: repeat(4, 1fr); }
            .stat-grid { grid-template-columns: repeat(5, 1fr); }

            .data-table thead { display: table-header-group; }
            .data-table tbody { display: table-row-group; }

            .data-table th {
                padding: 10px 8px;
                border-bottom: 1px solid var(--line);
                color: var(--muted);
                font-size: 0.73rem;
                font-weight: 750;
                text-align: left;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .data-table tr {
                display: table-row;
                margin: 0;
                padding: 0;
                border: 0;
                border-bottom: 1px solid #eef2f7;
                border-radius: 0;
            }

            .data-table td {
                display: table-cell;
                padding: 11px 8px;
                border-bottom: 1px solid #eef2f7;
                text-align: left;
                vertical-align: middle;
            }

            .data-table td::before { display: none; }
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="<?= base_url('admin/dashboard') ?>">
                <span class="brand-mark">K</span>
                <span>Katalo.pe<small>Administración</small></span>
            </a>

            <nav class="sidebar-nav" aria-label="Menú de administración">
                <?php foreach ($menu as $item): ?>
                    <a href="<?= base_url($item['ruta']) ?>" class="<?= $esActivo($item['ruta']) ? 'active' : '' ?>">
                        <span class="nav-icon" aria-hidden="true"><?= esc($item['icono']) ?></span>
                        <span><?= esc($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-foot">
                <a href="<?= base_url('auth/logout') ?>">Cerrar sesión</a>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú">☰</button>
                <div class="topbar-title"><?= esc($titulo ?? 'Administración') ?></div>
                <a class="logout" href="<?= base_url('auth/logout') ?>">Cerrar sesión</a>
            </header>

            <main class="admin-content">
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
        </div>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('menuToggle');
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');

            if (!toggle || !sidebar || !overlay) {
                return;
            }

            function cerrar() {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            }

            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('open');
            });

            overlay.addEventListener('click', cerrar);

            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    cerrar();
                }
            });
        })();
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
