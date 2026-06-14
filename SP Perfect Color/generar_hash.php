<?php
$clave = 'admin123';
$hash = password_hash($clave, PASSWORD_DEFAULT);

echo "Hash generado: " . $hash . "<br>";
echo "Verificacion: " . (password_verify($clave, $hash) ? 'OK' : 'FALLA') . "<br>";
echo "<br>Copia esta linea SQL:<br>";
echo "UPDATE usuarios SET password_hash = '" . $hash . "' WHERE correo = 'admin@perfectcolor.com';";
?>