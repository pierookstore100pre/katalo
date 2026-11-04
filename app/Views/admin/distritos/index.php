<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$agrupados = [];
foreach ($distritos as $distrito) {
    $agrupados[$distrito['ciudad_nombre']][] = $distrito;
}
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Geografía</p>
            <h1>Distritos</h1>
            <p class="subtitle">Gestiona los distritos disponibles por ciudad.</p>
        </div>
        <a class="btn" href="<?= base_url('admin/distritos/crear') ?>">Nuevo distrito</a>
    </div>

    <form class="filter-form" action="<?= base_url('admin/distritos') ?>" method="get">
        <div class="filter-field">
            <label for="ciudad">Ciudad</label>
            <select id="ciudad" name="ciudad">
                <option value="">Todas las ciudades</option>
                <?php foreach ($ciudades as $ciudad): ?>
                    <option
                        value="<?= (int) $ciudad['id_ciudad'] ?>"
                        <?= (int) $filtroCiudad === (int) $ciudad['id_ciudad'] ? 'selected' : '' ?>
                    ><?= esc($ciudad['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="todos" <?= $filtroEstado === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activos" <?= $filtroEstado === 'activos' ? 'selected' : '' ?>>Activos</option>
                <option value="inactivos" <?= $filtroEstado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-small">Filtrar</button>
            <a class="btn btn-secondary btn-small" href="<?= base_url('admin/distritos') ?>">Limpiar</a>
        </div>
    </form>

    <div class="notice">
        Desactivar un distrito no oculta los negocios que ya existen en él, pero impide nuevas
        asignaciones en el registro y en el perfil del negocio. No es posible eliminar distritos.
    </div>

    <?php if ($distritos === []): ?>
        <div class="empty-state">
            <h2>No hay distritos para este filtro</h2>
            <p>Crea un distrito o cambia los filtros de búsqueda.</p>
        </div>
    <?php else: ?>
        <?php foreach ($agrupados as $ciudadNombre => $lista): ?>
            <div class="section-title">
                <h2><?= esc($ciudadNombre) ?></h2>
                <span class="badge badge-info"><?= count($lista) ?> distrito(s)</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Distrito</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Negocios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista as $distrito): ?>
                            <?php $activo = (int) $distrito['activo'] === 1; ?>
                            <tr>
                                <td data-label="Distrito"><strong><?= esc($distrito['nombre']) ?></strong></td>
                                <td data-label="Descripción"><?= esc($distrito['descripcion'] ?? '—') ?></td>
                                <td data-label="Estado">
                                    <span class="badge <?= $activo ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td data-label="Negocios"><?= (int) $distrito['negocios_count'] ?></td>
                                <td data-label="Acciones">
                                    <div class="actions">
                                        <a
                                            class="btn btn-secondary btn-small"
                                            href="<?= base_url('admin/distritos/editar/' . (int) $distrito['id_distrito']) ?>"
                                        >Editar</a>

                                        <?php if ($activo): ?>
                                            <form action="<?= base_url('admin/distritos/' . (int) $distrito['id_distrito'] . '/desactivar') ?>" method="post">
                                                <?= csrf_field() ?>
                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-small"
                                                    onclick="return confirm('¿Desactivar este distrito?');"
                                                >Desactivar</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= base_url('admin/distritos/' . (int) $distrito['id_distrito'] . '/activar') ?>" method="post">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-success btn-small">Activar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
