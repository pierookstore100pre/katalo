<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php $errors = validation_errors(); ?>
<div class="card">
    <h1>Inicia sesión</h1>
    <p class="subtitle">Accede al panel de tu negocio.</p>

    <form action="<?= base_url('auth/login') ?>" method="post" novalidate>
        <?= csrf_field() ?>

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
                autocomplete="current-password"
                required
                class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= esc($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Ingresar</button>
        </div>
    </form>

    <div class="auth-links">
        <a href="<?= base_url('auth/recuperar') ?>">Olvidé mi contraseña</a>
        <span>·</span>
        <a href="<?= base_url('auth/registro') ?>">Crear cuenta</a>
    </div>
</div>
<?= $this->endSection() ?>
