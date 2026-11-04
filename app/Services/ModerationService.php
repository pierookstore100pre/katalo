<?php

namespace App\Services;

use App\Models\LogActividadModel;
use Config\Moderation;

class ModerationService
{
    protected Moderation $config;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->config   = config(Moderation::class);
        $this->logModel = model(LogActividadModel::class);
    }

    public function contieneContenidoBloqueado(string $texto): bool
    {
        return $this->palabrasDetectadas($texto) !== [];
    }

    /**
     * @param array<string, mixed> $datos
     *
     * @return array{
     *     bloqueado: bool,
     *     campos: list<string>,
     *     textos: array<string, string>,
     *     palabras: list<string>
     * }
     */
    public function evaluarCampos(array $datos): array
    {
        $campos   = [];
        $textos   = [];
        $palabras = [];

        foreach ($this->config->camposARevisar as $campo) {
            if (! array_key_exists($campo, $datos)) {
                continue;
            }

            $valor = $datos[$campo];

            if (! is_string($valor) && ! is_numeric($valor)) {
                continue;
            }

            $texto      = (string) $valor;
            $detectadas = $this->palabrasDetectadas($texto);

            if ($detectadas === []) {
                continue;
            }

            $campos[]         = $campo;
            $textos[$campo]   = $texto;

            foreach ($detectadas as $palabra) {
                if (! in_array($palabra, $palabras, true)) {
                    $palabras[] = $palabra;
                }
            }
        }

        return [
            'bloqueado' => $campos !== [],
            'campos'    => $campos,
            'textos'    => $textos,
            'palabras'  => $palabras,
        ];
    }

    /**
     * @param array<string, mixed> $evaluacion Resultado de evaluarCampos()
     */
    public function registrarMarcado(
        string $tabla,
        int $registroId,
        array $evaluacion,
        ?int $negocioId,
        ?int $usuarioId
    ): void {
        $request = service('request');

        $campos   = $evaluacion['campos'] ?? [];
        $textos   = $evaluacion['textos'] ?? [];
        $palabras = $evaluacion['palabras'] ?? [];

        if (array_is_list($campos)) {
            $nombresCampos = $campos;

            if ($textos === []) {
                $textos = array_fill_keys($nombresCampos, '');
            }
        } else {
            $nombresCampos = array_keys($campos);

            if ($textos === []) {
                $textos = $campos;
            }
        }

        $insertado = $this->logModel->insert([
            'usuario_id'     => $usuarioId,
            'negocio_id'     => $negocioId,
            'accion'         => 'contenido_marcado',
            'tabla_afectada' => $tabla,
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr(
                'Contenido marcado automáticamente. Campos afectados: ' . implode(', ', $nombresCampos),
                0,
                500
            ),
            'datos'          => json_encode([
                'campos'               => $textos,
                'palabras_detectadas'  => array_values($palabras),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address'     => $request->getIPAddress(),
            'user_agent'     => mb_substr($request->getUserAgent()->getAgentString(), 0, 255),
        ]);

        if ($insertado === false) {
            log_message('error', 'No se pudo registrar el contenido marcado para {tabla} #{registro}.', [
                'tabla'    => $tabla,
                'registro' => $registroId,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function palabrasDetectadas(string $texto): array
    {
        $texto = $this->normalizar($texto);

        if ($texto === '') {
            return [];
        }

        $detectadas = [];

        foreach ($this->config->palabrasBloqueadas as $palabra) {
            $original   = trim((string) $palabra);
            $normalizada = trim($this->normalizar($original));

            if ($normalizada === '') {
                continue;
            }

            $patron = '/(?<![\p{L}\p{N}_])' . preg_quote($normalizada, '/') . '(?![\p{L}\p{N}_])/u';

            if (preg_match($patron, $texto) === 1 && ! in_array($original, $detectadas, true)) {
                $detectadas[] = $original;
            }
        }

        return $detectadas;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
    }
}
