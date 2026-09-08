<?php

include "../clases/Auth.php";

if (
    isset($_POST['nombre_completo']) &&
    isset($_POST['correo']) &&
    isset($_POST['password']) &&
    isset($_POST['confirmar_password'])
) {

    $nombre = trim($_POST['nombre_completo']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar_password'];

    // Verificar que las contraseñas coincidan
    if ($password !== $confirmar) {
        die("Las contraseñas no coinciden.");
    }

    $auth = new Auth();

    $resultado = $auth->registrar($nombre, $correo, $password);

    if ($resultado === true) {

        header("Location: ../index.php");
        exit();

    } elseif ($resultado === "correo_existente") {

        echo "El correo ya se encuentra registrado.";

    } else {

        echo "Ocurrió un error al registrar el usuario.";

    }

} else {

    echo "No llegaron todos los datos.";

}

?>