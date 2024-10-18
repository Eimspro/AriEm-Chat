<?php
include_once("../Database/Database.php"); // Incluir la conexión a la base de datos

// Activar el reporte de errores para facilitar la depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Comprobar si la cookie "loginfull" existe
if (isset($_COOKIE['loginfull'])) {
    $cookie_data = json_decode($_COOKIE['loginfull'], true);

    // Validar que los datos de la cookie sean válidos
    if (isset($cookie_data['id']) && isset($cookie_data['name'])) {
        $user_id = htmlspecialchars($cookie_data['id']); // Obtiene el ID del usuario desde la cookie y lo escapa
        $user_name = htmlspecialchars($cookie_data['name']); // Obtiene el nombre del usuario desde la cookie y lo escapa

        // Crear conexión a la base de datos
        $conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_datos);

        if (!$conexion) {
            $response = ["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()];
            header('Content-Type: application/json'); // Asegurarse de establecer el encabezado
            echo json_encode($response);
            exit;
        }

        // Realizar la consulta para obtener el campo private_table
        $sql = "SELECT private_table FROM usuarios WHERE id = ?";
        if ($stmt = mysqli_prepare($conexion, $sql)) {
            // Vincular el parámetro
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            // Ejecutar la consulta
            mysqli_stmt_execute($stmt);
            
            // Obtener el resultado
            mysqli_stmt_bind_result($stmt, $private_table);
            mysqli_stmt_fetch($stmt);

            // Cerrar la sentencia
            mysqli_stmt_close($stmt);
            
            // Verificar si se encontró el private_table
            if ($private_table) {
                // Imprimir el resultado en formato JSON
                $response = ["status" => "success", "private_table" => $private_table];
            } else {
                $response = ["status" => "error", "message" => "No se encontró la tabla privada para este usuario."];
            }
        } else {
            $response = ["status" => "error", "message" => "Error al preparar la consulta: " . mysqli_error($conexion)];
        }

        // Cerrar la conexión a la base de datos
        mysqli_close($conexion);
        
        // Enviar la respuesta en formato JSON
        header('Content-Type: application/json'); // Asegurarse de establecer el encabezado
        echo json_encode($response);
    } else {
        // Si los datos no son válidos, redirigir a la página de inicio de sesión
        header("Location: ../Login/login.php");
        exit;
    }
} else {
    // Redirigir a la página de inicio de sesión si no existe la cookie
    header("Location: ../Login/login.php");
    exit;
}
?>
