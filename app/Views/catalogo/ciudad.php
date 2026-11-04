<?= $this->extend('catalogo/layout') ?>

<?= $this->section('styles') ?>
<style>
    .city-head {
        margin: 6px 2px 20px;
    }

    .city-head h1 {
        margin: 0 0 4px;
        font-size: 1.5rem;
        letter-spacing: -0.02em;
    }

    .city-head p {
        margin: 0;
        color: var(--muted);
        font-size: 0.92rem;
    }

    .business-list {
        display: grid;
        gap: 12px;
    }

    .business-item {
        display: flex;
        gap: 13px;
        padding: 15px;
        border: 1px solid rgba(15, 118, 110, 0.12);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
        text-decoration: none;
        color: inherit;
    }

    .business-item:active { transform: scale(0.995); }

    .business-mark {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        border-radius: 15px;
        background: linear-gradient(145deg, var(--primary), #14b8a6);
        color: #fff;
        font-size: 1.25rem;
        font-weight: 800;
    }

    .business-info { min-width: 0; }

    .business-info h2 {
        margin: 0 0 3px;
        font-size: 1.02rem;
        overflow-wrap: anywhere;
    }

    .business-info p {
        margin: 0;
        color: var(--muted);
        font-size: 0.84rem;
        overflow-wrap: anywhere;
    }

    .business-info .business-address { margin-top: 5px; }

    .empty-state {
        padding: 34px 20px;
        border: 1px dashed #99f6e4;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.86);
        text-align: center;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="catalog-shell">
    <header class="city-head">
        <p class="business-eyebrow" style="margin: 0 0 4px; color: var(--primary); font-size: 0.72rem; font-weight: 800; letter-spacing: 0.09em; text-transform: uppercase;">Katalo.pe</p>
        <h1>Catálogos en <?= esc($ciudad_nombre) ?></h1>
        <p>Descubre los negocios locales y sus productos.</p>
    </header>

    <?php if ($negocios === []): ?>
        <section class="empty-state">
            <h2>Aún no hay catálogos publicados</h2>
            <p>Vuelve pronto para descubrir nuevos negocios en esta ciudad.</p>
        </section>
    <?php else: ?>
        <section class="business-list" aria-label="Negocios de la ciudad">
            <?php foreach ($negocios as $negocio): ?>
                <?php
                $inicial = mb_strtoupper(mb_substr((string) $negocio['nombre_comercial'], 0, 1));
                $url     = base_url($ciudad_slug . '/' . $negocio['slug']);
                $zona    = trim((string) ($negocio['distrito'] ?? '') . ', ' . (string) ($negocio['ciudad'] ?? ''), ', ');
                ?>
                <a class="business-item" href="<?= esc($url, 'attr') ?>">
                    <span class="business-mark" aria-hidden="true"><?= esc($inicial) ?></span>
                    <span class="business-info">
                        <h2><?= esc($negocio['nombre_comercial']) ?></h2>
                        <?php if (! empty($negocio['descripcion'])): ?>
                            <p><?= esc($negocio['descripcion']) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($negocio['direccion']) || $zona !== ''): ?>
                            <p class="business-address">
                                <?= esc((string) ($negocio['direccion'] ?? '')) ?>
                                <?php if ($zona !== ''): ?>
                                    <?= ! empty($negocio['direccion']) ? ' · ' : '' ?><?= esc($zona) ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?= $this->endSection() ?>
