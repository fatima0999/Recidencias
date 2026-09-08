<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/registo.css">

    <title>Registro</title>
</head>

<body>

<div class="container">
    <div class="row">
        <div class="col-lg-10 col-xl-9 mx-auto">

            <div class="card flex-row my-5 border-0 shadow rounded-3 overflow-hidden">

                <div class="card-img-left d-none d-md-flex"></div>

                <div class="card-body p-4 p-sm-5">

                    <h5 class="card-title text-center mb-5 fw-light fs-5">
                        Crear cuenta
                    </h5>

                    <form action="servidor/registrar.php" method="POST">

                        <!-- Nombre completo -->
                        <div class="form-floating mb-3">
                            <input
                                type="text"
                                class="form-control"
                                id="nombre_completo"
                                name="nombre_completo"
                                placeholder="Nombre completo"
                                required
                            >

                            <label for="nombre_completo">
                                Nombre completo
                            </label>
                        </div>

                        <!-- Correo -->
                        <div class="form-floating mb-3">
                            <input
                                type="email"
                                class="form-control"
                                id="correo"
                                name="correo"
                                placeholder="Correo electrónico"
                                required
                            >

                            <label for="correo">
                                Correo electrónico
                            </label>
                        </div>

                        <!-- Contraseña -->
                        <div class="form-floating mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Contraseña"
                                minlength="8"
                                required
                            >

                            <label for="password">
                                Contraseña
                            </label>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="form-floating mb-4">
                            <input
                                type="password"
                                class="form-control"
                                id="confirmar_password"
                                name="confirmar_password"
                                placeholder="Confirmar contraseña"
                                minlength="8"
                                required
                            >

                            <label for="confirmar_password">
                                Confirmar contraseña
                            </label>
                        </div>

                        <div class="d-grid mb-3">
                            <button
                                class="btn btn-lg btn-primary btn-login fw-bold text-uppercase"
                                type="submit">
                                Registrarse
                            </button>
                        </div>

                        <a class="d-block text-center mt-2 small" href="index.php">
                            ¿Ya tienes una cuenta? Inicia sesión
                        </a>

                    </form>

                </div>

            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>