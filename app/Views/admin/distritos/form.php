<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$esEdicion        = $distrito !== null;
$ciudadActual     = old('ciudad_id', $distrito['ciudad_id'] ?? '');
$nombreActual     = old('nombre', $distrito['nombre'] ?? '');
$descripcionActual = old('descripcion', $distrito['descripcion'] ?? '');
$activoActual     = old('activo', $distrito['activo'] ?? 1);
$activoMarcado    = (string) $activoActual === '1';
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Geografía</p>
            <h1><?= $esEdicion ? 'Editar distrito' : 'Nuevo distrito' ?></h1>
            <p class="subtitle">
                <?= $esEdicion
                    ? 'Actualiza los datos del distrito seleccionado.'
                    : 'Registra un nuevo distrito para una ciudad existente.' ?>
            </p>
        </div>
    </div>

    <form action="<?= esc($accion, 'attr') ?>" method="post">
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="ciudad_id">Ciudad <span aria-hidden="true">*</span></label>
                <select id="ciudad_id" name="ciudad_id" required>
                    <option value="">Selecciona una ciudad</option>
                    <?php foreach ($ciudades as $ciudad): ?>
                        <option
                            value="<?= (int) $ciudad['id_ciudad'] ?>"
                            <?= (string) $ciudadActual === (string) $ciudad['id_ciudad'] ? 'selected' : '' ?>
                        ><?= esc($ciudad['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="<?= esc((string) $nombreActual, 'attr') ?>"
                    required
                >
                <span class="hint">El nombre debe ser único dentro de la ciudad seleccionada.</span>
            </div>
        </div>

        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" maxlength="255"><?= esc((string) $descripcionActual) ?></textarea>
            <span class="hint">Opcional. Máximo 255 caracteres.</span>
        </div>

        <div class="checkbox-row">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" id="activo" name="activo" value="1" <?= $activoMarcado ? 'checked' : '' ?>>
            <label for="activo">Distrito activo</label>
        </div>
        <span class="hint">
            Si lo desactivas, no se podrá asignar a nuevos negocios, pero los negocios existentes
            seguirán visibles.
        </span>

        <div class="form-actions actions">
            <button type="submit" class="btn"><?= $esEdicion ? 'Guardar cambios' : 'Crear distrito' ?></button>
            <a class="btn btn-secondary" href="<?= base_url('admin/distritos') ?>">Cancelar</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
