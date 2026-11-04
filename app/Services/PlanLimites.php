<?php

namespace App\Services;

use App\Models\ImagenProductoModel;
use App\Models\NegocioUsuarioModel;
use App\Models\PedidoModel;
use App\Models\PlanModel;
use App\Models\ProductoModel;
use App\Models\SuscripcionModel;

class PlanLimites
{
    protected SuscripcionModel $suscripcionModel;
    protected PlanModel $planModel;
    protected ProductoModel $productoModel;
    protected ImagenProductoModel $imagenModel;
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected PedidoModel $pedidoModel;

    public function __construct()
    {
        $this->suscripcionModel     = model(SuscripcionModel::class);
        $this->planModel            = model(PlanModel::class);
        $this->productoModel        = model(ProductoModel::class);
        $this->imagenModel          = model(ImagenProductoModel::class);
        $this->negocioUsuarioModel  = model(NegocioUsuarioModel::class);
        $this->pedidoModel          = model(PedidoModel::class);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPlanVigente(int $idNegocio): ?array
    {
        $suscripcion = $this->suscripcionModel->obtenerVigentePorNegocio($idNegocio);

        if ($suscripcion === null) {
            return null;
        }

        return $this->planModel->find((int) $suscripcion['plan_id']);
    }

    public function puedeCrearProducto(int $idNegocio): bool
    {
        $plan = $this->obtenerPlanVigente($idNegocio);
        $max  = $plan['max_productos'] ?? null;

        if ($max === null) {
            return true;
        }

        return $this->productoModel->contarPorNegocio($idNegocio) < (int) $max;
    }

    public function puedeSubirImagen(int $idNegocio, int $idProducto): bool
    {
        if ($this->productoModel->findDelNegocio($idProducto, $idNegocio) === null) {
            return false;
        }

        $plan = $this->obtenerPlanVigente($idNegocio);
        $max  = $plan['max_imagenes_por_producto'] ?? null;

        if ($max === null) {
            return true;
        }

        return $this->imagenModel->contarDelProducto($idProducto) < (int) $max;
    }

    public function puedeCrearPedido(int $idNegocio): bool
    {
        $plan = $this->obtenerPlanVigente($idNegocio);
        $max  = $plan['max_pedidos_mes'] ?? null;

        if ($max === null) {
            return true;
        }

        return $this->pedidoModel->contarDelMes($idNegocio) < (int) $max;
    }

    public function contarUsuariosActivos(int $idNegocio): int
    {
        return $this->negocioUsuarioModel->contarActivosPorNegocio($idNegocio);
    }
}
