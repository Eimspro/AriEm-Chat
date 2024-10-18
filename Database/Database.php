<?php
// Datos de conexión
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

?>
