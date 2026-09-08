<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Iniciar sesión</title>
</head>
<body>

<section class="min-vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                <div class="card login-transparente text-white">
                    <div class="card-body p-4 text-center">

                        <?php if(isset($_GET['error'])){ ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php } ?>

                        <form action="servidor/validar.php" method="POST">

                            <h2 class="fw-bold mb-2 text-uppercase">SIPV</h2>
                            <p class="text-white-50 mb-4">
                                Inicie sesión para continuar
                            </p>
                            <form action="servidor/login/logear.php" method="post">
                            <div class="form-floating mb-3">
                                <input
                                    type="email"
                                    class="form-control"
                                    id="correo"
                                    name="correo"
                                    placeholder="Correo electrónico"
                                    required>

                                <label for="correo">
                                    Correo electrónico
                                </label>
                            </div>

                            <div class="form-floating mb-4">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="Contraseña"
                                    required>

                                <label for="password">
                                    Contraseña
                                </label>
                            </div>

                            <button class="btn btn-outline-light btn-lg w-100" type="submit">
                                Iniciar sesión
                            </button>

                        </form>

                        <hr>

                        <p class="mb-0">
                            ¿No tienes cuenta?
                            <a href="registro.php" class="text-white fw-bold">
                                Regístrate
                            </a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

</body>
</html>