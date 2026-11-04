<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuración del filtro de moderación de contenido.
 *
 * La lista de palabras bloqueadas es editable. En producción puede cargarse
 * desde la tabla configuracion o desde un archivo externo sin cambiar el
 * servicio de moderación.
 */
class Moderation extends BaseConfig
{
    /**
     * @var list<string>
     */
    public array $palabrasBloqueadas = [
        'spam',
        'xxx',
        'porno',
        'puta',
        'puto',
        'mierda',
        'idiota',
        'imbecil',
        'estafa',
        'fraude',
        'droga',
        'drogas',
        'cocaina',
        'marihuana',
        'arma',
        'armas',
        'pistola',
        'matar',
        'muerte',
        'violencia',
        'tonto',
        'basura',
        'scam',
        'viagra',
        'casino',
        'apuesta',
        'apuestas',
    ];

    /**
     * @var list<string>
     */
    public array $camposARevisar = [
        'titulo',
        'descripcion',
        'nombre_comercial',
        'nombre',
    ];
}
