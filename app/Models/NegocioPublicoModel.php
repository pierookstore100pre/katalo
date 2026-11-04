<?php

namespace App\Models;

use CodeIgniter\Model;

class NegocioPublicoModel extends Model
{
    protected $table            = 'v_negocios_publicos';
    protected $primaryKey       = 'id_negocio';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

    protected $useTimestamps = false;

    /**
     * @return array<string, mixed>|null
     */
    public function findPorSlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
}
