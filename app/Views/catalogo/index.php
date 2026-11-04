<?= $this->extend('catalogo/layout') ?>

<?= $this->section('styles') ?>
<style>
    .business-card {
        overflow: hidden;
        border: 1px solid rgba(15, 118, 110, 0.12);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
    }

    .business-cover {
        height: 84px;
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #5eead4 100%);
    }

    .business-body {
        display: flex;
        gap: 14px;
        padding: 0 16px 18px;
        margin-top: -32px;
    }

    .business-avatar {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 66px;
        height: 66px;
        border: 4px solid #fff;
        border-radius: 20px;
        background: linear-gradient(145deg, var(--primary), #14b8a6);
        color: #fff;
        font-size: 1.6rem;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(15, 118, 110, 0.24);
    }

    .business-main { min-width: 0; padding-top: 34px; }

    .business-eyebrow {
        margin: 0 0 2px;
        color: var(--primary);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .business-name {
        margin: 0;
        font-size: 1.4rem;
        line-height: 1.2;
        letter-spacing: -0.02em;
        overflow-wrap: anywhere;
    }

    .business-desc {
        margin: 8px 0 0;
        color: #475569;
        font-size: 0.9rem;
        overflow-wrap: anywhere;
    }

    .business-facts {
        display: grid;
        gap: 5px;
        margin: 11px 0 0;
        padding: 0;
        list-style: none;
        color: var(--muted);
        font-size: 0.84rem;
    }

    .business-facts li {
        display: flex;
        gap: 7px;
        overflow-wrap: anywhere;
    }

    .business-facts .fact-icon { flex: 0 0 auto; }

    .business-actions { margin-top: 14px; }

    .business-actions .btn { width: 100%; }

    .catalog-tools {
        margin-top: 18px;
        padding: 14px;
        border: 1px solid rgba(15, 118, 110, 0.12);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }

    .search-form {
        display: flex;
        gap: 8px;
    }

    .search-input {
        flex: 1;
        min-width: 0;
        min-height: 46px;
        padding: 10px 14px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        color: var(--ink);
        font: inherit;
        font-size: 1rem;
        outline: none;
    }

    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.13);
    }

    .search-submit {
        flex: 0 0 auto;
        min-height: 46px;
        padding: 10px 16px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(145deg, var(--primary), var(--primary-dark));
        color: #fff;
        font: inherit;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
    }

    .chips {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        margin: 14px 0 0;
        padding: 0 0 4px;
        scrollbar-width: none;
    }

    .chips::-webkit-scrollbar { display: none; }

    .chip {
        flex: 0 0 auto;
        padding: 8px 14px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: #fff;
        color: var(--muted);
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: 650;
        white-space: nowrap;
    }

    .chip.active {
        border-color: var(--primary);
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .products-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        margin: 22px 2px 10px;
    }

    .products-title {
        margin: 0;
        font-size: 1.08rem;
        letter-spacing: -0.01em;
    }

    .products-count {
        color: var(--muted);
        font-size: 0.8rem;
        white-space: nowrap;
    }

    .product-grid {
        display: grid;
        gap: 12px;
    }

    .product-card {
        overflow: hidden;
        border: 1px solid #e6efec;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .product-media {
        display: grid;
        place-items: center;
        aspect-ratio: 16 / 10;
        background: #f1f5f9;
        color: #94a3b8;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .product-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-no-image { letter-spacing: 0.02em; }

    .product-info { padding: 12px 13px 13px; }

    .product-category {
        display: inline-block;
        margin-bottom: 5px;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.68rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .product-title {
        margin: 0;
        font-size: 1rem;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .product-desc {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 0.83rem;
        overflow-wrap: anywhere;
    }

    .product-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 11px;
    }

    .product-price {
        color: var(--primary-dark);
        font-size: 1.02rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .product-add {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 11px;
        background: linear-gradient(145deg, var(--primary), var(--primary-dark));
        color: #fff;
        font: inherit;
        font-size: 1.25rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(15, 118, 110, 0.22);
        transition: transform 0.12s ease, background 0.12s ease;
    }

    .product-add:active { transform: scale(0.94); }

    .product-add.added { background: #16a34a; }

    body { padding-bottom: 96px; }

    .cart-float {
        position: fixed;
        left: 50%;
        bottom: calc(14px + env(safe-area-inset-bottom));
        z-index: 40;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: calc(100% - 28px);
        max-width: 520px;
        min-height: 52px;
        padding: 12px 18px;
        border: 0;
        border-radius: 16px;
        transform: translateX(-50%);
        background: linear-gradient(145deg, #0f766e, #115e59);
        color: #fff;
        font: inherit;
        font-size: 0.95rem;
        font-weight: 800;
        box-shadow: 0 16px 36px rgba(15, 118, 110, 0.28);
        cursor: pointer;
    }

    .cart-float:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        box-shadow: none;
    }

    .cart-float-count {
        padding: 2px 9px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        font-size: 0.8rem;
    }

    .cart-overlay {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        background: rgba(15, 23, 42, 0.55);
    }

    .cart-overlay[hidden] { display: none; }

    .cart-sheet {
        width: 100%;
        max-width: 560px;
        max-height: 92vh;
        overflow-y: auto;
        padding: 16px 16px calc(18px + env(safe-area-inset-bottom));
        border-radius: 22px 22px 0 0;
        background: #fff;
        box-shadow: 0 -18px 50px rgba(15, 23, 42, 0.18);
        animation: cart-up 0.2s ease-out;
    }

    @keyframes cart-up {
        from { opacity: 0.6; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .cart-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .cart-head h2 {
        margin: 0;
        font-size: 1.15rem;
    }

    .cart-close {
        width: 38px;
        height: 38px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #f8fafc;
        color: var(--muted);
        font: inherit;
        font-size: 1.2rem;
        cursor: pointer;
    }

    .cart-items {
        display: grid;
        gap: 9px;
        margin: 8px 0 14px;
    }

    .cart-empty {
        padding: 24px 10px;
        border: 1px dashed var(--line);
        border-radius: 12px;
        color: var(--muted);
        font-size: 0.9rem;
        text-align: center;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 6px 10px;
        padding: 10px;
        border: 1px solid #e6efec;
        border-radius: 12px;
        background: #fff;
    }

    .cart-item-title {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .cart-item-unit {
        color: var(--muted);
        font-size: 0.76rem;
    }

    .cart-item-subtotal {
        color: var(--primary-dark);
        font-size: 0.92rem;
        font-weight: 800;
        text-align: right;
    }

    .cart-qty {
        display: inline-flex;
        grid-column: 1 / -1;
        align-items: center;
        gap: 4px;
    }

    .cart-qty button {
        width: 34px;
        height: 34px;
        border: 1px solid var(--line);
        border-radius: 9px;
        background: #f8fafc;
        color: var(--ink);
        font: inherit;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
    }

    .cart-qty input {
        width: 52px;
        height: 34px;
        padding: 2px 4px;
        border: 1px solid var(--line);
        border-radius: 9px;
        font: inherit;
        font-size: 0.95rem;
        text-align: center;
    }

    .cart-remove {
        grid-column: 2;
        grid-row: 2;
        justify-self: end;
        padding: 4px 0;
        border: 0;
        background: transparent;
        color: #b91c1c;
        font: inherit;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
    }

    .cart-total-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        padding: 12px 2px;
        border-top: 1px solid var(--line);
        font-size: 1rem;
    }

    .cart-total-row strong {
        color: var(--primary-dark);
        font-size: 1.25rem;
    }

    .cart-form {
        display: grid;
        gap: 11px;
        margin-top: 8px;
    }

    .cart-field label {
        display: block;
        margin-bottom: 5px;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .cart-field input,
    .cart-field textarea {
        width: 100%;
        min-height: 48px;
        padding: 11px 13px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        color: var(--ink);
        font: inherit;
        font-size: 1rem;
        outline: none;
    }

    .cart-field textarea {
        min-height: 78px;
        resize: vertical;
    }

    .cart-field input:focus,
    .cart-field textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.13);
    }

    .cart-hint {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 0.75rem;
    }

    .cart-error {
        display: none;
        padding: 10px 12px;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 0.84rem;
    }

    .cart-error.visible { display: block; }

    .cart-send {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        min-height: 54px;
        margin-top: 2px;
        padding: 12px 18px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(145deg, #25d366, #128c7e);
        color: #fff;
        font: inherit;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 30px rgba(18, 140, 126, 0.28);
    }

    .cart-send:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    .cart-toast {
        position: fixed;
        top: 14px;
        left: 50%;
        z-index: 80;
        max-width: calc(100% - 28px);
        padding: 11px 16px;
        border-radius: 12px;
        transform: translateX(-50%);
        background: #0f172a;
        color: #fff;
        font-size: 0.86rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.25);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .cart-toast.visible { opacity: 1; }

    .empty-state {
        margin-top: 18px;
        padding: 34px 20px;
        border: 1px dashed #99f6e4;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.86);
        text-align: center;
    }

    .empty-state h2 {
        margin: 0 0 6px;
        font-size: 1.05rem;
    }

    .empty-state p {
        margin: 0 auto 14px;
        max-width: 420px;
        color: var(--muted);
        font-size: 0.9rem;
    }

    @media (min-width: 560px) {
        .business-body { padding: 0 20px 20px; }
        .business-name { font-size: 1.6rem; }
        .business-actions .btn { width: auto; }
        .product-grid { grid-template-columns: 1fr 1fr; }
        .cart-overlay { align-items: center; padding: 20px; }
        .cart-sheet { border-radius: 22px; }
    }

    @media (min-width: 860px) {
        .product-grid { grid-template-columns: repeat(3, 1fr); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$slugNegocio = (string) $negocio['slug'];
$telefono    = preg_replace('/\D+/', '', (string) ($negocio['telefono_principal'] ?? ''));
$whatsapp    = '';

if ($telefono !== '') {
    $whatsapp = strlen($telefono) === 9 ? '51' . $telefono : $telefono;
}

$urlFiltro = static function (string $categoria) use ($slugNegocio, $busqueda): string {
    $params = [];

    if ($categoria !== '') {
        $params['categoria'] = $categoria;
    }

    if ($busqueda !== '') {
        $params['q'] = $busqueda;
    }

    $url = base_url('c/' . $slugNegocio);

    return $params === [] ? $url : $url . '?' . http_build_query($params);
};

$hayFiltro = $categoriaActiva !== '' || $busqueda !== '';
$inicial   = mb_strtoupper(mb_substr((string) $negocio['nombre_comercial'], 0, 1));
?>
<main class="catalog-shell">
    <header class="business-card">
        <div class="business-cover"></div>
        <div class="business-body">
            <div class="business-avatar" aria-hidden="true"><?= esc($inicial) ?></div>
            <div class="business-main">
                <p class="business-eyebrow">Catálogo digital</p>
                <h1 class="business-name"><?= esc($negocio['nombre_comercial']) ?></h1>

                <?php if (! empty($negocio['descripcion'])): ?>
                    <p class="business-desc"><?= esc($negocio['descripcion']) ?></p>
                <?php endif; ?>

                <ul class="business-facts">
                    <?php if (! empty($negocio['direccion'])): ?>
                        <li>
                            <span class="fact-icon" aria-hidden="true">📍</span>
                            <span>
                                <?= esc($negocio['direccion']) ?>
                                <?php if (! empty($negocio['distrito']) || ! empty($negocio['ciudad'])): ?>
                                    · <?= esc(trim((string) ($negocio['distrito'] ?? '') . ', ' . (string) ($negocio['ciudad'] ?? ''), ', ')) ?>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endif; ?>

                    <?php if (! empty($negocio['horario_texto'])): ?>
                        <li>
                            <span class="fact-icon" aria-hidden="true">🕒</span>
                            <span><?= esc($negocio['horario_texto']) ?></span>
                        </li>
                    <?php endif; ?>

                    <?php if ($telefono !== ''): ?>
                        <li>
                            <span class="fact-icon" aria-hidden="true">📞</span>
                            <span><?= esc($telefono) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>

                <?php if ($whatsapp !== ''): ?>
                    <div class="business-actions">
                        <a
                            class="btn"
                            href="<?= esc('https://wa.me/' . $whatsapp, 'attr') ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Contactar por WhatsApp</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="catalog-tools" aria-label="Buscador y categorías">
        <form class="search-form" method="get" action="<?= esc(base_url('c/' . $slugNegocio), 'attr') ?>" role="search">
            <?php if ($categoriaActiva !== ''): ?>
                <input type="hidden" name="categoria" value="<?= esc($categoriaActiva, 'attr') ?>">
            <?php endif; ?>
            <input
                class="search-input"
                type="search"
                name="q"
                value="<?= esc($busqueda, 'attr') ?>"
                placeholder="Buscar en el catálogo..."
                aria-label="Buscar en el catálogo"
                maxlength="100"
            >
            <button class="search-submit" type="submit">Buscar</button>
        </form>

        <?php if ($categorias !== []): ?>
            <nav class="chips" aria-label="Categorías del catálogo">
                <a class="chip <?= $categoriaActiva === '' ? 'active' : '' ?>" href="<?= esc($urlFiltro(''), 'attr') ?>">Todos</a>
                <?php foreach ($categorias as $categoria): ?>
                    <?php $slugCategoria = (string) $categoria['slug']; ?>
                    <a
                        class="chip <?= $categoriaActiva === $slugCategoria ? 'active' : '' ?>"
                        href="<?= esc($urlFiltro($slugCategoria), 'attr') ?>"
                    ><?= esc($categoria['nombre']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </section>

    <div class="products-head">
        <h2 class="products-title">Productos</h2>
        <?php if ($productos !== []): ?>
            <span class="products-count"><?= count($productos) ?> <?= count($productos) === 1 ? 'producto' : 'productos' ?></span>
        <?php endif; ?>
    </div>

    <?php if ($productos === []): ?>
        <section class="empty-state">
            <?php if ($hayFiltro): ?>
                <h2>No encontramos productos</h2>
                <p>Prueba con otra palabra o selecciona una categoría diferente.</p>
                <a class="btn" href="<?= esc($urlFiltro(''), 'attr') ?>">Ver todo el catálogo</a>
            <?php else: ?>
                <h2>Este negocio aún no tiene productos publicados</h2>
                <p>Vuelve pronto para descubrir lo que tiene para ofrecer.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="product-grid" aria-label="Productos del catálogo">
            <?php foreach ($productos as $producto): ?>
                <?= view('catalogo/producto_card', ['producto' => $producto]) ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<button
    type="button"
    id="cartToggle"
    class="cart-float"
    aria-haspopup="dialog"
    aria-controls="cartOverlay"
    disabled
>
    <span>Ver mi pedido</span>
    <span class="cart-float-count">(<span id="cartCount">0</span>)</span>
    <span>-</span>
    <span id="cartTotalFloat">S/ 0.00</span>
</button>

<div class="cart-overlay" id="cartOverlay" hidden>
    <section class="cart-sheet" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
        <div class="cart-head">
            <h2 id="cartTitle">Mi pedido</h2>
            <button type="button" class="cart-close" id="cartClose" aria-label="Cerrar carrito">×</button>
        </div>

        <div class="cart-items" id="cartItems"></div>

        <div class="cart-total-row">
            <span>Total</span>
            <strong id="cartTotal">S/ 0.00</strong>
        </div>

        <form class="cart-form" id="cartForm" novalidate>
            <div class="cart-field">
                <label for="cartNombre">Tu nombre</label>
                <input
                    type="text"
                    id="cartNombre"
                    name="cliente_nombre"
                    maxlength="150"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="cart-field">
                <label for="cartTelefono">Tu teléfono / WhatsApp</label>
                <input
                    type="tel"
                    id="cartTelefono"
                    name="cliente_telefono"
                    maxlength="15"
                    inputmode="numeric"
                    pattern="[0-9]{7,15}"
                    autocomplete="tel"
                    required
                >
                <p class="cart-hint">Solo números, entre 7 y 15 dígitos.</p>
            </div>

            <div class="cart-field">
                <label for="cartNota">Comentario o dirección de entrega</label>
                <textarea
                    id="cartNota"
                    name="comentario"
                    maxlength="500"
                    placeholder="Opcional"
                ></textarea>
            </div>

            <div class="cart-error" id="cartError" role="alert"></div>

            <button type="submit" class="cart-send" id="cartSend">
                Enviar pedido por WhatsApp
            </button>
        </form>
    </section>
</div>

<div class="cart-toast" id="cartToast" role="status" aria-live="polite"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    'use strict';

    const SLUG        = <?= json_encode($slugNegocio) ?>;
    const PEDIDO_URL  = <?= json_encode(base_url('c/' . $slugNegocio . '/pedido')) ?>;
    const CSRF_HEADER = <?= json_encode(csrf_header()) ?>;
    const STORAGE_KEY = 'katalo_carrito_' + SLUG;

    let csrfToken = <?= json_encode(csrf_hash()) ?>;
    let carrito   = leerCarrito();
    let toastTimer = null;

    const botonFlotante = document.getElementById('cartToggle');
    const overlay       = document.getElementById('cartOverlay');
    const listaItems    = document.getElementById('cartItems');
    const formulario    = document.getElementById('cartForm');
    const botonEnviar   = document.getElementById('cartSend');
    const cajaError     = document.getElementById('cartError');
    const toast         = document.getElementById('cartToast');

    function leerCarrito() {
        try {
            const guardado = localStorage.getItem(STORAGE_KEY);
            const datos    = guardado ? JSON.parse(guardado) : [];

            if (!Array.isArray(datos)) {
                return [];
            }

            return datos
                .filter((item) => item && Number.isInteger(Number(item.id_producto)) && Number(item.id_producto) > 0)
                .map((item) => ({
                    id_producto: Number(item.id_producto),
                    titulo: String(item.titulo || ''),
                    precio: Number(item.precio) || 0,
                    cantidad: Math.max(1, parseInt(item.cantidad, 10) || 1),
                }));
        } catch (error) {
            return [];
        }
    }

    function guardarCarrito() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
        } catch (error) {
            // Si el navegador bloquea localStorage, el carrito sigue funcionando en memoria.
        }
    }

    function dinero(valor) {
        return Number(valor || 0).toFixed(2);
    }

    function totalCarrito() {
        return carrito.reduce((total, item) => total + (item.precio * item.cantidad), 0);
    }

    function unidadesCarrito() {
        return carrito.reduce((total, item) => total + item.cantidad, 0);
    }

    function escaparHtml(valor) {
        return String(valor).replace(/[&<>"']/g, (caracter) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[caracter]));
    }

    function renderizarItems() {
        if (carrito.length === 0) {
            listaItems.innerHTML = '<div class="cart-empty">Tu pedido está vacío. Agrega productos del catálogo.</div>';
            return;
        }

        listaItems.innerHTML = carrito.map((item) => `
            <article class="cart-item" data-id="${item.id_producto}">
                <div>
                    <h3 class="cart-item-title">${escaparHtml(item.titulo)}</h3>
                    <span class="cart-item-unit">S/ ${dinero(item.precio)} c/u</span>
                </div>
                <div class="cart-item-subtotal">S/ ${dinero(item.precio * item.cantidad)}</div>
                <div class="cart-qty">
                    <button type="button" data-accion="restar" aria-label="Quitar una unidad">−</button>
                    <input
                        type="number"
                        min="1"
                        step="1"
                        value="${item.cantidad}"
                        data-cantidad
                        aria-label="Cantidad de ${escaparHtml(item.titulo)}"
                    >
                    <button type="button" data-accion="sumar" aria-label="Agregar una unidad">+</button>
                </div>
                <button type="button" class="cart-remove" data-accion="eliminar">Quitar</button>
            </article>
        `).join('');
    }

    function renderizar() {
        const total = totalCarrito();

        document.getElementById('cartCount').textContent = String(unidadesCarrito());
        document.getElementById('cartTotalFloat').textContent = 'S/ ' + dinero(total);
        document.getElementById('cartTotal').textContent = 'S/ ' + dinero(total);

        botonFlotante.disabled = carrito.length === 0;
        botonEnviar.disabled = carrito.length === 0;

        renderizarItems();
    }

    function mostrarError(mensaje) {
        cajaError.textContent = mensaje;
        cajaError.classList.add('visible');
    }

    function ocultarError() {
        cajaError.textContent = '';
        cajaError.classList.remove('visible');
    }

    function mostrarToast(mensaje) {
        toast.textContent = mensaje;
        toast.classList.add('visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('visible'), 4500);
    }

    function abrirCarrito() {
        if (carrito.length === 0) {
            return;
        }

        ocultarError();
        renderizar();
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('cartNombre').focus(), 60);
    }

    function cerrarCarrito() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    document.addEventListener('click', (event) => {
        const boton = event.target.closest('.product-add');

        if (!boton) {
            return;
        }

        const id     = Number(boton.dataset.id);
        const precio = Number(boton.dataset.precio);

        if (!Number.isInteger(id) || id <= 0 || !Number.isFinite(precio)) {
            return;
        }

        const existente = carrito.find((item) => item.id_producto === id);

        if (existente) {
            existente.cantidad += 1;
        } else {
            carrito.push({
                id_producto: id,
                titulo: boton.dataset.titulo || '',
                precio: precio,
                cantidad: 1,
            });
        }

        guardarCarrito();
        renderizar();

        boton.classList.add('added');
        boton.textContent = '✓';
        setTimeout(() => {
            boton.classList.remove('added');
            boton.textContent = '+';
        }, 700);
    });

    listaItems.addEventListener('click', (event) => {
        const boton = event.target.closest('button[data-accion]');

        if (!boton) {
            return;
        }

        const contenedor = boton.closest('.cart-item');
        const id         = Number(contenedor ? contenedor.dataset.id : 0);
        const item       = carrito.find((fila) => fila.id_producto === id);

        if (!item) {
            return;
        }

        if (boton.dataset.accion === 'sumar') {
            item.cantidad += 1;
        } else if (boton.dataset.accion === 'restar') {
            item.cantidad = Math.max(1, item.cantidad - 1);
        } else if (boton.dataset.accion === 'eliminar') {
            carrito = carrito.filter((fila) => fila.id_producto !== id);
        }

        guardarCarrito();
        renderizar();
    });

    listaItems.addEventListener('change', (event) => {
        const entrada = event.target.closest('input[data-cantidad]');

        if (!entrada) {
            return;
        }

        const contenedor = entrada.closest('.cart-item');
        const id         = Number(contenedor ? contenedor.dataset.id : 0);
        const item       = carrito.find((fila) => fila.id_producto === id);
        const cantidad   = parseInt(entrada.value, 10);

        if (!item) {
            return;
        }

        if (!Number.isInteger(cantidad) || cantidad < 1) {
            entrada.value = String(item.cantidad);
            return;
        }

        item.cantidad = cantidad;
        guardarCarrito();
        renderizar();
    });

    botonFlotante.addEventListener('click', abrirCarrito);
    document.getElementById('cartClose').addEventListener('click', cerrarCarrito);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            cerrarCarrito();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !overlay.hidden) {
            cerrarCarrito();
        }
    });

    formulario.addEventListener('submit', async (event) => {
        event.preventDefault();
        ocultarError();

        const nombre   = document.getElementById('cartNombre').value.trim();
        const telefono = document.getElementById('cartTelefono').value.trim();

        if (carrito.length === 0) {
            mostrarError('Tu carrito está vacío. Agrega productos antes de enviar.');
            return;
        }

        if (nombre === '') {
            mostrarError('Ingresa tu nombre para enviar el pedido.');
            document.getElementById('cartNombre').focus();
            return;
        }

        if (!/^[0-9]{7,15}$/.test(telefono)) {
            mostrarError('Ingresa un teléfono válido de 7 a 15 dígitos (solo números).');
            document.getElementById('cartTelefono').focus();
            return;
        }

        const textoOriginal = botonEnviar.textContent.trim();
        botonEnviar.disabled = true;
        botonEnviar.textContent = 'Registrando pedido...';

        try {
            const respuesta = await fetch(PEDIDO_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    [CSRF_HEADER]: csrfToken,
                },
                body: JSON.stringify({
                    cliente_nombre: nombre,
                    cliente_telefono: telefono,
                    cliente_direccion: '',
                    comentario: document.getElementById('cartNota').value.trim(),
                    items: carrito.map((item) => ({
                        id_producto: item.id_producto,
                        cantidad: item.cantidad,
                    })),
                }),
            });

            const tokenRenovado = respuesta.headers.get(CSRF_HEADER);
            if (tokenRenovado) {
                csrfToken = tokenRenovado;
            }

            let datos = {};
            try {
                datos = await respuesta.json();
            } catch (error) {
                datos = {};
            }

            if (!respuesta.ok || !datos.ok) {
                throw new Error(datos.mensaje || 'No pudimos registrar tu pedido. Inténtalo nuevamente.');
            }

            carrito = [];
            guardarCarrito();
            renderizar();

            document.getElementById('cartNombre').value = '';
            document.getElementById('cartTelefono').value = '';
            document.getElementById('cartNota').value = '';

            cerrarCarrito();
            mostrarToast('Pedido ' + datos.codigo + ' registrado. Abriendo WhatsApp...');

            const telefonoNegocio = String(datos.telefono_negocio || '').replace(/\D/g, '');
            const urlWhatsApp = 'https://wa.me/51' + telefonoNegocio
                + '?text=' + encodeURIComponent(datos.mensaje_whatsapp || '');

            const ventana = window.open(urlWhatsApp, '_blank');
            if (!ventana) {
                window.location.href = urlWhatsApp;
            }
        } catch (error) {
            mostrarError(error && error.message
                ? error.message
                : 'No pudimos registrar tu pedido. Inténtalo nuevamente.');
        } finally {
            botonEnviar.disabled = carrito.length === 0;
            botonEnviar.textContent = textoOriginal;
        }
    });

    renderizar();
})();
</script>
<?= $this->endSection() ?>
