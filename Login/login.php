<?php
include_once("../Database/Database.php");

// Conectar a la base de datos
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_datos);

if (!$conexion) {
    $response = ["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()];
    echo json_encode($response);
    exit;
}

// Revisar si la cookie "loginfull" ya existe
if (isset($_COOKIE['loginfull'])) {
    $cookie_data = json_decode($_COOKIE['loginfull'], true);
    $response = ["status" => "success", "message" => "Ya has iniciado sesión", "user" => $cookie_data];
    echo json_encode($response);
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = mysqli_real_escape_string($conexion, $_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $response = ["status" => "error", "message" => "El correo y la contraseña son obligatorios."];
            echo json_encode($response);
            exit;
        }

        $sql = "SELECT id, name, password FROM usuarios WHERE email = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $hashed_password);
                mysqli_stmt_fetch($stmt);

                if (password_verify($password, $hashed_password)) {
                    $cookie_value = json_encode(['id' => $id, 'name' => $name]);
                    setcookie("loginfull", $cookie_value, time() + (86400 * 30), "/");

                    $response = ["status" => "success", "message" => "Inicio de sesión exitoso.", "user" => ["id" => $id, "name" => $name]];
                } else {
                    $response = ["status" => "error", "message" => "Contraseña incorrecta."];
                }
            } else {
                $response = ["status" => "error", "message" => "No se encontró ninguna cuenta con ese correo electrónico."];
            }

            mysqli_stmt_close($stmt);
        } else {
            $response = ["status" => "error", "message" => "Error al preparar la consulta: " . mysqli_error($conexion)];
        }

        echo json_encode($response);
    }
}

mysqli_close($conexion);
//Paseme el email y el password en post podemos tambiem validar el phone tambien podria Hacer redireccion a alguna pagina principal
?>
