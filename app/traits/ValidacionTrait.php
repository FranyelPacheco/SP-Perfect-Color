<?php
declare(strict_types=1);

namespace App\Traits;

// TRAIT: ValidacionTrait
// OBJETIVO: Reglas de validación compartidas por los modelos (vía ModeloBase) y por validacionHelper
trait ValidacionTrait
{
    // FUNCIÓN: validarCorreo
    // OBJETIVO: Verificar que un correo electrónico tenga una estructura válida
    public function validarCorreo($correo): bool
    {
        return $this->_ejecutarValidarCorreo((string)$correo);
    }

    private function _ejecutarValidarCorreo(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }

    // FUNCIÓN: validarRequerido
    // OBJETIVO: Verificar que un campo de texto no esté vacío después de eliminar espacios
    public function validarRequerido($valor): bool
    {
        return $this->_ejecutarValidarRequerido((string)$valor);
    }

    private function _ejecutarValidarRequerido(string $valor): bool
    {
        return trim($valor) !== '';
    }

    // FUNCIÓN: validarFecha
    // OBJETIVO: Verificar que una fecha tenga formato YYYY-MM-DD y sea una fecha real del calendario
    public function validarFecha($fecha): bool
    {
        return $this->_ejecutarValidarFecha((string)$fecha);
    }

    private function _ejecutarValidarFecha(string $fecha): bool
    {
        $partes = explode('-', $fecha);
        if (count($partes) !== 3) {
            return false;
        }
        return checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0]);
    }

    // FUNCIÓN: validarClave
    // OBJETIVO: Verificar que la contraseña tenga entre 6 y 20 caracteres y no contenga caracteres de inyección
    public function validarClave(string $clave, ?string &$error = null): bool
    {
        $error = $this->_ejecutarValidarClave($clave);
        return $error === null;
    }

    private function _ejecutarValidarClave(string $clave): ?string
    {
        $longitud = strlen($clave);
        if ($longitud < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($longitud > 20) {
            return 'La contraseña no puede exceder los 20 caracteres';
        }
        if ($this->_ejecutarTieneCaracteresProhibidos($clave) || str_contains($clave, '--') || str_contains($clave, '/*') || str_contains($clave, '*/')) {
            return 'La contraseña contiene caracteres no permitidos (evite comillas, punto y coma, barras invertidas o símbolos < >)';
        }
        return null;
    }

    // FUNCIÓN: validarTextoSeguro
    // OBJETIVO: Validar un texto libre (obligatorio opcional, longitud máxima y sin caracteres peligrosos); devuelve el error o null
    public function validarTextoSeguro(string $valor, int $maximo, string $etiqueta, bool $obligatorio = true): ?string
    {
        return $this->_ejecutarValidarTextoSeguro(trim($valor), $maximo, $etiqueta, $obligatorio);
    }

    private function _ejecutarValidarTextoSeguro(string $valor, int $maximo, string $etiqueta, bool $obligatorio): ?string
    {
        if ($valor === '') {
            return $obligatorio ? "{$etiqueta} es obligatorio" : null;
        }
        if (mb_strlen($valor) > $maximo) {
            return "{$etiqueta} no puede superar los {$maximo} caracteres";
        }
        if ($this->_ejecutarTieneCaracteresProhibidos($valor)) {
            return "{$etiqueta} contiene caracteres no permitidos";
        }
        return null;
    }

    // FUNCIÓN: validarNombrePersona
    // OBJETIVO: Validar nombres y apellidos (obligatorio, longitud y solo letras con acentos y espacios); devuelve el error o null
    public function validarNombrePersona(string $valor, int $maximo, string $etiqueta): ?string
    {
        return $this->_ejecutarValidarNombrePersona(trim($valor), $maximo, $etiqueta);
    }

    private function _ejecutarValidarNombrePersona(string $valor, int $maximo, string $etiqueta): ?string
    {
        if ($valor === '') {
            return "{$etiqueta} es obligatorio";
        }
        if (mb_strlen($valor) > $maximo) {
            return "{$etiqueta} no puede exceder {$maximo} caracteres";
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $valor)) {
            return "{$etiqueta} solo puede contener letras y espacios";
        }
        return null;
    }

    // FUNCIÓN: validarPagoBancario
    // OBJETIVO: Regla común de pagos: efectivo no lleva banco; los tipos indicados exigen banco y referencia
    // NOTA: Devuelve ['valido', 'mensaje', 'id_banco', 'referencia'] con banco/referencia ya normalizados
    public function validarPagoBancario(int $tipoPago, ?int $banco, ?string $referencia, string $contexto = 'el pago', ?array $tiposConBanco = [2, 3], string $referenciaEfectivo = 'Efectivo'): array
    {
        return $this->_ejecutarValidarPagoBancario($tipoPago, $banco, trim((string)$referencia), $contexto, $tiposConBanco, $referenciaEfectivo);
    }

    private function _ejecutarValidarPagoBancario(int $tipoPago, ?int $banco, string $referencia, string $contexto, ?array $tiposConBanco, string $referenciaEfectivo): array
    {
        if ($tipoPago === 1) {
            return ['valido' => true, 'mensaje' => '', 'id_banco' => null, 'referencia' => $referencia !== '' ? $referencia : $referenciaEfectivo];
        }

        // null en $tiposConBanco significa que todo pago distinto de efectivo exige banco
        $exigeBanco = $tiposConBanco === null || in_array($tipoPago, $tiposConBanco, true);
        if ($exigeBanco) {
            if (empty($banco) || $banco < 1) {
                return ['valido' => false, 'mensaje' => "Debe seleccionar el banco para {$contexto}"];
            }
            if ($referencia === '') {
                return ['valido' => false, 'mensaje' => "Debe ingresar el número de referencia para {$contexto}"];
            }
        }

        return ['valido' => true, 'mensaje' => '', 'id_banco' => $banco ?: null, 'referencia' => $referencia !== '' ? $referencia : null];
    }

    private function _ejecutarTieneCaracteresProhibidos(string $valor): bool
    {
        return preg_match('/[\'"`;\\\\<>]/', $valor) === 1;
    }
}
