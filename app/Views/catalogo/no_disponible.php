<?= $this->extend('catalogo/layout') ?>

<?= $this->section('styles') ?>
<style>
    .state-shell {
        display: grid;
        place-items: center;
        min-height: calc(100vh - 120px);
        padding: 28px 16px;
    }

    .state-card {
        width: 100%;
        max-width: 460px;
        padding: 32px 24px;
        border: 1px solid rgba(15, 118, 110, 0.12);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        text-align: center;
    }

    .state-mark {
        display: grid;
        place-items: center;
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        border-radius: 20px;
        background: linear-gradient(145deg, var(--primary), #14b8a6);
        color: #fff;
        font-size: 1.7rem;
        font-weight: 800;
        box-shadow: 0 12px 28px rgba(15, 118, 110, 0.24);
    }

    .state-card h1 {
        margin: 0 0 8px;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
    }

    .state-card p {
        margin: 0 auto 20px;
        max-width: 360px;
        color: var(--muted);
        font-size: 0.94rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="state-shell">
    <section class="state-card">
        <div class="state-mark" aria-hidden="true">K</div>
        <h1>Catálogo no disponible</h1>
        <p>Este catálogo no está disponible o el negocio ya no opera en Katalo.pe</p>
        <a class="btn" href="<?= esc(base_url('/'), 'attr') ?>">Ir a Katalo.pe</a>
    </section>
</main>
<?= $this->endSection() ?>
