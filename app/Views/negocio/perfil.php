<?= $this->extend('layouts/panel') ?>

<?= $this->section('content') ?>
<?php
$errors = validation_errors();
$distritosPorCiudad = [];
foreach ($distritos as $distrito) {
    $distritosPorCiudad[$distrito['ciudad']][] = $distrito;
}
$distritoActual = (string) old('distrito_id', $negocio['distrito_id']);
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Perfil público</p>
            <h1>Mi perfil</h1>
            <p class="subtitle">Actualiza la información que verán tus clientes.</p>
        </div>
    </div>

    <form action="<?= base_url('negocio/perfil') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="nombre_comercial">Nombre comercial</label>
                <input
                    type="text"
                    id="nombre_comercial"
                    name="nombre_comercial"
                    value="<?= esc(old('nombre_comercial', $negocio['nombre_comercial'])) ?>"
                    maxlength="150"
                    required
                    class="<?= isset($errors['nombre_comercial']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['nombre_comercial'])): ?>
                    <p class="field-error"><?= esc($errors['nombre_comercial']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="slug">Slug</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="<?= esc(old('slug', $negocio['slug'])) ?>"
                    maxlength="120"
                    pattern="[a-z0-9]+(-[a-z0-9]+)*"
                    required
                    class="<?= isset($errors['slug']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Minúsculas, números y guiones. Debe ser único.</span>
                <?php
                $urlPublica = base_url(($ciudad_slug ?? '') . '/' . $negocio['slug']);
                ?>
                <span class="hint">
                    URL pública:
                    <a href="<?= esc($urlPublica, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($urlPublica) ?></a>
                </span>
                <?php if (isset($errors['slug'])): ?>
                    <p class="field-error"><?= esc($errors['slug']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea
                id="descripcion"
                name="descripcion"
                placeholder="Cuéntale a tus clientes qué ofrece tu negocio"
                class="<?= isset($errors['descripcion']) ? 'input-error' : '' ?>"
            ><?= esc(old('descripcion', $negocio['descripcion'] ?? '')) ?></textarea>
            <?php if (isset($errors['descripcion'])): ?>
                <p class="field-error"><?= esc($errors['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="direccion">Dirección</label>
            <input
                type="text"
                id="direccion"
                name="direccion"
                value="<?= esc(old('direccion', $negocio['direccion'])) ?>"
                maxlength="255"
                autocomplete="street-address"
                required
                class="<?= isset($errors['direccion']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['direccion'])): ?>
                <p class="field-error"><?= esc($errors['direccion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="distrito_id">Distrito</label>
                <select
                    id="distrito_id"
                    name="distrito_id"
                    required
                    class="<?= isset($errors['distrito_id']) ? 'input-error' : '' ?>"
                >
                    <option value="">Selecciona tu distrito</option>
                    <?php foreach ($distritosPorCiudad as $ciudad => $listaDistritos): ?>
                        <optgroup label="<?= esc($ciudad) ?>">
                            <?php foreach ($listaDistritos as $distrito): ?>
                                <option
                                    value="<?= esc($distrito['id_distrito'], 'attr') ?>"
                                    <?= set_select('distrito_id', (string) $distrito['id_distrito'], $distritoActual === (string) $distrito['id_distrito']) ?>
                                >
                                    <?= esc($distrito['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['distrito_id'])): ?>
                    <p class="field-error"><?= esc($errors['distrito_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="telefono_principal">Teléfono o WhatsApp</label>
                <input
                    type="tel"
                    id="telefono_principal"
                    name="telefono_principal"
                    value="<?= esc(old('telefono_principal', $negocio['telefono_principal'])) ?>"
                    inputmode="numeric"
                    pattern="[0-9]{7,15}"
                    maxlength="15"
                    autocomplete="tel"
                    required
                    class="<?= isset($errors['telefono_principal']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Solo números, entre 7 y 15 dígitos.</span>
                <?php if (isset($errors['telefono_principal'])): ?>
                    <p class="field-error"><?= esc($errors['telefono_principal']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="horario_texto">Horario de atención</label>
            <input
                type="text"
                id="horario_texto"
                name="horario_texto"
                value="<?= esc(old('horario_texto', $negocio['horario_texto'] ?? '')) ?>"
                maxlength="255"
                placeholder="Ej. Lunes a sábado, 9:00 a 19:00"
                class="<?= isset($errors['horario_texto']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['horario_texto'])): ?>
                <p class="field-error"><?= esc($errors['horario_texto']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Guardar cambios</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
