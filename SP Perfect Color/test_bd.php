<?php
// Archivo de prueba de conexion a base de datos

echo "<h2>Prueba de Conexion</h2>";

// Verificar si el driver PDO MySQL esta disponible
echo "<p>Drivers PDO disponibles: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";

// Probar conexion directa
try {
    $conexion = new PDO(
        'mysql:host=localhost;dbname=sp_perfect_color;charset=utf8mb4',
        'root',
        ''
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Conexion a la base de datos exitosa.</p>";
    
    // Verificar si la tabla usuarios existe y tiene datos
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
    $resultado = $stmt->fetch();
    echo "<p>Usuarios registrados: " . $resultado['total'] . "</p>";
    
    // Mostrar los usuarios para verificar
    $stmt = $conexion->query("SELECT id, nombre, correo, rol_id, activo FROM usuarios");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th></tr>";
    while ($fila = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $fila['id'] . "</td>";
        echo "<td>" . $fila['nombre'] . "</td>";
        echo "<td>" . $fila['correo'] . "</td>";
        echo "<td>" . $fila['rol_id'] . "</td>";
        echo "<td>" . $fila['activo'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error de conexion: " . $e->getMessage() . "</p>";
}

// Verificar el hash de la clave
echo "<h2>Prueba de Password</h2>";
$clavePrueba = 'admin123';
$hashAlmacenado = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($clavePrueba, $hashAlmacenado)) {
    echo "<p style='color:green;'>La clave admin123 coincide con el hash almacenado.</p>";
} else {
    echo "<p style='color:red;'>La clave NO coincide. Posible problema con el hash.</p>";
}
?>