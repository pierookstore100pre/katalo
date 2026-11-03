<?php

namespace App\Models;

use CodeIgniter\Model;

class LogActividadModel extends Model
{
    protected $table            = 'logs_actividad';
    protected $primaryKey       = 'id_log';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id',
        'negocio_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'descripcion',
        'datos',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = false;
}
