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
    <title>UnaMatch - Descubre tu comida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="juego-descubrir" id="juegoComida">

            <div class="juego-barra">
                <span class="juego-categoria">Comida</span>
                <span class="juego-racha" id="rachaJuego">Sin racha</span>
                <span class="juego-progreso" id="progresoJuego"></span>
            </div>

            <p class="text-center text-secondary mb-3">
                Desliza la tarjeta o usa los botones para decir cuánto te gusta cada opción.
            </p>

            <div class="juego-tarjeta-holder">
                <div class="juego-tarjeta d-none" id="tarjetaJuego">
                    <div class="juego-tarjeta-imagen">
                        <img id="tarjetaImagen" alt="" onerror="this.style.display='none'">
                    </div>
                    <div class="juego-tarjeta-info">
                        <h3 id="tarjetaNombre"></h3>
                        <span class="juego-tipo" id="tarjetaTipo"></span>
                    </div>
                </div>
                <div class="juego-vacio d-none" id="juegoVacio">
                    <p class="text-secondary mb-0">No hay opciones de comida disponibles por ahora.</p>
                    <a href="index.php?vista=miperfil" class="btn btn-outline-primary mt-3">Volver a mi perfil</a>
                </div>
                <span class="juego-sentido d-none" id="sentidoJuego"></span>
            </div>

            <div class="juego-botones">
                <button type="button" class="btn btn-juego-dislike" id="btnDislike" aria-label="No me gusta">✕</button>
                <button type="button" class="btn btn-outline-primary" id="btnResultado">Ver mi resultado</button>
                <button type="button" class="btn btn-juego-like" id="btnLike" aria-label="Me gusta">♥</button>
            </div>

            <div id="juegoMensaje" class="juego-mensaje text-center mt-3"></div>

            <div id="resultadoJuego" class="juego-resultado d-none mt-4">
                <h2 class="h4 mb-1">Tu perfil de comida</h2>
                <p class="text-secondary mb-3" id="resultadoSub"></p>
                <ul class="juego-resultado-puestos" id="resultadoPuestos"></ul>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-primary" id="btnCoincidencias">Ver coincidencias</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnJugarDeNuevo">Jugar de nuevo</button>
                </div>
                <div id="listaCoincidencias" class="mt-3 text-start"></div>
            </div>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.CONFIG_JUEGO = {
            apiUrl: 'apicomida.php',
            idCampo: 'comidaId',
            nombreOpcion: 'comida'
        };
    </script>
    <script src="js/swipe.js"></script>
</body>

</html>