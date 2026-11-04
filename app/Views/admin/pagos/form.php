<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Pago manual</p>
            <h1>Registrar pago</h1>
            <p class="subtitle">Registra el pago de una solicitud de upgrade pendiente.</p>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('admin/pagos') ?>">Volver</a>
    </div>

    <div class="plan-meter" style="margin:0 0 18px;padding:12px 14px;border:1px solid #ccfbf1;border-radius:13px;background:#f0fdfa;color:#115e59;font-size:.88rem">
        Negocio: <strong><?= esc($negocio['nombre_comercial'] ?? 'Negocio') ?></strong>
        · Plan solicitado: <strong><?= esc($plan['nombre'] ?? 'Plan') ?></strong>
        · Precio pactado: <strong>S/ <?= number_format((float) $suscripcion['precio_pactado'], 2, '.', ',') ?></strong>
        · Suscripción #<?= (int) $suscripcion['id_suscripcion'] ?>
    </div>

    <form action="<?= base_url('admin/pagos/registrar/' . (int) $suscripcion['id_suscripcion']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="monto">Monto</label>
                <input
                    type="number"
                    id="monto"
                    name="monto"
                    step="0.01"
                    min="0.01"
                    max="99999999.99"
                    value="<?= esc(old('monto', $montoDefault), 'attr') ?>"
                    required
                >
                <span class="hint">Monto mayor a 0.</span>
            </div>

            <div class="field">
                <label for="metodo">Método de pago</label>
                <?php $metodoActual = old('metodo', 'transferencia'); ?>
                <select id="metodo" name="metodo" required>
                    <option value="transferencia" <?= $metodoActual === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    <option value="yape" <?= $metodoActual === 'yape' ? 'selected' : '' ?>>Yape</option>
                    <option value="plin" <?= $metodoActual === 'plin' ? 'selected' : '' ?>>Plin</option>
                    <option value="efectivo" <?= $metodoActual === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                    <option value="otro" <?= $metodoActual === 'otro' ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="periodo_desde">Periodo desde</label>
                <input
                    type="date"
                    id="periodo_desde"
                    name="periodo_desde"
                    value="<?= esc(old('periodo_desde', $desdeDefault), 'attr') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="periodo_hasta">Periodo hasta</label>
                <input
                    type="date"
                    id="periodo_hasta"
                    name="periodo_hasta"
                    value="<?= esc(old('periodo_hasta', $hastaDefault), 'attr') ?>"
                    required
                >
                <span class="hint">Debe ser igual o posterior al inicio.</span>
            </div>
        </div>

        <div class="field">
            <label for="fecha_pago">Fecha del pago</label>
            <input
                type="date"
                id="fecha_pago"
                name="fecha_pago"
                value="<?= esc(old('fecha_pago', $pagoDefault), 'attr') ?>"
                required
            >
        </div>

        <div class="field">
            <label for="referencia">Referencia</label>
            <input
                type="text"
                id="referencia"
                name="referencia"
                maxlength="100"
                value="<?= esc(old('referencia'), 'attr') ?>"
                placeholder="Número de operación, código o nota"
            >
        </div>

        <div class="field">
            <label for="comprobante_ruta">Ruta o referencia del comprobante</label>
            <input
                type="text"
                id="comprobante_ruta"
                name="comprobante_ruta"
                maxlength="255"
                value="<?= esc(old('comprobante_ruta'), 'attr') ?>"
                placeholder="Opcional"
            >
            <span class="hint">Por ahora se guarda solo texto; la carga de archivos se implementará después.</span>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Registrar pago</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
