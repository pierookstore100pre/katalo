<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php $errors = validation_errors(); ?>
<div class="card">
    <h1>Nueva contraseña</h1>

    <?php if (! $valido): ?>
        <div class="alert alert-error">
            El enlace es inválido, ya fue usado o ha vencido. Solicita uno nuevo.
        </div>
        <a class="btn btn-secondary" href="<?= base_url('auth/recuperar') ?>">Solicitar otro enlace</a>
    <?php else: ?>
        <p class="subtitle">Define una nueva contraseña para tu cuenta.</p>

        <form action="<?= base_url('auth/reset') ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">

            <div class="field">
                <label for="password">Nueva contraseña</label>
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

            <div class="field">
                <label for="password_confirm">Confirma la nueva contraseña</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    minlength="8"
                    autocomplete="new-password"
                    required
                    class="<?= isset($errors['password_confirm']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['password_confirm'])): ?>
                    <p class="field-error"><?= esc($errors['password_confirm']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Guardar contraseña</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="auth-links">
        <a href="<?= base_url('auth/login') ?>">Volver al inicio de sesión</a>
    </div>
</div>
<?= $this->endSection() ?>
