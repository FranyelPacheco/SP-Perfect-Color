<?php
declare(strict_types=1);

namespace App\Traits;

// TRAIT: PdfSeguroTrait
// OBJETIVO: Validar y guardar documentos PDF subidos, con nombre aleatorio y rechazando los que traen scripts o archivos incrustados
// NOTA: Requiere RespuestaModeloTrait en la clase que lo use; el directorio de destino debe estar fuera de la carpeta pública
trait PdfSeguroTrait
{
    // FUNCIÓN: guardarPdfSeguro
    // OBJETIVO: Valida el PDF subido y lo guarda con nombre aleatorio en el directorio indicado
    public function guardarPdfSeguro(array $archivo, string $directorio, int $maxBytes = 5242880): array
    {
        return $this->_ejecutarGuardarPdf($archivo, $directorio, $maxBytes);
    }

    // FUNCIÓN: eliminarPdfSeguro
    // OBJETIVO: Borra un PDF previamente guardado, solo si el nombre cumple el formato generado por este trait
    public function eliminarPdfSeguro(?string $nombre, string $directorio): void
    {
        $ruta = $this->rutaPdfSeguro($nombre, $directorio);
        if ($ruta !== null) {
            @unlink($ruta);
        }
    }

    // FUNCIÓN: rutaPdfSeguro
    // OBJETIVO: Devuelve la ruta completa de un PDF guardado, o null si el nombre no es válido o el archivo no existe
    public function rutaPdfSeguro(?string $nombre, string $directorio): ?string
    {
        if ($nombre === null || !preg_match('/^[a-f0-9]{32}\.pdf$/', $nombre)) {
            return null;
        }
        $ruta = $directorio . DIRECTORY_SEPARATOR . $nombre;
        return is_file($ruta) ? $ruta : null;
    }

    private function _ejecutarGuardarPdf(array $archivo, string $directorio, int $maxBytes): array
    {
        $error = $this->_validarPdfSubido($archivo, $maxBytes);
        if ($error !== null) {
            return $this->respuestaError($error);
        }

        $tmp = $archivo['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $contenido = (string)file_get_contents($tmp);

        if ($mime !== 'application/pdf' || !str_starts_with($contenido, '%PDF-')) {
            return $this->respuestaError('El archivo no es un PDF válido');
        }
        if (preg_match('#/(JavaScript|JS|Launch|EmbeddedFile)(?![A-Za-z])#', $contenido)) {
            return $this->respuestaError('El PDF contiene scripts o archivos incrustados y no se puede aceptar');
        }

        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            return $this->respuestaError('No se pudo preparar la carpeta de documentos');
        }

        $nombre = bin2hex(random_bytes(16)) . '.pdf';
        $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;
        if (!move_uploaded_file($tmp, $destino)) {
            return $this->respuestaError('No se pudo guardar el documento');
        }
        @chmod($destino, 0644);

        return $this->respuestaExito('Documento guardado', ['nombre' => $nombre]);
    }

    private function _validarPdfSubido(array $archivo, int $maxBytes): ?string
    {
        $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        $limite = round($maxBytes / 1048576);
        if ($codigo === UPLOAD_ERR_NO_FILE) {
            return 'Seleccione un archivo PDF';
        }
        if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
            return 'El PDF supera el tamaño máximo permitido de ' . $limite . ' MB';
        }
        if ($codigo !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            return 'Error al recibir el documento, intente nuevamente';
        }
        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > $maxBytes) {
            return 'El PDF debe pesar máximo ' . $limite . ' MB';
        }
        return null;
    }
}
