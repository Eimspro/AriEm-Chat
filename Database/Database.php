<?php
// Emilio Madriz
$servidor = "localhost"; 
$usuario = "root"; 
$contrasena = ""; 
$base_datos = "Chat"; 

// Crear conexión
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_datos);

// Verificar la conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}


// Cerrar la conexión al final
mysqli_close($conexion);
?>
