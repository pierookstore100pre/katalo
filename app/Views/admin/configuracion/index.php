<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Plataforma</p>
            <h1>Configuración</h1>
            <p class="subtitle">Ajusta los límites globales de la plataforma sin recompilar ni desplegar.</p>
        </div>
    </div>

    <div class="notice">
        Un valor de <strong>0</strong> en «Pedidos por IP por hora» significa <strong>sin límite</strong>.
    </div>

    <form action="<?= base_url('admin/configuracion') ?>" method="post">
        <?= csrf_field() ?>

        <div class="grid-2">
            <?php foreach ($campos as $campo): ?>
                <div class="field">
                    <label for="<?= esc($campo['clave'], 'attr') ?>"><?= esc($campo['label']) ?></label>
                    <input
                        type="number"
                        id="<?= esc($campo['clave'], 'attr') ?>"
                        name="<?= esc($campo['clave'], 'attr') ?>"
                        value="<?= esc($campo['valor'], 'attr') ?>"
                        min="<?= (int) $campo['min'] ?>"
                        max="<?= (int) $campo['max'] ?>"
                        step="1"
                        required
                    >
                    <span class="hint">
                        <?= esc($campo['descripcion']) ?>
                        (<?= (int) $campo['min'] ?>–<?= (int) $campo['max'] ?>)
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Guardar configuración</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
