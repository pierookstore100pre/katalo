<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaModel extends Model
{
    protected $table            = 'categorias';
    protected $primaryKey       = 'id_categoria';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'nombre',
        'slug',
        'descripcion',
        'categoria_padre_id',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * @return array<string, mixed>|null
     */
    public function findDelNegocio(int $idCategoria, int $idNegocio): ?array
    {
        return $this->where('id_categoria', $idCategoria)
            ->where('negocio_id', $idNegocio)
            ->first();
    }

    public function slugDisponible(int $idNegocio, string $slug, ?int $excluirId = null): bool
    {
        $builder = $this->withDeleted()
            ->where('negocio_id', $idNegocio)
            ->where('slug', $slug);

        if ($excluirId !== null) {
            $builder->where('id_categoria !=', $excluirId);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getArbolPorNegocio(int $idNegocio): array
    {
        $categorias = $this->where('negocio_id', $idNegocio)
            ->orderBy('nombre', 'ASC')
            ->findAll();

        if ($categorias === []) {
            return [];
        }

        $ids = array_map(static fn (array $categoria): int => (int) $categoria['id_categoria'], $categorias);
        $porPadre = [];

        foreach ($categorias as $categoria) {
            $padreId = $categoria['categoria_padre_id'] !== null
                ? (int) $categoria['categoria_padre_id']
                : 0;

            if ($padreId !== 0 && ! in_array($padreId, $ids, true)) {
                $padreId = 0;
            }

            $porPadre[$padreId][] = $categoria;
        }

        $resultado = [];
        $this->aplanarArbol($porPadre, 0, 0, $resultado);

        return $resultado;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getArbolDisponibleComoPadre(int $idNegocio, ?int $excluirId = null): array
    {
        $arbol = $this->getArbolPorNegocio($idNegocio);

        if ($excluirId === null) {
            return $arbol;
        }

        $excluidos = $this->idsConDescendientes($idNegocio, $excluirId);

        return array_values(array_filter(
            $arbol,
            static fn (array $categoria): bool => ! in_array((int) $categoria['id_categoria'], $excluidos, true)
        ));
    }

    public function tieneProductos(int $idCategoria, int $idNegocio): bool
    {
        return db_connect()
            ->table('productos')
            ->where('negocio_id', $idNegocio)
            ->where('categoria_id', $idCategoria)
            ->countAllResults() > 0;
    }

    public function tieneHijos(int $idCategoria, int $idNegocio): bool
    {
        return $this->where('negocio_id', $idNegocio)
            ->where('categoria_padre_id', $idCategoria)
            ->countAllResults() > 0;
    }

    /**
     * @param array<int, list<array<string, mixed>>> $porPadre
     * @param list<array<string, mixed>>             $resultado
     */
    private function aplanarArbol(array $porPadre, int $padreId, int $nivel, array &$resultado): void
    {
        if ($nivel > 20) {
            return;
        }

        foreach ($porPadre[$padreId] ?? [] as $categoria) {
            $categoria['nivel'] = $nivel;
            $resultado[] = $categoria;
            $this->aplanarArbol($porPadre, (int) $categoria['id_categoria'], $nivel + 1, $resultado);
        }
    }

    /**
     * @return list<int>
     */
    private function idsConDescendientes(int $idNegocio, int $raizId): array
    {
        $categorias = $this->where('negocio_id', $idNegocio)->findAll();
        $hijos = [];

        foreach ($categorias as $categoria) {
            if ($categoria['categoria_padre_id'] === null) {
                continue;
            }

            $hijos[(int) $categoria['categoria_padre_id']][] = (int) $categoria['id_categoria'];
        }

        $visitados = [];
        $pendientes = [$raizId];

        while ($pendientes !== []) {
            $actual = (int) array_pop($pendientes);

            if (isset($visitados[$actual])) {
                continue;
            }

            $visitados[$actual] = true;

            foreach ($hijos[$actual] ?? [] as $hijo) {
                $pendientes[] = $hijo;
            }
        }

        return array_keys($visitados);
    }
}
