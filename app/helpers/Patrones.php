<?php
declare(strict_types=1);

namespace App\Helpers;

// CLASE: Patrones
// OBJETIVO: Única fuente de las expresiones regulares de validación del lado PHP (modelos, traits y controladores)
// NOTA: El lado JavaScript tiene su equivalente en assets/js/patrones.js; si se cambia una regla, cambiarla en los dos
final class Patrones
{
    public const SOLO_LETRAS = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u';
    public const NOMBRE_EMPRESA = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,\-&]+$/u';
    public const CEDULA = '/^[VEJ]-\d{6,9}$/i';
    public const RIF = '/^[JVEG]-\d{1,9}$/';
    public const TELEFONO = '/^\d{11}$/';
    public const REFERENCIA_PAGO = '/^\d{5}$/';
    public const FECHA_ISO = '/^\d{4}-\d{2}-\d{2}$/';
    public const FRACCION = '/^(\d+)\/(\d+)$/';
    public const TEXTO_PELIGROSO = '/[\'"`;\\\\<>]/';
    public const CLAVE_PROHIBIDA = '/[\'"`;\\\\<>]|--|\/\*|\*\//';
    public const CONTROLADOR_URL = '/^[a-z]+$/';
    public const ARCHIVO_IMAGEN = '/^[a-f0-9]{32}\.(jpg|png)$/';
    public const ARCHIVO_PDF = '/^[a-f0-9]{32}\.pdf$/';
    public const PDF_PELIGROSO = '#/(JavaScript|JS|Launch|EmbeddedFile)(?![A-Za-z])#';
    public const SUFIJO_GALON = '/\s*Gal[oó]n.*$/iu';
    public const SUFIJO_BASE_GALON = '/\s*\(Gal[oó]n\)\s*$/iu';
}
