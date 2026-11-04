<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NegocioModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $negocioModel = model(NegocioModel::class);
        $db           = db_connect();

        $productos = $db->query(
            'SELECT COUNT(*) AS total
             FROM productos p
             JOIN negocios n ON n.id_negocio = p.negocio_id
             WHERE p.visible = 1
               AND p.estado = \'activo\'
               AND p.deleted_at IS NULL
               AND n.deleted_at IS NULL'
        )->getRowArray();

        $pedidosMes = $db->query(
            'SELECT COUNT(*) AS total
             FROM pedidos
             WHERE deleted_at IS NULL
               AND YEAR(created_at) = YEAR(CURDATE())
               AND MONTH(created_at) = MONTH(CURDATE())'
        )->getRowArray();

        $mrr = $db->query(
            'SELECT COALESCE(SUM(precio_pactado), 0) AS total
             FROM suscripciones
             WHERE estado = \'activa\'
               AND deleted_at IS NULL'
        )->getRowArray();

        $porCiudad = $db->query(
            'SELECT c.id_ciudad, c.nombre, c.activo,
                    (
                        SELECT COUNT(*)
                        FROM negocios n
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND n.activo = 1
                          AND n.deleted_at IS NULL
                    ) AS negocios_activos,
                    (
                        SELECT COUNT(*)
                        FROM productos p
                        JOIN negocios n ON n.id_negocio = p.negocio_id
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND p.visible = 1
                          AND p.estado = \'activo\'
                          AND p.deleted_at IS NULL
                          AND n.deleted_at IS NULL
                    ) AS productos,
                    (
                        SELECT COUNT(*)
                        FROM pedidos p
                        JOIN negocios n ON n.id_negocio = p.negocio_id
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND p.deleted_at IS NULL
                          AND n.deleted_at IS NULL
                          AND YEAR(p.created_at) = YEAR(CURDATE())
                          AND MONTH(p.created_at) = MONTH(CURDATE())
                    ) AS pedidos_mes,
                    (
                        SELECT COALESCE(SUM(s.precio_pactado), 0)
                        FROM suscripciones s
                        JOIN negocios n ON n.id_negocio = s.negocio_id
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND s.estado = \'activa\'
                          AND s.deleted_at IS NULL
                          AND n.deleted_at IS NULL
                    ) AS mrr
             FROM ciudades c
             WHERE c.deleted_at IS NULL
             ORDER BY c.nombre ASC'
        )->getResultArray();

        return view('admin/dashboard', [
            'titulo'   => 'Dashboard',
            'metricas' => [
                'negocios_activos'     => $negocioModel->contarActivos(),
                'negocios_suspendidos' => $negocioModel->contarSuspendidos(),
                'productos'            => (int) ($productos['total'] ?? 0),
                'pedidos_mes'          => (int) ($pedidosMes['total'] ?? 0),
                'mrr'                  => (float) ($mrr['total'] ?? 0),
            ],
            'porCiudad' => $porCiudad,
        ]);
    }
}
