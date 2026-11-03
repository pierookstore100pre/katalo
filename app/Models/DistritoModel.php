<?php

namespace App\Models;

use CodeIgniter\Model;

class DistritoModel extends Model
{
    protected $table            = 'distritos';
    protected $primaryKey       = 'id_distrito';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ciudad_id',
        'nombre',
        'descripcion',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Distritos activos cuya ciudad también está activa.
     *
     * @return list<array<string, mixed>>
     */
    public function getActivosConCiudadActiva(): array
    {
        return $this->select('distritos.id_distrito, distritos.nombre, ciudades.nombre AS ciudad')
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->where('distritos.activo', 1)
            ->where('ciudades.activo', 1)
            ->where('ciudades.deleted_at', null)
            ->orderBy('ciudades.nombre', 'ASC')
            ->orderBy('distritos.nombre', 'ASC')
            ->findAll();
    }
}
