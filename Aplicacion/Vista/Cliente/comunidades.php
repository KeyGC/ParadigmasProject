<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$perfilIdActual = (int) $_SESSION['perfil']['tbperfilid'];
$perfilNombreActual = $_SESSION['perfil']['tbperfilnombre'] ?? '';
$comunidadSolicitada = (int) ($_GET['comunidad'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Comunidades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">

            <div id="comunidadCatalogo">
                <header class="mb-4">
                    <h1 class="h4 mb-1">Comunidades</h1>
                    <p class="text-secondary mb-0">
                        Salas de chat por interés. Elige una comunidad para ver y escribir en su sala.
                    </p>
                </header>

                <p class="text-secondary" id="comunidadCargando">Cargando comunidades...</p>

                <p class="text-danger small mb-0" id="comunidadError" hidden></p>

                <section class="comunidad-seccion" id="comunidadSeccionGenero" hidden>
                    <h2 class="h6 text-uppercase comunidad-seccion-titulo">Musica</h2>
                    <div class="comunidad-grid" id="comunidadGridGenero"></div>
                </section>

                <section class="comunidad-seccion" id="comunidadSeccionComida" hidden>
                    <h2 class="h6 text-uppercase comunidad-seccion-titulo">Comida</h2>
                    <div class="comunidad-grid" id="comunidadGridComida"></div>
                </section>

                <section class="comunidad-seccion" id="comunidadSeccionDeporte" hidden>
                    <h2 class="h6 text-uppercase comunidad-seccion-titulo">Deporte</h2>
                    <div class="comunidad-grid" id="comunidadGridDeporte"></div>
                </section>

                <p class="chat-vacio text-secondary mb-0" id="comunidadVacio" hidden>
                    Todavia no hay comunidades disponibles.
                </p>
            </div>

            <div class="chat-panel comunidad-panel d-none" id="comunidadSala">
                <div class="chat-ventana d-flex flex-column">
                    <div class="chat-cabecera border-bottom d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="comunidadVolver">
                            &larr; Comunidades
                        </button>
                        <h2 class="h6 mb-0 text-truncate" id="comunidadNombre">Comunidad</h2>
                    </div>

                    <div class="chat-mensajes flex-grow-1" id="comunidadMensajes">
                        <p class="chat-vacio text-secondary mb-0" id="comunidadMensajesVacio">
                            Los mensajes de la sala apareceran aqui.
                        </p>
                    </div>

                    <form class="chat-form border-top" id="comunidadForm">
                        <input type="text" class="form-control" id="comunidadTexto" maxlength="1000"
                            placeholder="Escribe un mensaje para la comunidad..." autocomplete="off" disabled>
                        <button type="submit" class="btn btn-primary" id="comunidadEnviar" disabled>Enviar</button>
                    </form>

                    <p class="chat-estado text-secondary small mb-0" id="comunidadEstado"></p>
                </div>
            </div>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.CONFIG_COMUNIDAD = {
            apiUrl: 'apicomunidad.php',
            perfilId: <?= $perfilIdActual ?>,
            perfilNombre: <?= json_encode($perfilNombreActual, JSON_UNESCAPED_UNICODE) ?>,
            comunidadInicial: <?= $comunidadSolicitada ?>
        };
    </script>
    <script src="js/comunidades.js"></script>
</body>

</html>