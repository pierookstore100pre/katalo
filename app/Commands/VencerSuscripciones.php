<?php

namespace App\Commands;

use App\Models\LogActividadModel;
use App\Models\PlanModel;
use App\Models\SuscripcionModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

class VencerSuscripciones extends BaseCommand
{
    protected $group       = 'Katalo';
    protected $name        = 'katalo:vencer-suscripciones';
    protected $description = 'Marca suscripciones vencidas y les asigna el plan Gratis.';
    protected $usage       = 'katalo:vencer-suscripciones';

    public function run(array $params)
    {
        $suscripcionModel = model(SuscripcionModel::class);
        $planGratis       = model(PlanModel::class)->obtenerPorSlug('gratis');

        if ($planGratis === null) {
            CLI::error('No se encontró el plan Gratis.');

            return;
        }

        $vencidas = $suscripcionModel
            ->whereIn('estado', ['prueba', 'activa'])
            ->where('fecha_fin IS NOT NULL', null, false)
            ->where('fecha_fin <', date('Y-m-d'))
            ->findAll();

        $db              = db_connect();
        $logModel        = model(LogActividadModel::class);
        $totalVencidas   = 0;
        $totalAsignadas  = 0;

        $db->transBegin();

        try {
            foreach ($vencidas as $suscripcion) {
                $idSuscripcion = (int) $suscripcion['id_suscripcion'];
                $actual = $db->query(
                    'SELECT * FROM suscripciones '
                    . "WHERE id_suscripcion = ? AND estado IN ('prueba', 'activa') "
                    . 'AND deleted_at IS NULL FOR UPDATE',
                    [$idSuscripcion]
                )->getRowArray();

                if ($actual === null) {
                    continue;
                }

                $db->table('suscripciones')
                    ->where('id_suscripcion', $idSuscripcion)
                    ->update(['estado' => 'vencida']);

                $idNueva = (int) $suscripcionModel->insert([
                    'negocio_id'     => (int) $actual['negocio_id'],
                    'plan_id'        => (int) $planGratis['id_plan'],
                    'estado'         => 'activa',
                    'fecha_inicio'   => date('Y-m-d'),
                    'fecha_fin'      => null,
                    'precio_pactado' => '0.00',
                ], true);

                if ($idNueva <= 0) {
                    throw new RuntimeException('No se pudo asignar el plan Gratis.');
                }

                if ($logModel->insert([
                    'usuario_id'     => null,
                    'negocio_id'     => (int) $actual['negocio_id'],
                    'accion'         => 'vencer_suscripcion',
                    'tabla_afectada' => 'suscripciones',
                    'registro_id'    => $idSuscripcion,
                    'descripcion'    => mb_substr(
                        'Suscripción #' . $idSuscripcion . ' vencida; plan Gratis asignado en suscripción #' . $idNueva . '.',
                        0,
                        500
                    ),
                ]) === false) {
                    throw new RuntimeException('No se pudo registrar la actividad del vencimiento.');
                }

                $totalVencidas++;
                $totalAsignadas++;
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al vencer suscripciones: {message}', ['message' => $e->getMessage()]);
            CLI::error('No se pudieron vencer las suscripciones. Revisa el log.');

            return;
        }

        CLI::write(
            $totalVencidas . ' suscripciones vencidas, ' . $totalAsignadas . ' planes Gratis asignados',
            'green'
        );
    }
}
