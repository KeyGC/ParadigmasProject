<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body id="bodyLogin">
    <main>
        <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center p-4" id="contenedorPrincipal">
            <div class="marca-login">UnaMatch</div>
            <div class="slogan-login">Tu próxima gran historia comienza hoy</div>

            <div class="contenedorFormulario w-100">
                <h1>Crear cuenta</h1>
                <div id="alertaRegistro" class="mt-3"></div>
                <form id="registroForm" class="mt-3">
                    <input type="text" id="nombre" class="form-control" placeholder="Sobrenombre" required>
                    <input type="email" id="correo" class="form-control" placeholder="Correo Electrónico" required>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Registrarme</button>
                    <div>
                        <a href="index.php?vista=login" class="btn btn-link p-0">¿Ya tienes cuenta? Inicia sesión</a>
                    </div>
                </form>

                <div id="panelBiometria" class="panel-biometria d-none mt-3">
                    <p class="text-center mb-1 fw-semibold">Registro exitoso. Paso opcional</p>
                    <p class="text-center text-secondary small mb-2">Configura tu rostro para iniciar sesión con la cámara.</p>
                    <video id="videoCamara" class="camara-biometria" playsinline autoplay muted></video>
                    <canvas id="canvasBiometria" class="d-none"></canvas>
                    <div id="mensajeBiometria" class="text-center mt-2"></div>
                    <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                        <button type="button" class="btn btn-primary" id="btnCapturarRostro">Capturar rostro</button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnReintentarRostro">Reintentar</button>
                        <button type="button" class="btn btn-outline-danger" id="btnCancelarBiometria">Quitar cámara</button>
                        <button type="button" class="btn btn-outline-success" id="btnOmitirBiometria">Puedo hacerlo después</button>
                    </div>
                </div>

                <input type="hidden" id="biometriaId">
                <input type="hidden" id="biometriaToken">
            </div>
        </div>
    </main>

    <script src="js/registro.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="js/biometria.js"></script>
</body>

</html>