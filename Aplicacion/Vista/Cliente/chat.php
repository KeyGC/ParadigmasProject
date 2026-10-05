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
                    <p class="chat-vacio text-secondary mb-0" id="chatListaVacia">
                        Todavía no tienes conversaciones. Envía un mensaje desde tus coincidencias.
                    </p>
                    <ul class="chat-conversaciones list-unstyled mb-0" id="chatConversaciones"></ul>
                </div>

                <div class="chat-ventana d-flex flex-column" id="chatVentana">
                    <div class="chat-cabecera border-bottom">
                        <h2 class="h6 mb-0" id="chatNombre">Selecciona una conversación</h2>
                    </div>

                    <div class="chat-mensajes flex-grow-1" id="chatMensajes">
                        <p class="chat-vacio text-secondary mb-0" id="chatMensajesVacio">
                            Los mensajes aparecerán aquí.
                        </p>
                    </div>

                    <form class="chat-form border-top" id="chatForm">
                        <input type="text" class="form-control" id="chatTexto" maxlength="1000"
                            placeholder="Escribe un mensaje..." autocomplete="off" disabled>
                        <button type="submit" class="btn btn-primary" id="chatEnviar" disabled>Enviar</button>
                    </form>

                    <p class="chat-estado text-secondary small mb-0" id="chatEstado"></p>
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
    <script src="js/chat.js"></script>
</body>

</html>