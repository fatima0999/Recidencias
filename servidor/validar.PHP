<?php

session_start();

include "../clases/Conexion.php";


if(isset($_POST['correo']) && isset($_POST['password'])){


    $correo = $_POST['correo'];
    $password = $_POST['password'];


    $conexion = new Conexion();

    $bd = $conexion->conectar();


    $sql = "SELECT *
            FROM tb_usuarios
            WHERE correo = $1";


    $resultado = pg_query_params(
        $bd,
        $sql,
        [$correo]
    );


    if(pg_num_rows($resultado) > 0){


        $usuario = pg_fetch_assoc($resultado);


        if(password_verify($password, $usuario['contrasena_hash'])){


            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['rol'] = $usuario['rol'];


            header("Location: ../inicio.php");
            exit();


        }else{

            header("Location: ../index.php?error=Contraseña incorrecta");
            exit();

        }


    }else{

        header("Location: ../index.php?error=Correo no encontrado");
        exit();

    }


}else{

    header("Location: ../index.php?error=No llegaron datos");
    exit();

}

?>