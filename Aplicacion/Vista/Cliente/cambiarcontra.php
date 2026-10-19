<?php
if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Cambiar contraseña</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body id="bodyLogin">
    <main>
        <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center p-4" id="contenedorPrincipal">
            <div class="marca-login">UnaMatch</div>
            <div class="slogan-login">Crea tu contraseña segura</div>

            <div class="contenedorFormulario w-100">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h1 class="mb-0">Cambiar contraseña</h1>
                    <a href="index.php?vista=ayudapublica" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" style="font-size: 0.8rem; border-radius: var(--radio-control);" title="Guía y Ayuda">
                        <span aria-hidden="true">&#10067;</span> Ayuda
                    </a>
                </div>

                <div id="alertaContra" class="mt-2"></div>

                <form id="formCambiarContra" class="mt-3 d-flex flex-column gap-3">

                    <input
                        type="password"
                        id="nuevaContra"
                        class="form-control"
                        placeholder="Nueva contraseña"
                        required>

                    <input
                        type="password"
                        id="confirmarContra"
                        class="form-control"
                        placeholder="Confirmar contraseña"
                        required>

                    <div class="reglas-contra text-start">
                        <p>La contraseña debe cumplir con:</p>
                        <ul>
                            <li>Entre 8 y 16 caracteres</li>
                            <li>Mínimo 4 números</li>
                            <li>Mínimo 4 letras</li>
                            <li>No repetir letras o números consecutivos</li>
                            <li>No usar vocales</li>
                        </ul>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100">
                        Guardar contraseña
                    </button>

                </form>

            </div>
        </div>
    </main>

    <script src="js/peticiones.js"></script>
    <script src="js/cambiarcontra.js"></script>

</body>

</html>