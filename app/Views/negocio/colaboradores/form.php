<?= $this->extend('layouts/panel') ?>

<?= $this->section('content') ?>
<?php $errors = validation_errors(); ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Equipo</p>
            <h1>Nuevo colaborador</h1>
            <p class="subtitle">Crea una cuenta temporal para que tu colaborador acceda al panel.</p>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('negocio/colaboradores') ?>">Volver</a>
    </div>

    <form action="<?= esc($accion, 'attr') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="nombres">Nombres</label>
                <input
                    type="text"
                    id="nombres"
                    name="nombres"
                    value="<?= esc(old('nombres')) ?>"
                    maxlength="100"
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
                    value="<?= esc(old('apellidos')) ?>"
                    maxlength="100"
                    required
                    class="<?= isset($errors['apellidos']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['apellidos'])): ?>
                    <p class="field-error"><?= esc($errors['apellidos']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= esc(old('email')) ?>"
                    maxlength="100"
                    autocomplete="email"
                    required
                    class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Con este correo iniciará sesión.</span>
                <?php if (isset($errors['email'])): ?>
                    <p class="field-error"><?= esc($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="telefono">Teléfono</label>
                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    value="<?= esc(old('telefono')) ?>"
                    inputmode="numeric"
                    pattern="[0-9]{7,15}"
                    maxlength="15"
                    required
                    class="<?= isset($errors['telefono']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Solo números, entre 7 y 15 dígitos.</span>
                <?php if (isset($errors['telefono'])): ?>
                    <p class="field-error"><?= esc($errors['telefono']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="password">Contraseña temporal</label>
            <input
                type="text"
                id="password"
                name="password"
                value=""
                minlength="8"
                maxlength="100"
                autocomplete="new-password"
                required
                class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
            >
            <span class="hint">Mínimo 8 caracteres e incluir al menos un número. Compártela con tu colaborador.</span>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= esc($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Crear colaborador</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
