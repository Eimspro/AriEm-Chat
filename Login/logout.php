<?php
// Iniciar sesión si no se ha hecho
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eliminar la cookie "loginfull"
if (isset($_COOKIE['loginfull'])) {
    // Establecer la cookie para expirar en el pasado
    setcookie("loginfull", "", time() - 3600, "/");
}

// Destruir la sesión
session_unset(); // Eliminar todas las variables de sesión
session_destroy(); // Destruir la sesión

// Redirigir a la página de inicio de sesión
header("Location: ../index.php");
exit;
?>
