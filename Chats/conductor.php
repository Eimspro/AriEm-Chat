<?php
include_once("../Database/Database.php"); 

header('Content-Type: application/json'); 

$response = []; // Inicializar la respuesta

// Comprobar si la cookie "loginfull" existe
if (isset($_COOKIE['loginfull'])) {
    $cookie_data = json_decode($_COOKIE['loginfull'], true);

    // Validar que los datos de la cookie sean válidos
    if (isset($cookie_data['id']) && isset($cookie_data['name'])) {
        $user_id = htmlspecialchars($cookie_data['id']); // Escapar el ID del usuario
        $user_name = htmlspecialchars($cookie_data['name']); // Escapar el nombre del usuario

        // Verificar si la conexión sigue activa
        if ($conexion->connect_errno) {
            $response['error'] = "Error en la conexión a la base de datos: " . $conexion->connect_error;
            echo json_encode($response);
            exit;
        }

        // Consulta para obtener el campo private_table y phone de la tabla usuarios según el user_id
        $query = "SELECT private_table, phone FROM usuarios WHERE id = ?";

        // Preparar la consulta
        if ($stmt = $conexion->prepare($query)) { 
            $stmt->bind_param("i", $user_id); // Vincular el parámetro user_id
            
            // Ejecutar la consulta
            $stmt->execute();
            
            // Obtener el resultado
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Obtener los valores de private_table y phone
                $row = $result->fetch_assoc();
                $table_user = $row['private_table']; // Almacenar el campo private_table en $table_user
                $user_phone = $row['phone']; // Almacenar el número de teléfono en $user_phone
            } else {
                $response['error'] = "No se encontró el usuario.";
                echo json_encode($response);
                exit;
            }

            // Cerrar la consulta
            $stmt->close();
        } else {
            $response['error'] = "Error al preparar la consulta: " . $conexion->error; // Mostrar el error de preparación
            echo json_encode($response);
            exit;
        }
    } else {
        $response['error'] = "Datos de cookie no válidos.";
        echo json_encode($response);
        exit;
    }
} else {
    $response['error'] = "Cookie de sesión no encontrada.";
    echo json_encode($response);
    exit;
}

// Obtener los datos del formulario
$phone = $_POST['phone'];
$message = $_POST['message'];

// Verificar si el número de teléfono del formulario es el mismo que el del usuario
if ($phone == $user_phone) {
    $response['error'] = "No puedes enviarte un mensaje a ti mismo.";
    echo json_encode($response);
    exit;
}

// Consulta para obtener el campo private_table y el nombre del destinatario basado en el número de teléfono ($phone)
$query_phone = "SELECT private_table, name FROM usuarios WHERE phone = ?";

// Preparar la consulta
if ($stmt = $conexion->prepare($query_phone)) {
    $stmt->bind_param("s", $phone); // Vincular el parámetro phone
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Obtener el resultado
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Obtener el valor de private_table y el nombre
        $row = $result->fetch_assoc();
        $table_send = $row['private_table']; // Almacenar el campo private_table en $table_send
        $name_receive = $row['name']; // Almacenar el nombre del destinatario
    } else {
        $response['error'] = "El usuario no existe.";
        echo json_encode($response);
        exit;
    }

    // Cerrar la consulta
    $stmt->close();
} else {
    $response['error'] = "Error al preparar la consulta para obtener el destinatario: " . $conexion->error; // Mostrar el error de preparación
    echo json_encode($response);
    exit;
}

// Insertar datos (configurar manualmente)
$insert_query1 = "INSERT INTO $table_user (phone_send, phone_receive, name_send, name_receive, message) VALUES (?, ?, ?, ?, ?)";
$insert_query2 = "INSERT INTO $table_send (phone_send, phone_receive, name_send, name_receive, message) VALUES (?, ?, ?, ?, ?)"; // Cambiar nombre de la tabla y campos

$success = true;

// Aquí puedes agregar la lógica para ejecutar las consultas de inserción
if ($stmt = $conexion->prepare($insert_query1)) {
    $stmt->bind_param("sssss", $user_phone, $phone, $user_name, $name_receive, $message); // Ajustar según tipos de datos
    if (!$stmt->execute()) {
        $success = false;
        $response['error'] = "Error al enviar el mensaje: " . $stmt->error; // Mostrar el error de ejecución
    }
    $stmt->close();
} else {
    $success = false;
    $response['error'] = "Error al preparar la consulta de inserción en la tabla del usuario: " . $conexion->error; // Mostrar el error de preparación
}

if ($stmt = $conexion->prepare($insert_query2)) {
    $stmt->bind_param("sssss", $user_phone, $phone, $user_name, $name_receive, $message); // Ajustar según tipos de datos
    if (!$stmt->execute()) {
        $success = false;
        $response['error'] = "Error al enviar el mensaje: " . $stmt->error; // Mostrar el error de ejecución
    }
    $stmt->close();
} else {
    $success = false;
    $response['error'] = "Error al preparar la consulta de inserción en la tabla del destinatario: " . $conexion->error; // Mostrar el error de preparación
}

// Comprobar si la inserción fue exitosa
if ($success) {
    $response['success'] = "Mensaje enviado correctamente.";
} 

// Cerrar la conexión al final del script
if ($conexion) {
    $conexion->close();
}


echo json_encode($response);
?>
