<?php
declare(strict_types=1);

namespace App\Traits;

use PDO;

// TRAIT: ContactoTrait
// OBJETIVO: Teléfonos y correo compartidos por ClienteModel y ProveedorModel (armado, validación, unicidad y guardado)
// NOTA: La clase define sus tablas con los métodos abstractos
trait ContactoTrait
{
    abstract protected function tablaContacto(): string;     // clientes | proveedores
    abstract protected function pkContacto(): string;        // id_cliente | id_proveedor
    abstract protected function tablaTelefonos(): string;    // telefono_cliente | telefono_proveedor
    abstract protected function etiquetaContacto(): string;  // cliente | proveedor

    // FUNCIÓN: procesarTelefonosPayload
    // OBJETIVO: Compone el teléfono principal y el secundario (operadora + número) desde el arreglo de datos
    protected function procesarTelefonosPayload(array $datos): array
    {
        return [$this->_ejecutarArmarTelefono($datos, ''), $this->_ejecutarArmarTelefono($datos, '2')];
    }

    private function _ejecutarArmarTelefono(array $datos, string $sufijo): string
    {
        $telefono = trim($datos['telefono' . $sufijo] ?? '');
        $numero = trim($datos['telefono_num' . $sufijo] ?? '');
        if ($telefono === '' && $numero !== '') {
            $telefono = trim($datos['operadora' . $sufijo] ?? '') . $numero;
        }
        return $telefono;
    }

    // FUNCIÓN: validarTelefonos
    // OBJETIVO: Valida formato (11 dígitos), que no se repitan entre sí y que no pertenezcan a otro registro activo; devuelve el error o null
    public function validarTelefonos(string $telefono, string $telefono2, ?int $idActual = null): ?string
    {
        return $this->_ejecutarValidarTelefonos($telefono, $telefono2, $idActual);
    }

    private function _ejecutarValidarTelefonos(string $telefono, string $telefono2, ?int $idActual): ?string
    {
        $etiqueta = $this->etiquetaContacto();

        if ($telefono === '') {
            return 'El teléfono principal es obligatorio';
        }
        if (!preg_match('/^\d{11}$/', $telefono)) {
            return 'El teléfono principal debe tener 11 dígitos';
        }
        if ($telefono2 !== '') {
            if (!preg_match('/^\d{11}$/', $telefono2)) {
                return 'El segundo teléfono debe tener 11 dígitos';
            }
            if ($telefono === $telefono2) {
                return 'El segundo teléfono no puede ser igual al teléfono principal';
            }
        }
        if ($this->telefonoExiste($telefono, $idActual)) {
            return "El teléfono principal ya se encuentra registrado en otro {$etiqueta}";
        }
        if ($telefono2 !== '' && $this->telefonoExiste($telefono2, $idActual)) {
            return "El segundo teléfono ya se encuentra registrado en otro {$etiqueta}";
        }
        return null;
    }

    // FUNCIÓN: telefonoExiste
    // OBJETIVO: Verifica si un teléfono ya está registrado en un registro activo (excluyendo opcionalmente uno)
    public function telefonoExiste(string $telefono, ?int $idExcluir = null): bool
    {
        if (trim($telefono) === '') return false;
        return $this->_ejecutarTelefonoExiste(trim($telefono), $idExcluir);
    }

    private function _ejecutarTelefonoExiste(string $telefono, ?int $idExcluir): bool
    {
        $pk = $this->pkContacto();
        $consulta = "SELECT COUNT(*) FROM {$this->tablaTelefonos()} t
                     INNER JOIN {$this->tablaContacto()} e ON t.{$pk} = e.{$pk}
                     WHERE t.telefono = :telefono AND e.estado = 1"
            . ($idExcluir !== null ? " AND t.{$pk} != :id_excluir" : '');
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':telefono', $telefono, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id_excluir', $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // FUNCIÓN: correoExiste
    // OBJETIVO: Verifica si un correo ya está registrado en otro registro activo
    public function correoExiste(string $correo, ?int $idExcluir = null): bool
    {
        if (trim($correo) === '') return false;
        return $this->_ejecutarCorreoExiste(trim($correo), $idExcluir);
    }

    private function _ejecutarCorreoExiste(string $correo, ?int $idExcluir): bool
    {
        $pk = $this->pkContacto();
        $consulta = "SELECT COUNT(*) FROM {$this->tablaContacto()} WHERE correo = :correo AND estado = 1"
            . ($idExcluir !== null ? " AND {$pk} != :id" : '');
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id', $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // FUNCIÓN: insertarTelefono
    // OBJETIVO: Inserta un teléfono asociado al registro
    public function insertarTelefono(int $idContacto, string $telefono, ?string $tipo = null): bool
    {
        if ($idContacto < 1 || $telefono === '') return false;
        return $this->_ejecutarInsertTelefono($idContacto, $telefono);
    }

    private function _ejecutarInsertTelefono(int $idContacto, string $telefono): bool
    {
        $stmt = $this->conexion->prepare("INSERT INTO {$this->tablaTelefonos()} ({$this->pkContacto()}, telefono, estado) VALUES (:id, :telefono, 1)");
        $stmt->bindValue(':id', $idContacto, PDO::PARAM_INT);
        $stmt->bindValue(':telefono', $telefono, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminarTelefonos
    // OBJETIVO: Elimina todos los teléfonos del registro (reemplazo completo)
    public function eliminarTelefonos(int $idContacto): bool
    {
        if ($idContacto < 1) return false;
        return $this->_ejecutarDeleteTelefonos($idContacto);
    }

    private function _ejecutarDeleteTelefonos(int $idContacto): bool
    {
        $stmt = $this->conexion->prepare("DELETE FROM {$this->tablaTelefonos()} WHERE {$this->pkContacto()} = :id");
        $stmt->bindValue(':id', $idContacto, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
