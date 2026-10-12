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
    <title>UnaMatch - Notificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">
            <header class="mb-4">
                <h1 class="h4 mb-1">Notificaciones</h1>
                <p class="text-secondary mb-0">
                    Personas que hablan de lo mismo que tú. Si aceptan dos o más, se crea un grupo de chat.
                </p>
            </header>

            <p class="text-danger small mb-3" id="notificacionesError" hidden></p>

            <div class="row g-3" id="notificacionesLista"></div>

            <p class="chat-vacio text-secondary mb-0" id="notificacionesVacio" hidden>
                No tienes recomendaciones pendientes por ahora.
            </p>

            <?php include APP_PATH . '/Vista/Cliente/parcialrecomendadas.php'; ?>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.CONFIG_NOTIFICACIONES = {
            apiUrl: 'apiafinidad.php',
            intervalo: 10000
        };
    </script>
</body>

</html>
