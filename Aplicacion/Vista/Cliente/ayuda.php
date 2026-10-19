<?php
// Vista accesible tanto para usuarios autenticados como visitantes
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Guía y Ayuda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">

            <div class="mb-3">
                <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" style="border-radius: var(--radio-control); font-size: 0.85rem;">
                    &larr; Volver
                </a>
            </div>

            <header class="mb-4 text-center">
                <h1 class="titulo-pagina">Guía de uso de UnaMatch</h1>
                <p class="subtitulo-pagina">Aprende a sacarle el máximo provecho a la plataforma desde tu primer día.</p>
            </header>

            <div class="row g-3 justify-content-center">
                <div class="col-12 col-lg-9">

                    <!-- 1. Registro y contraseña temporal -->
                    <div class="ayuda-tarjeta">
                        <h2>1. Registro y contraseña temporal</h2>
                        <p>Al registrarte, UnaMatch genera una contraseña temporal segura y te la envía a tu correo electrónico.</p>
                        <p class="small mb-0">En tu primer inicio de sesión, el sistema te solicitará obligatoriamente cambiar tu contraseña por una personalizada que cumpla con los requisitos de seguridad.</p>
                    </div>

                    <!-- 2. Inicio de sesión y biometría facial -->
                    <div class="ayuda-tarjeta">
                        <h2>2. Inicio de sesión y biometría facial</h2>
                        <p>Puedes iniciar sesión de dos formas:</p>
                        <ul>
                            <li><strong>Tradicional:</strong> con tu correo y contraseña.</li>
                            <li><strong>Biometría facial:</strong> inicia sesión mirando a la cámara. Cuenta con detección pasiva de vida para proteger tu identidad y evitar fotos o pantallas.</li>
                        </ul>
                        <p class="small mb-0">Para activarla, ve a tu perfil y presiona <em>"Configurar biometría facial"</em>.</p>
                    </div>

                    <!-- 3. Perfil y descubrimiento de gustos -->
                    <div class="ayuda-tarjeta">
                        <h2>3. Perfil y descubrimiento de gustos</h2>
                        <p>Tus afinidades se construyen a partir de tres pilares:</p>
                        <ul>
                            <li><strong>🎵 Música:</strong> Al escuchar canciones en la sección <em>Inicio</em>, el sistema registra el tiempo reproducido y genera tu perfil musical.</li>
                            <li><strong>🍔 Comida:</strong> En la sección <em>Comida</em>, desliza (swipe) a la derecha si te gusta o a la izquierda si no, para calcular tus preferencias gastronómicas.</li>
                            <li><strong>⚽ Deporte:</strong> En la sección <em>Deporte</em>, haz swipe sobre disciplinas y actividades para determinar tus gustos deportivos.</li>
                        </ul>
                        <p class="small mb-0">En tu perfil puedes consultar en un solo lugar el resumen de tus gustos musicales, gastronómicos y deportivos.</p>
                    </div>

                    <!-- 4. Chat 1 a 1 y búsqueda de afinidades -->
                    <div class="ayuda-tarjeta">
                        <h2>4. Chat directo y búsqueda de afinidades</h2>
                        <p>En la pestaña <em>Chat</em> encontrarás el buscador de afinidades por categoría (Comida, Deporte y Música), que se carga automáticamente al entrar.</p>
                        <p class="small mb-0">El algoritmo de recomendación compara tus vectores de gustos con los de otros usuarios y te muestra el porcentaje de afinidad. Con solo presionar <em>"Enviar mensaje"</em>, se abrirá una conversación privada directa.</p>
                    </div>

                    <!-- 5. Afinidades automáticas y Grupos -->
                    <div class="ayuda-tarjeta">
                        <h2>5. Afinidades automáticas y Grupos</h2>
                        <p>Cuando participas en comunidades o chats, el analizador de discurso identifica intereses compartidos.</p>
                        <p class="small mb-0">Al haber coincidencia, recibirás una notificación. Si dos o más miembros aceptan la recomendación, se crea automáticamente un grupo en la sección <em>Grupos</em> para conversar entre todos.</p>
                    </div>

                    <!-- 6. Comunidades temáticas -->
                    <div class="ayuda-tarjeta">
                        <h2>6. Comunidades temáticas</h2>
                        <p>Son salas abiertas de conversación organizadas por temática: Música, Comida y Deporte.</p>
                        <p class="small mb-0">Cualquier usuario puede ingresar, leer mensajes y compartir opiniones. Todos los mensajes pasan por un filtro de moderación automática para mantener un ambiente seguro.</p>
                    </div>

                    <!-- 7. Eventos sociales -->
                    <div class="ayuda-tarjeta">
                        <h2>7. Detección automática de eventos</h2>
                        <p>UnaMatch detecta automáticamente si asistes a un evento mediante tu ubicación geográfica. No necesitas confirmar ni hacer nada: cuando el sistema identifica que estás en la zona de un evento registrado en la plataforma, lo registra de forma silenciosa en segundo plano.</p>
                        <p class="small mb-0">A partir de esa asistencia detectada, el sistema genera automáticamente afinidades con otros usuarios que también estuvieron en el mismo evento, tomando en cuenta el género musical, el tipo de actividad y otras preferencias relacionadas. Es una forma de conectar personas sin que tengan que hacer nada manual.</p>
                    </div>

                    <!-- 8. Notificaciones -->
                    <div class="ayuda-tarjeta">
                        <h2>8. Notificaciones</h2>
                        <p>El ícono de campana en la barra superior te alerta sobre nuevas propuestas de afinidad y mensajes no leídos.</p>
                        <p class="small mb-0">Desde allí puedes aceptar o posponer propuestas de conexión.</p>
                    </div>

                    <!-- 9. Chat en vivo de canciones -->
                    <div class="ayuda-tarjeta" style="margin-bottom: 2rem;">
                        <h2>9. Chat en vivo por canción</h2>
                        <p>Al reproducir cualquier canción en la sección <em>Inicio</em>, se despliega a la derecha del reproductor un chat en vivo exclusivo para esa pista.</p>
                        <p class="small mb-0">Cualquiera que esté escuchando la misma canción al mismo tiempo puede chatear en tiempo real sin necesidad de haber hecho match previo.</p>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

</body>

</html>
