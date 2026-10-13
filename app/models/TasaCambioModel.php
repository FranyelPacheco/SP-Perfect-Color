<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

// MODELO: TasaCambioModel
// OBJETIVO: Gestión de tasas de cambio USD/Bs diarias (CRUD sin eliminación física).
class TasaCambioModel extends ModeloBase
{
    // === PROPIEDADES PRIVADAS DE LA ENTIDAD ===
    private ?int $id_tasa_cambio = null;
    private ?float $tasa = null;
    private ?string $fecha = null;
    private ?int $estado = null;

    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodas
    // OBJETIVO: Obtiene el histórico de tasas ordenado descendentemente por fecha
    public function listarTodas(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_tasa_cambio, tasa, fecha, created_at, estado 
                     FROM tasa_cambio 
                     ORDER BY fecha DESC, id_tasa_cambio DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Obtiene una tasa por su clave primaria
    public function buscarPorId(int $id): array|false
    {
        $this->id_tasa_cambio = $id;
        if ($this->id_tasa_cambio < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_tasa_cambio, tasa, fecha, created_at, estado 
                     FROM tasa_cambio 
                     WHERE id_tasa_cambio = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tasa_cambio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: obtenerTasaHoy
    // OBJETIVO: Retorna la tasa registrada para la fecha actual (CURDATE())
    public function obtenerTasaHoy(): array|false
    {
        return $this->_ejecutarSelectHoy();
    }

    private function _ejecutarSelectHoy(): array|false
    {
        $consulta = "SELECT id_tasa_cambio, tasa, fecha, created_at, estado 
                     FROM tasa_cambio 
                     WHERE fecha = CURDATE() AND estado = 1 
                     ORDER BY id_tasa_cambio DESC 
                     LIMIT 1";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: obtenerUltimaTasa
    // OBJETIVO: Obtiene la tasa activa más reciente registrada en el sistema
    public function obtenerUltimaTasa(): array|false
    {
        return $this->_ejecutarSelectUltima();
    }

    private function _ejecutarSelectUltima(): array|false
    {
        $consulta = "SELECT id_tasa_cambio, tasa, fecha, created_at, estado 
                     FROM tasa_cambio 
                     WHERE estado = 1 
                     ORDER BY fecha DESC, id_tasa_cambio DESC 
                     LIMIT 1";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: obtenerTasaVigenteValor
    // OBJETIVO: Retorna el valor numérico float de la tasa del día o la última activa
    public function obtenerTasaVigenteValor(): float
    {
        $hoy = $this->obtenerTasaHoy();
        if ($hoy && isset($hoy['tasa'])) {
            return (float)$hoy['tasa'];
        }
        $ultima = $this->obtenerUltimaTasa();
        return ($ultima && isset($ultima['tasa'])) ? (float)$ultima['tasa'] : 0.0;
    }

    // FUNCIÓN: obtenerTasaPorFecha
    // OBJETIVO: Retorna la tasa activa vigente en una fecha específica (o la más cercana anterior)
    public function obtenerTasaPorFecha(string $fecha): float
    {
        $fechaLimpia = substr(trim($fecha), 0, 10);
        if (empty($fechaLimpia)) {
            return $this->obtenerTasaVigenteValor();
        }
        $sql = "SELECT tasa FROM tasa_cambio 
                WHERE fecha <= :fecha AND estado = 1 
                ORDER BY fecha DESC, id_tasa_cambio DESC 
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':fecha', $fechaLimpia, PDO::PARAM_STR);
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ($val !== false && (float)$val > 0) {
            return (float)$val;
        }
        return $this->obtenerTasaVigenteValor();
    }

    // FUNCIÓN: fechaExiste
    // OBJETIVO: Verifica si ya existe una tasa para la fecha indicada (evitar duplicados)
    public function fechaExiste(string $fecha, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM tasa_cambio WHERE fecha = :fecha";
        if ($excluirId !== null && $excluirId > 0) {
            $sql .= " AND id_tasa_cambio != :excluirId";
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':fecha', $fecha);
        if ($excluirId !== null && $excluirId > 0) {
            $stmt->bindParam(':excluirId', $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // FUNCIÓN: insertar
    // OBJETIVO: Inserta una nueva tasa de cambio para un día específico
    public function insertar(float $tasa, string $fecha): bool
    {
        $this->tasa = $tasa;
        $this->fecha = $fecha;
        if ($this->tasa <= 0 || empty($this->fecha)) return false;
        return $this->_ejecutarInsert();
    }

    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO tasa_cambio (tasa, fecha, created_at, estado) 
                     VALUES (:tasa, :fecha, NOW(), 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':tasa', $this->tasa);
        $stmt->bindParam(':fecha', $this->fecha);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizar
    // OBJETIVO: Edita el valor de una tasa existente
    public function actualizar(int $id, float $tasa, string $fecha, int $estado = 1): bool
    {
        $this->id_tasa_cambio = $id;
        $this->tasa = $tasa;
        $this->fecha = $fecha;
        $this->estado = $estado;
        if ($this->id_tasa_cambio < 1 || $this->tasa <= 0 || empty($this->fecha)) return false;
        return $this->_ejecutarUpdate();
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE tasa_cambio 
                     SET tasa = :tasa, fecha = :fecha, estado = :estado 
                     WHERE id_tasa_cambio = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':tasa', $this->tasa);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':estado', $this->estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id_tasa_cambio, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
