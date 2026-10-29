<?php
declare(strict_types=1);

namespace App\Traits;

use PDO;

// TRAIT: EnvaseTrait
// OBJETIVO: Controla el stock de los tintes base por envases (1, 1/2 ... 1/128 de galón) con conversión al abrir un envase mayor
// NOTA: Cada tamaño es un producto hijo (id_producto_padre) del producto de 1 galón; la clase que lo use debe tener $this->conexion
trait EnvaseTrait
{
    private const TAMANOS_128 = [128, 64, 32, 16, 8, 4, 2, 1];

    // FUNCIÓN: factor128
    // OBJETIVO: Convierte '1', '1/2', '1/4'... a 128avos de galón (0 si no es un tamaño válido)
    public function factor128(string $fraccion): int
    {
        $fraccion = trim($fraccion);
        if ($fraccion === '' || $fraccion === '1') {
            return 128;
        }
        if (!preg_match('/^(\d+)\/(\d+)$/', $fraccion, $m) || (int)$m[2] === 0) {
            return 0;
        }
        $valor = (int)$m[1] * 128;
        return $valor % (int)$m[2] === 0 ? intdiv($valor, (int)$m[2]) : 0;
    }

    // FUNCIÓN: etiquetaFraccion
    // OBJETIVO: Texto de la fracción para un tamaño en 128avos (128 => '1', 64 => '1/2')
    public function etiquetaFraccion(int $factor128): string
    {
        return $factor128 >= 128 ? '1' : '1/' . intdiv(128, max(1, $factor128));
    }

    // FUNCIÓN: descomponerVolumen128
    // OBJETIVO: Divide un volumen en 128avos en tamaños estándar de envase, de mayor a menor
    public function descomponerVolumen128(int $volumen): array
    {
        $partes = [];
        foreach (self::TAMANOS_128 as $tamano) {
            while ($volumen >= $tamano) {
                $partes[] = $tamano;
                $volumen -= $tamano;
            }
        }
        return $partes;
    }

    // FUNCIÓN: consumirEnvase
    // OBJETIVO: Descuenta N envases del tamaño del producto; si faltan, abre el envase mayor más pequeño y guarda el sobrante como envases menores
    // NOTA: Debe llamarse dentro de una transacción; lanza RuntimeException si no hay stock suficiente
    public function consumirEnvase(int $idProducto, int $cantidad, ?int $tamano128 = null): void
    {
        $this->_ejecutarConsumoEnvase($idProducto, $cantidad, $tamano128, true);
    }

