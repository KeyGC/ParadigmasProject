<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$perfilIdActual = (int) $_SESSION['perfil']['tbperfilid'];
$perfilNombreActual = $_SESSION['perfil']['tbperfilnombre'] ?? '';
$conversacionSolicitada = (int) ($_GET['con'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">
            <div class="chat-panel">
                <div class="chat-lista" id="chatLista">
                    <h3 class="h6 mb-2 fw-semibold" style="color:var(--color-texto)">Conversaciones</h3>
                    <p class="small mb-2" style="color:var(--color-texto-suave)" id="chatListaVacia">
                        Cargando conversaciones...
                    </p>
                    <ul class="chat-conversaciones list-unstyled mb-0" id="chatConversaciones"></ul>

                    <div class="chat-busqueda-afinidades mt-4 pt-3 border-top" id="chatAfinidadesSeccion">
                        <h3 class="h6 mb-1 fw-semibold" style="color:var(--color-texto)">Descubrir afinidades</h3>
                        <p class="small mb-2" style="color:var(--color-texto-suave)">Conecta con personas afines a tus gustos:</p>
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Categoría de afinidad">
                            <button type="button" class="btn btn-outline-primary active" id="btnAfinidadComida" data-cat="comida">🍔 Comida</button>
                            <button type="button" class="btn btn-outline-primary" id="btnAfinidadDeporte" data-cat="deporte">⚽ Deporte</button>
                            <button type="button" class="btn btn-outline-primary" id="btnAfinidadMusica" data-cat="musica">🎵 Música</button>
                        </div>
                        <div id="chatListaCoincidencias" class="coincidencias-contenedor">
                            <p class="small mb-0" style="color:var(--color-texto-suave)" id="chatCoincidenciasEstado">Cargando afinidades...</p>
                        </div>
                    </div>
                </div>

                <div class="chat-ventana d-flex flex-column" id="chatVentana">
                    <div class="chat-cabecera">
                        <h2 class="h6 mb-0" id="chatNombre" style="color:var(--color-texto)">Selecciona una conversación</h2>
                    </div>

                    <div class="chat-mensajes flex-grow-1" id="chatMensajes">
                        <p class="chat-vacio mb-0" id="chatMensajesVacio">
                            Los mensajes aparecerán aquí.
                        </p>
                    </div>

                    <form class="chat-form" id="chatForm">
                        <input type="text" class="form-control" id="chatTexto" maxlength="1000"
                            placeholder="Escribe un mensaje..." autocomplete="off" disabled>
                        <button type="submit" class="btn btn-primary" id="chatEnviar" disabled>Enviar</button>
                    </form>

                    <p class="chat-estado small mb-0" id="chatEstado"></p>
                </div>
            </div>
        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.CONFIG_CHAT = {
            apiUrl: 'apichat.php',
            perfilId: <?= $perfilIdActual ?>,
            perfilNombre: <?= json_encode($perfilNombreActual, JSON_UNESCAPED_UNICODE) ?>,
            conversacionInicial: <?= $conversacionSolicitada ?>
        };
    </script>
    <script src="js/chat.js?v=<?= filemtime(BASE_PATH . '/Publico/js/chat.js') ?>"></script>
</body>

</html>