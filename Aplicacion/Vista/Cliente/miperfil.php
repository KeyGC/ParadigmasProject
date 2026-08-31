<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}

$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Perfil de <?= htmlspecialchars($usuarioActivo ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-fluid" id="contenedorMiPerfil">
            <div class="contenedorFormulario" id="tarjetaPerfilResumen">

                <div class="d-flex flex-column align-items-center text-center mb-4">
                    <div class="avatarPerfil mb-3">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($usuarioActivo ?? '?', 0, 1))) ?>
                    </div>
                    <h1 class="mb-1" id="nombrePerfilResumen">Cargando...</h1>
                    <p class="text-secondary mb-1" id="correoPerfilResumen"></p>
                    <p class="text-secondary" id="ubicacionPerfilResumen"></p>
                </div>

                <hr>

                <h2 class="h5 mb-3">Mis gustos musicales</h2>
                <div id="gustosMusicalesLista">
                    <p class="text-secondary">Cargando gustos musicales...</p>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <a href="index.php?vista=perfil" class="btn btn-primary">Editar mi perfil</a>
                </div>

            </div>
        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script src="js/miperfil.js"></script>
</body>

</html>