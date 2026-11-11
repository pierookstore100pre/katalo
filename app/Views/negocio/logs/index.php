<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .log-filters {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 16px;
        padding: 14px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .log-filters label {
        display: block;
        margin-bottom: 4px;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .log-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .log-table { width: 100%; border-collapse: collapse; }

    .log-table thead { display: none; }
    .log-table tbody { display: block; }

    .log-table tr {
        display: block;
        margin-bottom: 10px;
        padding: 12px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .log-table td {
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

    .log-table td::before {
        content: attr(data-label);
        flex: 0 0 auto;
        color: var(--muted);
        font-weight: 650;
        text-align: left;
    }

    .log-accion {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.74rem;
        font-weight: 750;
    }

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
        color: var(--primary-dark);
        text-align: center;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 650;
    }

    .pagination li.active a,
    .pagination li.active span {
        border-color: var(--primary);
        background: var(--primary);
        color: #fff;
    }

    .pagination li.disabled span { color: #cbd5e1; }

    @media (min-width: 620px) {
        .log-filters { grid-template-columns: repeat(3, 1fr); }
        .log-filter-actions { grid-column: 1 / -1; }
    }

    @media (min-width: 760px) {
        .log-table thead { display: table-header-group; }
        .log-table tbody { display: table-row-group; }

        .log-table th {
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.74rem;
            font-weight: 750;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .log-table tr {
            display: table-row;
            margin: 0;
            padding: 0;
            border: 0;
            border-bottom: 1px solid #eef4f2;
            border-radius: 0;
            background: transparent;
        }

        .log-table td {
            display: table-cell;
            padding: 11px 8px;
            border-bottom: 1px solid #eef4f2;
            text-align: left;
            vertical-align: middle;
        }

        .log-table td::before { display: none; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Auditoría</p>
            <h1>Registro de actividad</h1>
            <p class="subtitle">Consulta las acciones realizadas por tu negocio y sus usuarios.</p>
        </div>
    </div>

    <form class="log-filters" action="<?= base_url('negocio/logs') ?>" method="get">
        <div>
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="<?= esc((string) ($filtros['desde'] ?? ''), 'attr') ?>">
        </div>

        <div>
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="<?= esc((string) ($filtros['hasta'] ?? ''), 'attr') ?>">
        </div>

        <div>
            <label for="accion">Acción</label>
            <select id="accion" name="accion">
                <option value="">Todas</option>
                <?php foreach ($acciones as $accion): ?>
                    <option value="<?= esc($accion, 'attr') ?>" <?= ($filtros['accion'] ?? '') === $accion ? 'selected' : '' ?>>
                        <?= esc($accion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="log-filter-actions">
            <button type="submit" class="btn btn-small">Filtrar</button>
            <a class="btn btn-secondary btn-small" href="<?= base_url('negocio/logs') ?>">Limpiar</a>
        </div>
    </form>

    <?php if ($logs === []): ?>
        <div class="empty-state">
            <h2>No hay registros para este filtro</h2>
            <p>Prueba con otro rango de fechas o acción.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $fecha = $log['created_at'] !== null
                            ? date('d/m/Y H:i:s', strtotime((string) $log['created_at']))
                            : '—';
                        $tabla  = trim((string) ($log['tabla_afectada'] ?? ''));
                        $modulo = $tabla !== '' ? ucfirst(str_replace('_', ' ', $tabla)) : '—';
                        ?>
                        <tr>
                            <td data-label="Fecha"><?= esc($fecha) ?></td>
                            <td data-label="Usuario"><?= esc($log['usuario_email'] ?? 'sistema') ?></td>
                            <td data-label="Acción"><span class="log-accion"><?= esc($log['accion']) ?></span></td>
                            <td data-label="Módulo"><?= esc($modulo) ?></td>
                            <td data-label="Descripción"><?= esc($log['descripcion'] ?? '—') ?></td>
                            <td data-label="IP"><?= esc($log['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= $pager !== null ? $pager->links('logs_negocio') : '' ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
