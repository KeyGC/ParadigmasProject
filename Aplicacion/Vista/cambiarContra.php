<?php
if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">



    <main>
        <div class="container-fluid p-5 text-center">

            <div class="container contenedorFormulario p-5">

                <h1>Cambiar contraseña</h1>

                <div id="alertaContra"></div>

                <form id="formCambiarContra">

                    <input
                        type="password"
                        id="nuevaContra"
                        class="form-control p-2 my-4"
                        placeholder="Nueva contraseña"
                        required>

                    <input
                        type="password"
                        id="confirmarContra"
                        class="form-control p-2 mb-4"
                        placeholder="Confirmar contraseña"
                        required>

                    <p class="text-start">
                        La contraseña debe tener:
                    </p>

                    <ul class="text-start">
                        <li>Entre 8 y 16 caracteres</li>
                        <li>Mínimo 4 números</li>
                        <li>Mínimo 4 letras</li>
                        <li>No repetir letras o números consecutivos</li>
                        <li>No usar vocales</li>
                    </ul>


                    <button
                        type="submit"
                        class="btn btn-primary p-2 my-4">
                        Guardar contraseña
                    </button>

                </form>

            </div>

        </div>
    </main>


    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>


    <script src="js/cambiarContra.js"></script>

</body>

</html>