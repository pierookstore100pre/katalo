<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php $errors = validation_errors(); ?>
<?php
$distritosPorCiudad = [];
foreach ($distritos as $distrito) {
    $distritosPorCiudad[$distrito['ciudad']][] = $distrito;
}
?>
<div class="card">
    <h1>Crea tu cuenta</h1>
    <p class="subtitle">Registra tu negocio y empieza a armar tu catálogo digital.</p>

    <form action="<?= base_url('auth/registro') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="nombres">Nombres</label>
                <input
                    type="text"
                    id="nombres"
                    name="nombres"
                    value="<?= set_value('nombres') ?>"
                    maxlength="100"
                    autocomplete="given-name"
                    required
                    class="<?= isset($errors['nombres']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['nombres'])): ?>
                    <p class="field-error"><?= esc($errors['nombres']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="apellidos">Apellidos</label>
                <input
                    type="text"
                    id="apellidos"
                    name="apellidos"
                    value="<?= set_value('apellidos') ?>"
                    maxlength="100"
                    autocomplete="family-name"
                    required
                    class="<?= isset($errors['apellidos']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['apellidos'])): ?>
                    <p class="field-error"><?= esc($errors['apellidos']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="telefono">Teléfono o WhatsApp</label>
            <input
                type="tel"
                id="telefono"
                name="telefono"
                value="<?= set_value('telefono') ?>"
                inputmode="numeric"
                pattern="[0-9]{7,15}"
                maxlength="15"
                autocomplete="tel"
                placeholder="Ej. 987654321"
                required
                class="<?= isset($errors['telefono']) ? 'input-error' : '' ?>"
            >
            <span class="hint">Solo números, entre 7 y 15 dígitos.</span>
            <?php if (isset($errors['telefono'])): ?>
                <p class="field-error"><?= esc($errors['telefono']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= set_value('email') ?>"
                maxlength="100"
                autocomplete="email"
                placeholder="tucorreo@ejemplo.com"
                required
                class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['email'])): ?>
                <p class="field-error"><?= esc($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres e incluye un número"
                required
                class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
            >
            <span class="hint">Mínimo 8 caracteres y al menos un número.</span>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= esc($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <div class="field">
            <label for="nombre_comercial">Nombre comercial del negocio</label>
            <input
                type="text"
                id="nombre_comercial"
                name="nombre_comercial"
                value="<?= set_value('nombre_comercial') ?>"
                maxlength="150"
                autocomplete="organization"
                placeholder="Ej. Café Tacna"
                required
                class="<?= isset($errors['nombre_comercial']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['nombre_comercial'])): ?>
                <p class="field-error"><?= esc($errors['nombre_comercial']) ?></p>
            <?php endif; ?>
        </div>

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
                                <?= set_select('distrito_id', (string) $distrito['id_distrito']) ?>
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
            <label for="direccion">Dirección del negocio</label>
            <input
                type="text"
                id="direccion"
                name="direccion"
                value="<?= set_value('direccion') ?>"
                maxlength="255"
                autocomplete="street-address"
                placeholder="Calle, número, referencia"
                required
                class="<?= isset($errors['direccion']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['direccion'])): ?>
                <p class="field-error"><?= esc($errors['direccion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Crear mi cuenta</button>
        </div>
    </form>

    <div class="auth-links">
        <span>¿Ya tienes una cuenta?</span>
        <a href="<?= base_url('auth/login') ?>">Inicia sesión</a>
    </div>
</div>
<?= $this->endSection() ?>
