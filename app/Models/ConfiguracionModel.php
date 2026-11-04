<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfiguracionModel extends Model
{
    protected $table            = 'configuracion';
    protected $primaryKey       = 'id_config';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'clave',
        'valor',
        'descripcion',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    public function obtenerValor(string $clave, mixed $default = null): mixed
    {
        $fila = $this->select('valor')
            ->where('clave', $clave)
            ->first();

        if ($fila === null || $fila['valor'] === null) {
            return $default;
        }

        return $fila['valor'];
    }

    public function actualizarPorClave(string $clave, string $valor, ?int $modifiedBy = null): bool
    {
        $fila = $this->where('clave', $clave)->first();

        if ($fila === null) {
            return false;
        }

        $datos = ['valor' => $valor];

        if ($modifiedBy !== null) {
            $datos['modified_by'] = $modifiedBy;
        }

        return $this->update((int) $fila['id_config'], $datos) !== false;
    }
}