    // FUNCIÓN: hayEnvases
    // OBJETIVO: Comprueba sin modificar nada si se podrían consumir N envases del tamaño indicado
    public function hayEnvases(int $idProducto, int $cantidad, ?int $tamano128 = null): bool
    {
        try {
            $this->_ejecutarConsumoEnvase($idProducto, $cantidad, $tamano128, false);
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    // FUNCIÓN: volumenFamilia128
    // OBJETIVO: Suma el volumen total (en 128avos de galón) de todos los envases de una familia de tintes
    public function volumenFamilia128(int $idProducto): int
    {
        $stmt = $this->conexion->prepare("SELECT id_producto_padre FROM productos WHERE id_producto = :id");
        $stmt->bindValue(':id', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        $raiz = (int)($stmt->fetchColumn() ?: $idProducto);

        $stmt = $this->conexion->prepare("SELECT fraccion, stock_actual FROM productos WHERE estado = 1 AND (id_producto = :r OR id_producto_padre = :r2)");
        $stmt->bindValue(':r', $raiz, PDO::PARAM_INT);
        $stmt->bindValue(':r2', $raiz, PDO::PARAM_INT);
        $stmt->execute();
        $total = 0;
        foreach ($stmt->fetchAll() as $fila) {
            $total += (int)floor((float)$fila['stock_actual']) * $this->factor128((string)$fila['fraccion']);
        }
        return $total;
    }

    private function _ejecutarConsumoEnvase(int $idProducto, int $cantidad, ?int $tamano128, bool $aplicar): void
    {
        $stmt = $this->conexion->prepare("SELECT id_producto, id_producto_padre, fraccion FROM productos WHERE id_producto = :id");
        $stmt->bindValue(':id', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        $producto = $stmt->fetch();
        if (!$producto) {
            throw new \RuntimeException('Producto no encontrado');
        }

        $raiz = (int)($producto['id_producto_padre'] ?: $producto['id_producto']);
        $tamano = $tamano128 ?? $this->factor128((string)$producto['fraccion']);
        if ($cantidad < 1 || !in_array($tamano, self::TAMANOS_128, true)) {
            throw new \RuntimeException('Cantidad o presentación no válida');
        }

        $stmt = $this->conexion->prepare(
            "SELECT id_producto, codigo, nombre, fraccion, stock_actual, precio_venta, estado FROM productos
             WHERE id_producto = :r OR id_producto_padre = :r2" . ($aplicar ? ' FOR UPDATE' : '')
        );
        $stmt->bindValue(':r', $raiz, PDO::PARAM_INT);
        $stmt->bindValue(':r2', $raiz, PDO::PARAM_INT);
        $stmt->execute();

        $filas = [];
        $stock = array_fill_keys(self::TAMANOS_128, 0);
        $nombreRaiz = '';
        foreach ($stmt->fetchAll() as $fila) {
            $factor = $this->factor128((string)$fila['fraccion']);
            if ((int)$fila['id_producto'] === $raiz) {
                $factor = 128;
                $nombreRaiz = (string)$fila['nombre'];
            }
            if ($factor === 0) {
                continue;
            }
            $filas[$factor] = $fila;
            $stock[$factor] = (int)$fila['estado'] === 1 ? (int)floor((float)$fila['stock_actual']) : 0;
        }

        $tocados = [];
        for ($i = 0; $i < $cantidad; $i++) {
            if ($stock[$tamano] >= 1) {
                $stock[$tamano]--;
                $tocados[$tamano] = true;
                continue;
            }
            $mayor = null;
            foreach (self::TAMANOS_128 as $t) {
                if ($t > $tamano && $stock[$t] >= 1) {
                    $mayor = $t;
                }
            }
            if ($mayor === null) {
                throw new \RuntimeException("Stock insuficiente de '{$nombreRaiz}': no hay envases para {$cantidad} x " . $this->etiquetaFraccion($tamano) . ' galón');
            }
            $stock[$mayor]--;
            $tocados[$mayor] = true;
            foreach ($this->descomponerVolumen128($mayor - $tamano) as $sobrante) {
                $stock[$sobrante]++;
                $tocados[$sobrante] = true;
            }
        }

        if (!$aplicar) {
            return;
        }
        foreach (array_keys($tocados) as $t) {
            $this->_guardarStockEnvase($raiz, $t, $stock[$t], $filas[$t] ?? null, $filas[128]);
        }
    }

    private function _guardarStockEnvase(int $raiz, int $tamano, int $stock, ?array $fila, array $padre): void
    {
        if ($fila !== null) {
            $stmt = $this->conexion->prepare("UPDATE productos SET stock_actual = :s, estado = IF(:s2 > 0, 1, estado) WHERE id_producto = :id");
            $stmt->bindValue(':s', $stock, PDO::PARAM_INT);
            $stmt->bindValue(':s2', $stock, PDO::PARAM_INT);
            $stmt->bindValue(':id', (int)$fila['id_producto'], PDO::PARAM_INT);
            $stmt->execute();
            return;
        }

        $fraccion = $this->etiquetaFraccion($tamano);
        $nombreBase = trim(preg_replace('/\s*\(Gal[oó]n\)\s*$/iu', '', (string)$padre['nombre']));
        $stmt = $this->conexion->prepare(
            "INSERT INTO productos (id_tipo_producto, id_producto_padre, fraccion, codigo, nombre, unidad_medida, stock_actual, stock_minimo, alerta_activa, precio_venta, estado)
             VALUES (1, :padre, :fraccion, :codigo, :nombre, :unidad, :stock, 0, 0, :precio, 1)"
        );
        $stmt->bindValue(':padre', $raiz, PDO::PARAM_INT);
        $stmt->bindValue(':fraccion', $fraccion);
        $stmt->bindValue(':codigo', $padre['codigo'] . '-' . $fraccion);
        $stmt->bindValue(':nombre', $nombreBase . ' ' . $fraccion);
        $stmt->bindValue(':unidad', $fraccion . ' Galon');
        $stmt->bindValue(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindValue(':precio', number_format(round((float)$padre['precio_venta'] * $tamano / 128, 2), 2, '.', ''));
        $stmt->execute();
    }
}
