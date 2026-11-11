<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .team-summary {
        margin-bottom: 18px;
        padding: 13px 14px;
        border: 1px solid #ccfbf1;
        border-radius: 13px;
        background: #f0fdfa;
        color: #115e59;
        font-size: 0.88rem;
    }

    .team-summary strong { color: #0f766e; }

    .team-list {
        display: grid;
        gap: 10px;
    }

    .team-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 13px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .team-main { min-width: 0; flex: 1; }

    .team-name {
        margin: 0;
        font-size: 1rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .team-meta {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 0.82rem;
        overflow-wrap: anywhere;
    }

    .team-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex: 0 0 auto;
    }

    .team-actions form { display: inline; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Equipo</p>
            <h1>Colaboradores</h1>
            <p class="subtitle">Gestiona quiénes pueden administrar tu catálogo y pedidos.</p>
        </div>
        <a class="btn" href="<?= base_url('negocio/colaboradores/crear') ?>">Nuevo colaborador</a>
    </div>

    <div class="team-summary">
        <strong><?= (int) $totalActivos ?></strong>
        <?= (int) $totalActivos === 1 ? 'usuario activo' : 'usuarios activos' ?>
        de <?= $maxUsuarios === null ? 'usuarios ilimitados' : (int) $maxUsuarios . ' permitidos' ?>
        en tu plan <?= esc($plan['nombre'] ?? '') ?>.
    </div>

    <?php if ($colaboradores === []): ?>
        <div class="empty-state">
            <h2>Aún no tienes colaboradores</h2>
            <p>Crea un colaborador para que te ayude a gestionar productos y pedidos.</p>
            <a class="btn" href="<?= base_url('negocio/colaboradores/crear') ?>">Crear colaborador</a>
        </div>
    <?php else: ?>
        <div class="team-list">
            <?php foreach ($colaboradores as $colaborador): ?>
                <?php $activo = (int) $colaborador['activo'] === 1; ?>
                <article class="team-row">
                    <div class="team-main">
                        <h2 class="team-name">
                            <?= esc(trim($colaborador['nombres'] . ' ' . $colaborador['apellidos'])) ?>
                        </h2>
                        <p class="team-meta"><?= esc($colaborador['email']) ?></p>
                        <p class="team-meta"><?= esc($colaborador['telefono']) ?></p>
                    </div>
                    <div class="team-actions">
                        <span class="badge <?= $activo ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $activo ? 'Activo' : 'Inactivo' ?>
                        </span>

                        <?php if ($activo): ?>
                            <form
                                action="<?= base_url('negocio/colaboradores/' . (int) $colaborador['id_negocio_usuario'] . '/desactivar') ?>"
                                method="post"
                                onsubmit="return confirm('¿Desactivar a este colaborador? Ya no podrá ingresar al panel.');"
                            >
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Desactivar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
