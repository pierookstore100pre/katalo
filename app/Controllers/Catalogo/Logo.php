<?php

namespace App\Controllers\Catalogo;

use App\Controllers\BaseController;
use App\Models\NegocioModel;

class Logo extends BaseController
{
    private const MIMES_PERMITIDOS = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    protected NegocioModel $negocioModel;

    public function __construct()
    {
        $this->negocioModel = model(NegocioModel::class);
    }

    public function ver($idNegocio = null)
    {
        $idNegocio = (int) $idNegocio;

        if ($idNegocio <= 0) {
            return $this->noEncontrado();
        }

        $negocio = $this->negocioModel
            ->where('id_negocio', $idNegocio)
            ->first();

        if ($negocio === null || empty($negocio['logo_ruta'])) {
            return $this->noEncontrado();
        }

        $ruta = $this->rutaAbsoluta((string) $negocio['logo_ruta']);

        if ($ruta === null || ! is_file($ruta)) {
            return $this->noEncontrado();
        }

        $mime = @mime_content_type($ruta);

        if ($mime === false || ! in_array($mime, self::MIMES_PERMITIDOS, true)) {
            return $this->noEncontrado();
        }

        $contenido = @file_get_contents($ruta);

        if ($contenido === false) {
            return $this->noEncontrado();
        }

        return $this->response
            ->removeHeader('Cache-Control')
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) strlen($contenido))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($contenido);
    }

    private function rutaAbsoluta(string $ruta): ?string
    {
        $partes = explode(
            DIRECTORY_SEPARATOR,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($ruta))
        );

        if (count($partes) !== 4
            || $partes[0] !== 'uploads'
            || $partes[1] !== 'logos'
            || ! ctype_digit($partes[2])
            || basename($partes[3]) !== $partes[3]
        ) {
            return null;
        }

        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $partes);
    }

    private function noEncontrado()
    {
        return $this->response
            ->setStatusCode(404)
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setBody('Logo no disponible.');
    }
}
