<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <meta name="description" content="Catálogo digital en Katalo.pe">
    <title><?= esc($titulo ?? 'Catálogo') ?> · Katalo.pe</title>
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

        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 0%, #ccfbf1 0, transparent 34%),
                radial-gradient(circle at 92% 4%, #d1fae5 0, transparent 28%),
                var(--bg);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.5;
        }

        img { max-width: 100%; display: block; }

        a { color: var(--primary-dark); font-weight: 600; }

        .catalog-shell {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
            padding: 16px 14px 34px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 16px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(15, 118, 110, 0.20);
        }

        .btn:hover { filter: brightness(1.04); }

        .site-footer {
            padding: 22px 14px calc(26px + env(safe-area-inset-bottom));
            text-align: center;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .site-footer a {
            color: var(--primary-dark);
            text-decoration: none;
        }

        @media (min-width: 640px) {
            .catalog-shell { padding: 24px 20px 44px; }
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <?= $this->renderSection('content') ?>

    <footer class="site-footer">
        <a href="<?= esc(base_url('/'), 'attr') ?>">Creado con Katalo.pe</a>
    </footer>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
