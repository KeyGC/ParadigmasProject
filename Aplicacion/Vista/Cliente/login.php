<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body id="bodyLogin">
    <main>
        <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center p-4" id="contenedorPrincipal">
            <div class="marca-login">UnaMatch</div>
            <div class="slogan-login">El lugar donde las historias comienzan</div>

            <div class="contenedorFormulario w-100">
                <h1>Iniciar sesión</h1>
                <div id="alertaLogin" class="mt-3"></div>
                <form id="loginForm" class="mt-3">
                    <input type="text" id="nombre" class="form-control" placeholder="Sobrenombre" required>
                    <input type="password" id="contra" class="form-control" placeholder="Contraseña" required>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Iniciar Sesión</button>
                    <div>
                        <a href="index.php?vista=registro" class="btn btn-link p-0">¿Aún no tienes cuenta? Regístrate</a>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary" id="btnLoginRostro">Iniciar sesión con rostro</button>
                </div>

                <div id="panelBiometria" class="panel-biometria d-none mt-3">
                    <p class="text-center mb-2 fw-semibold">Posiciona tu rostro frente a la cámara</p>
                    <video id="videoCamara" class="camara-biometria" playsinline autoplay muted></video>
                    <canvas id="canvasBiometria" class="d-none"></canvas>
                    <div id="mensajeBiometria" class="text-center mt-2"></div>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-primary" id="btnCapturarRostro">Capturar</button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnReintentarRostro">Reintentar</button>
                        <button type="button" class="btn btn-outline-danger" id="btnCancelarBiometria">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="js/login.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="js/biometria.js"></script>
</body>

</html>