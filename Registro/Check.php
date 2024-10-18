<?php
include_once("../Database/Database.php");

// Crear conexión
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_datos);

if (!$conexion) {
    $response = ["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()];
    echo json_encode($response);
    exit;
}

// Función para generar un nombre alfanumérico de 10 caracteres
function generateRandomTableName($length = 10) {
    return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", ceil($length / 62))), 1, $length);
}

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Limpiar escapes o inyecciones
    $name = mysqli_real_escape_string($conexion, $_POST['name']);
    $phone = mysqli_real_escape_string($conexion, $_POST['phone']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $password = $_POST['password']; // No escapar, ya que será hasheada
    
    // Validar los campos
    if (empty($name) || empty($phone) || empty($email) || empty($password)) {
        $response = ["status" => "error", "message" => "Todos los campos son obligatorios."];
        echo json_encode($response);
        exit;
    }

    // Verificar si el teléfono o correo electrónico ya existen
    $sql_check = "SELECT id FROM usuarios WHERE phone = ? OR email = ?";
    if ($stmt_check = mysqli_prepare($conexion, $sql_check)) {
        // Vincular parámetros
        mysqli_stmt_bind_param($stmt_check, "ss", $phone, $email);
        
        // Ejecutar la consulta
        mysqli_stmt_execute($stmt_check);
        
        // Obtener el resultado
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $response = ["status" => "error", "message" => "El número de teléfono o el correo electrónico ya están registrados."];
            echo json_encode($response);
            exit;
        }
        
        // Cerrar la sentencia de verificación
        mysqli_stmt_close($stmt_check);
    } else {
        $response = ["status" => "error", "message" => "Error al verificar la existencia de datos: " . mysqli_error($conexion)];
        echo json_encode($response);
        exit;
    }
    
    // Encriptar la contraseña con Argon2
    $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

    // Generar un nombre aleatorio para la tabla privada
    $private_table_name = generateRandomTableName();

    // Realizar la inserción en la tabla de usuarios
    $sql = "INSERT INTO usuarios (name, phone, password, email, private_table) VALUES (?, ?, ?, ?, ?)";
    
    // Preparar la sentencia
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        // Vincular parámetros
        mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $hashed_password, $email, $private_table_name);
        
        // Ejecutar
        if (mysqli_stmt_execute($stmt)) {
            // Crear la tabla privada para el usuario
            $sql_create_table = "CREATE TABLE `$private_table_name` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone_send VARCHAR(15),
                phone_receive VARCHAR(15),
                name_send VARCHAR(255),
                name_receive VARCHAR(255),
                message TEXT
            )";

            // Intentar crear la tabla
            if (mysqli_query($conexion, $sql_create_table)) {
                // Establecer la cookie con la opción Secure y HttpOnly
                $cookie_value = json_encode(['name' => $name, 'phone' => $phone]);
                setcookie("loginfull", $cookie_value, time() + (86400 * 30), "/", "", true, true); // Dominio, ruta, tiempo de expiración, Secure, HttpOnly
                
                $response = ["status" => "success", "message" => "Usuario registrado exitosamente y tabla privada creada."];
            } else {
                // Si falla la creación de la tabla, deshacer la inserción del usuario
                $delete_user_sql = "DELETE FROM usuarios WHERE id = LAST_INSERT_ID()"; // Eliminar el último usuario insertado
                mysqli_query($conexion, $delete_user_sql);
                
                $response = ["status" => "error", "message" => "Usuario registrado, pero ocurrió un error al crear la tabla privada: " . mysqli_error($conexion)];
            }
        } else {
            $response = ["status" => "error", "message" => "Error: " . mysqli_stmt_error($stmt)];
        }

        echo json_encode($response);
        
        // Cerrar la sentencia
        mysqli_stmt_close($stmt);
    } else {
        $response = ["status" => "error", "message" => "Error al preparar la consulta: " . mysqli_error($conexion)];
        echo json_encode($response);
    }
}

// Cerrar la conexión a la base de datos
mysqli_close($conexion);
?>
