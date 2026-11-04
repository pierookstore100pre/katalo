<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Moderación</p>
            <h1>Negocios</h1>
            <p class="subtitle">Revisa, suspende o reactiva los negocios registrados en la plataforma.</p>
        </div>
    </div>

    <form class="filter-form" action="<?= base_url('admin/negocios') ?>" method="get">
        <div class="filter-field">
            <label for="ciudad">Ciudad</label>
            <select id="ciudad" name="ciudad">
                <option value="">Todas las ciudades</option>
                <?php foreach ($ciudades as $ciudad): ?>
                    <option
                        value="<?= (int) $ciudad['id_ciudad'] ?>"
                        <?= (int) ($filtros['ciudad'] ?? 0) === (int) $ciudad['id_ciudad'] ? 'selected' : '' ?>
                    ><?= esc($ciudad['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="todos" <?= ($filtros['estado'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="activo" <?= ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="suspendido" <?= ($filtros['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendidos</option>
            </select>
        </div>

        <div class="filter-field">
            <label for="q">Buscar</label>
            <input
                type="search"
                id="q"
                name="q"
                placeholder="Nombre o slug"
                value="<?= esc((string) ($filtros['q'] ?? ''), 'attr') ?>"
            >
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-small">Filtrar</button>
            <a class="btn btn-secondary btn-small" href="<?= base_url('admin/negocios') ?>">Limpiar</a>
        </div>
    </form>

    <?php if ($negocios === []): ?>
        <div class="empty-state">
            <h2>No hay negocios para este filtro</h2>
            <p>Prueba con otra ciudad, estado o término de búsqueda.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Negocio</th>
                        <th>Ciudad</th>
                        <th>Distrito</th>
                        <th>Propietario</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th>Productos</th>
                        <th>Pedidos</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($negocios as $negocio): ?>
                        <?php
                        $activo = (int) $negocio['activo'] === 1;
                        $propietario = trim(
                            (string) ($negocio['propietario_nombres'] ?? '')
                            . ' ' . (string) ($negocio['propietario_apellidos'] ?? '')
                        );
                        $fecha = $negocio['created_at'] !== null
                            ? date('d/m/Y', strtotime((string) $negocio['created_at']))
                            : '—';
                        ?>
                        <tr>
                            <td data-label="ID">#<?= (int) $negocio['id_negocio'] ?></td>
                            <td data-label="Negocio">
                                <strong><?= esc($negocio['nombre_comercial']) ?></strong><br>
                                <span style="color:#64748b;font-size:0.8rem"><?= esc($negocio['slug']) ?></span>
                            </td>
                            <td data-label="Ciudad"><?= esc($negocio['ciudad_nombre']) ?></td>
                            <td data-label="Distrito"><?= esc($negocio['distrito_nombre']) ?></td>
                            <td data-label="Propietario">
                                <?php if ($propietario !== ''): ?>
                                    <?= esc($propietario) ?><br>
                                    <span style="color:#64748b;font-size:0.8rem"><?= esc($negocio['propietario_email'] ?? '') ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td data-label="Plan"><?= esc($negocio['plan_nombre'] ?? 'Sin plan') ?></td>
                            <td data-label="Estado">
                                <span class="badge <?= $activo ? 'badge-active' : 'badge-suspended' ?>">
                                    <?= $activo ? 'Activo' : 'Suspendido' ?>
                                </span>
                            </td>
                            <td data-label="Productos"><?= (int) $negocio['productos_count'] ?></td>
                            <td data-label="Pedidos"><?= (int) $negocio['pedidos_count'] ?></td>
                            <td data-label="Registro"><?= esc($fecha) ?></td>
                            <td data-label="Acciones">
                                <div class="actions">
                                    <a
                                        class="btn btn-secondary btn-small"
                                        href="<?= base_url('admin/negocios/' . (int) $negocio['id_negocio']) ?>"
                                    >Ver detalle</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= $pager !== null ? $pager->links('negocios_admin') : '' ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
