<?php
// Incluimos el archivo de conexión a la base de datos
include_once("../Database/Database.php");

// Verificar conexión
if (!$conexion) {
    $response = ["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()];
    echo json_encode($response);
    exit;
}

// Verificar si la solicitud es para enviar el enlace de reseteo o cambiar la contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['email'])) {
        // Solicitud de enlace de restablecimiento
        $email = mysqli_real_escape_string($conexion, $_POST['email']);

        if (empty($email)) {
            $response = ["status" => "error", "message" => "El correo es obligatorio."];
            echo json_encode($response);
            exit;
        }

        // Verificar si el correo está registrado
        $sql_check = "SELECT id FROM usuarios WHERE email = ?";
        if ($stmt = mysqli_prepare($conexion, $sql_check)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $user_id);
                mysqli_stmt_fetch($stmt);

                // Generar token de reseteo de contraseña
                $token = bin2hex(random_bytes(50)); // Token seguro

                // Guardar el token en la base de datos con una expiración
                $sql_insert_token = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
                if ($stmt_insert = mysqli_prepare($conexion, $sql_insert_token)) {
                    mysqli_stmt_bind_param($stmt_insert, "is", $user_id, $token);
                    mysqli_stmt_execute($stmt_insert);

                    // Enviar el enlace de reseteo por correo
                    $reset_link = "http://localhost/Login/reset_password.php?token=$token";
                    $subject = "Restablecer tu contraseña";
                    $message = "Haz clic en el siguiente enlace para restablecer tu contraseña: $reset_link";
                    $headers = "From: no-reply@chatariem.com";

                    // mail() para enviar correo (descomentar en un servidor con configuración de envío de correos)
                    // mail($email, $subject, $message, $headers);

                    $response = ["status" => "success", "message" => "Enlace de restablecimiento enviado al correo."];
                    echo json_encode($response);
                } else {
                    $response = ["status" => "error", "message" => "Error al generar el enlace de restablecimiento."];
                    echo json_encode($response);
                }
            } else {
                $response = ["status" => "error", "message" => "El correo no está registrado."];
                echo json_encode($response);
            }

            mysqli_stmt_close($stmt);
        } else {
            $response = ["status" => "error", "message" => "Error al preparar la consulta: " . mysqli_error($conexion)];
            echo json_encode($response);
        }
    }

    if (isset($_POST['token']) && isset($_POST['new_password'])) {
        // Restablecimiento de contraseña
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];

        if (empty($token) || empty($new_password)) {
            echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
            exit;
        }

        // Verificar si el token es válido y no ha expirado
        $sql_check_token = "SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1";
        if ($stmt = mysqli_prepare($conexion, $sql_check_token)) {
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $user_id);
                mysqli_stmt_fetch($stmt);

                // Encriptar la nueva contraseña
                $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);

                // Actualizar la contraseña del usuario
                $sql_update_password = "UPDATE usuarios SET password = ? WHERE id = ?";
                if ($stmt_update = mysqli_prepare($conexion, $sql_update_password)) {
                    mysqli_stmt_bind_param($stmt_update, "si", $hashed_password, $user_id);
                    if (mysqli_stmt_execute($stmt_update)) {
                        // Eliminar el token de reseteo
                        $sql_delete_token = "DELETE FROM password_resets WHERE user_id = ?";
                        if ($stmt_delete = mysqli_prepare($conexion, $sql_delete_token)) {
                            mysqli_stmt_bind_param($stmt_delete, "i", $user_id);
                            mysqli_stmt_execute($stmt_delete);

                            echo json_encode(["status" => "success", "message" => "Contraseña actualizada exitosamente."]);
                        }
                    } else {
                        echo json_encode(["status" => "error", "message" => "Error al actualizar la contraseña."]);
                    }
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Token inválido o expirado."]);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al preparar la consulta: " . mysqli_error($conexion)]);
        }
    }
}

// Cerrar la conexión a la base de datos
mysqli_close($conexion);

//Es posible que el código presente errores al estar todo junto, pero planeo probarlo una vez que implemente las plantillas. El flujo debe contar con una parte visible y otra oculta. Primero, se enviará el email mediante POST, y luego se recibirá el token junto con la nueva contraseña (new_password). Una vez que se valide el email correctamente, el formulario para cambiar la contraseña debe habilitarse automáticamente tras completar la primera etapa
?>
