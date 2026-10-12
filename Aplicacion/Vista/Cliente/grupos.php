<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$perfilIdActual = (int) $_SESSION['perfil']['tbperfilid'];
$perfilNombreActual = $_SESSION['perfil']['tbperfilnombre'] ?? '';
$grupoSolicitado = (int) ($_GET['grupo'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Grupos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">

            <div id="grupoCatalogo">
                <header class="mb-4">
                    <h1 class="h4 mb-1">Grupos</h1>
                    <p class="text-secondary mb-0">
                        Grupos creados a partir de afinidades aceptadas. Elige uno para conversar.
                    </p>
                </header>

                <p class="text-secondary" id="grupoCargando">Cargando grupos...</p>

                <p class="text-danger small mb-0" id="grupoError" hidden></p>

                <div class="list-group" id="grupoLista"></div>

                <p class="chat-vacio text-secondary mb-0" id="grupoVacio" hidden>
                    Todavía no perteneces a ningún grupo.
                </p>
            </div>

            <div class="chat-panel comunidad-panel d-none" id="grupoSala">
                <div class="chat-ventana d-flex flex-column">
                    <div class="chat-cabecera border-bottom d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="grupoVolver">
                            &larr; Grupos
                        </button>
                        <h2 class="h6 mb-0 text-truncate" id="grupoNombre">Grupo</h2>
                        <span class="text-secondary small ms-auto" id="grupoMiembros"></span>
                    </div>

                    <div class="chat-mensajes flex-grow-1" id="grupoMensajes">
                        <p class="chat-vacio text-secondary mb-0" id="grupoMensajesVacio">
                            Los mensajes del grupo aparecerán aquí.
                        </p>
                    </div>

                    <form class="chat-form border-top" id="grupoForm">
                        <input type="text" class="form-control" id="grupoTexto" maxlength="1000"
                            placeholder="Escribe un mensaje para el grupo..." autocomplete="off" disabled>
                        <button type="submit" class="btn btn-primary" id="grupoEnviar" disabled>Enviar</button>
                    </form>

                    <p class="chat-estado text-secondary small mb-0" id="grupoEstado"></p>
                </div>
            </div>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.CONFIG_GRUPO = {
            apiUrl: 'apigrupo.php',
            perfilId: <?= $perfilIdActual ?>,
            perfilNombre: <?= json_encode($perfilNombreActual, JSON_UNESCAPED_UNICODE) ?>,
            grupoInicial: <?= $grupoSolicitado ?>
        };
    </script>
    <script src="js/grupos.js"></script>
</body>

</html>
