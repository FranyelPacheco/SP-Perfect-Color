<?php
declare(strict_types=1);

namespace App\Traits;

// TRAIT: ImagenSeguraTrait
// OBJETIVO: Validar y guardar imágenes JPG/PNG subidas, re-codificándolas con GD para descartar contenido embebido
// NOTA: Requiere RespuestaModeloTrait en la clase que lo use
trait ImagenSeguraTrait
{
    // FUNCIÓN: guardarImagenSegura
    // OBJETIVO: Valida el archivo subido y lo guarda con nombre aleatorio en el directorio indicado
    public function guardarImagenSegura(array $archivo, string $directorio, int $maxBytes = 2097152, int $ladoMax = 512): array
    {
        return $this->_ejecutarGuardarImagen($archivo, $directorio, $maxBytes, $ladoMax);
    }

    // FUNCIÓN: eliminarImagenSegura
    // OBJETIVO: Borra una imagen previamente guardada, solo si el nombre cumple el formato generado por este trait
    public function eliminarImagenSegura(?string $nombre, string $directorio): void
    {
        if ($nombre !== null && preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nombre)) {
            $ruta = $directorio . DIRECTORY_SEPARATOR . $nombre;
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }

    private function _ejecutarGuardarImagen(array $archivo, string $directorio, int $maxBytes, int $ladoMax): array
    {
        $error = $this->_validarArchivoSubido($archivo, $maxBytes);
        if ($error !== null) {
            return $this->respuestaError($error);
        }

        $tmp = $archivo['tmp_name'];
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);

        if (!isset($tipos[$mime]) || $info === false || $info[2] !== ($mime === 'image/png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG)) {
            return $this->respuestaError('El archivo no es una imagen JPG o PNG válida');
        }
        if ($info[0] < 50 || $info[1] < 50 || $info[0] * $info[1] > 16000000) {
            return $this->respuestaError('Las dimensiones de la imagen no son válidas (mínimo 50x50 px)');
        }

        $ext = $tipos[$mime];
        $imagen = $ext === 'png' ? @imagecreatefrompng($tmp) : @imagecreatefromjpeg($tmp);
        if ($imagen === false) {
            return $this->respuestaError('No se pudo procesar la imagen, el archivo puede estar dañado');
        }

        if (max($info[0], $info[1]) > $ladoMax) {
            $imagen = imagescale($imagen, $info[0] >= $info[1] ? $ladoMax : -1, $info[1] > $info[0] ? $ladoMax : -1) ?: $imagen;
        }

        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            return $this->respuestaError('No se pudo preparar la carpeta de imágenes');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . $ext;
        $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;
        if ($ext === 'png') {
            imagesavealpha($imagen, true);
            $guardado = imagepng($imagen, $destino, 6);
        } else {
            $guardado = imagejpeg($imagen, $destino, 85);
        }

        if (!$guardado) {
            return $this->respuestaError('No se pudo guardar la imagen');
        }
        @chmod($destino, 0644);

        return $this->respuestaExito('Imagen guardada', ['nombre' => $nombre]);
    }

    private function _validarArchivoSubido(array $archivo, int $maxBytes): ?string
    {
        $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($codigo === UPLOAD_ERR_NO_FILE) {
            return 'Seleccione una imagen';
        }
        if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
            return 'La imagen supera el tamaño máximo permitido de ' . round($maxBytes / 1048576) . ' MB';
        }
        if ($codigo !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
            return 'Error al recibir la imagen, intente nuevamente';
        }
        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > $maxBytes) {
            return 'La imagen debe pesar máximo ' . round($maxBytes / 1048576) . ' MB';
        }
        return null;
    }
}
