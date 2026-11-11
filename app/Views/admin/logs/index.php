<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Auditoría</p>
            <h1>Registro de actividad</h1>
            <p class="subtitle">Consulta las acciones críticas realizadas en la plataforma.</p>
        </div>
    </div>

    <form class="filter-form" action="<?= base_url('admin/logs') ?>" method="get">
        <div class="filter-field">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="<?= esc((string) ($filtros['desde'] ?? ''), 'attr') ?>">
        </div>

        <div class="filter-field">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="<?= esc((string) ($filtros['hasta'] ?? ''), 'attr') ?>">
        </div>

        <div class="filter-field">
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

        <div class="filter-field">
            <label for="negocio_id">Negocio</label>
            <select id="negocio_id" name="negocio_id">
                <option value="">Todos</option>
                <?php foreach ($negocios as $negocio): ?>
                    <option
                        value="<?= (int) $negocio['id_negocio'] ?>"
                        <?= (int) ($filtros['negocio_id'] ?? 0) === (int) $negocio['id_negocio'] ? 'selected' : '' ?>
                    ><?= esc($negocio['nombre_comercial']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="usuario_id">Usuario</label>
            <select id="usuario_id" name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option
                        value="<?= (int) $usuario['id_usuario'] ?>"
                        <?= (int) ($filtros['usuario_id'] ?? 0) === (int) $usuario['id_usuario'] ? 'selected' : '' ?>
                    ><?= esc($usuario['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-small">Filtrar</button>
            <a class="btn btn-secondary btn-small" href="<?= base_url('admin/logs') ?>">Limpiar</a>
        </div>
    </form>

    <?php if ($logs === []): ?>
        <div class="empty-state">
            <h2>No hay registros para este filtro</h2>
            <p>Prueba con otro rango de fechas, acción o negocio.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Negocio</th>
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
                            <td data-label="Negocio"><?= esc($log['negocio_nombre'] ?? '—') ?></td>
                            <td data-label="Acción"><span class="badge badge-info"><?= esc($log['accion']) ?></span></td>
                            <td data-label="Módulo"><?= esc($modulo) ?></td>
                            <td data-label="Descripción"><?= esc($log['descripcion'] ?? '—') ?></td>
                            <td data-label="IP"><?= esc($log['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= $pager !== null ? $pager->links('logs_admin') : '' ?>
    <?php endif; ?>

    <div class="notice">
        El registro de actividad es de solo lectura: no se puede editar ni eliminar desde el panel.
    </div>
</section>
<?= $this->endSection() ?>
