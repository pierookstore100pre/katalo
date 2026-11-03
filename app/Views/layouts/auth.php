<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = validation_errors();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>Katalo.pe</title>
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
            --info-bg: #eff6ff;
            --info: #1d4ed8;
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
        a:hover { color: var(--primary); }

        .auth-shell {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px 96px;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 8px 0 22px;
            text-decoration: none;
            color: var(--primary-dark);
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--primary), #14b8a6);
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 8px 20px rgba(15, 118, 110, 0.25);
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(15, 118, 110, 0.10);
            border-radius: var(--radius);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            padding: 24px 20px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 0.94rem;
        }

        .field {
            margin-bottom: 15px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
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

        input,
        select {
            width: 100%;
            min-height: 48px;
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus,
        select:focus {
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

        .alert-info {
            color: var(--info);
            background: var(--info-bg);
            border: 1px solid #bfdbfe;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 50px;
            padding: 12px 18px;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            font: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(15, 118, 110, 0.22);
        }

        .btn:hover { filter: brightness(1.04); }

        .btn-secondary {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--primary-dark);
            box-shadow: none;
        }

        .form-actions {
            margin-top: 22px;
        }

        .auth-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 16px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .auth-links a { text-decoration: none; }

        .divider {
            height: 1px;
            margin: 20px 0;
            background: var(--line);
        }

        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .dashboard-header .brand { margin: 0; justify-content: flex-start; font-size: 1.25rem; }

        .logout-link {
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            text-decoration: none;
            font-size: 0.86rem;
            white-space: nowrap;
        }

        .business-name {
            margin: 0 0 4px;
            font-size: 1.2rem;
        }

        .meta-list {
            display: grid;
            gap: 10px;
            margin: 18px 0 0;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 11px 0;
            border-bottom: 1px solid #eef4f2;
            font-size: 0.92rem;
        }

        .meta-item:last-child { border-bottom: 0; }
        .meta-item span:first-child { color: var(--muted); }
        .meta-item span:last-child { text-align: right; font-weight: 650; }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 700;
        }

        @media (min-width: 420px) {
            .grid-2 {
                grid-template-columns: 1fr 1fr;
                gap: 0 12px;
            }
        }

        @media (max-width: 640px) {
            .auth-shell { padding: 16px 12px 118px; }
            .form-actions {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 20;
                margin: 0;
                padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
                background: rgba(244, 251, 249, 0.94);
                border-top: 1px solid var(--line);
                backdrop-filter: blur(8px);
            }
            .form-actions .btn { max-width: 480px; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <a class="brand" href="<?= base_url() ?>">
            <span class="brand-mark">K</span>
            <span>Katalo.pe</span>
        </a>

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

        <p class="auth-links" style="justify-content:center; margin-top:22px; color:#94a3b8; font-size:.78rem;">
            Catálogos digitales para tu negocio
        </p>
    </main>
</body>
</html>
