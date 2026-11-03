<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php
$errors    = validation_errors();
$resetLink = session()->getFlashdata('reset_link');
?>
<div class="card">
    <h1>Recupera tu contraseña</h1>
    <p class="subtitle">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>

    <?php if ($resetLink !== null): ?>
        <div class="alert alert-info">
            <strong>Modo desarrollo:</strong> como no hay SMTP configurado, usa este enlace para continuar.
            <br>
            <a href="<?= esc($resetLink, 'attr') ?>"><?= esc($resetLink) ?></a>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('auth/recuperar') ?>" method="post" novalidate>
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

        <div class="form-actions">
            <button type="submit" class="btn">Enviar enlace</button>
        </div>
    </form>

    <div class="auth-links">
        <a href="<?= base_url('auth/login') ?>">Volver al inicio de sesión</a>
    </div>
</div>
<?= $this->endSection() ?>
