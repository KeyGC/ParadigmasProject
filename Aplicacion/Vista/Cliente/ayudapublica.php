<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Guía y Ayuda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        #bodyAyudaPublica {
            background-image: url('../imagenes/Fondo1.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        #bodyAyudaPublica::before {
            content: "";
            position: fixed;
            inset: 0;
            background: linear-gradient(160deg, rgba(94, 30, 54, 0.55), rgba(23, 30, 43, 0.65));
            z-index: 0;
        }

        #bodyAyudaPublica main {
            position: relative;
            z-index: 1;
        }

        .ayuda-publica-card {
            background: rgba(255, 255, 255, 0.93);
            border-radius: var(--radio-tarjeta, 1rem);
            padding: 1.5rem 1.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
        }

        .ayuda-publica-card h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-primario, #5e1e36);
            margin-bottom: 0.5rem;
        }

        .ayuda-publica-card p,
        .ayuda-publica-card ul {
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
        }

        .ayuda-publica-card ul {
            padding-left: 1.2rem;
        }

        .ayuda-publica-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .ayuda-publica-header .marca {
            font-family: var(--fuente-titulo, 'Georgia', serif);
            font-size: 2rem;
            color: #fff;
            font-weight: bold;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }

        .ayuda-publica-header .subtitulo {
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .btn-volver-publico {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.4);
            color: #fff;
            border-radius: var(--radio-control, 0.5rem);
            font-size: 0.85rem;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .btn-volver-publico:hover {
            background: rgba(255,255,255,0.28);
            color: #fff;
            border-color: rgba(255,255,255,0.6);
        }
    </style>
</head>

<body id="bodyAyudaPublica">
    <main class="py-4 px-3">
        <div class="container" style="max-width: 680px;">

            <!-- Botón volver -->
            <div class="mb-3">
                <a href="javascript:history.back()" class="btn btn-volver-publico d-inline-flex align-items-center gap-1">
                    &larr; Volver
                </a>
            </div>

            <!-- Cabecera -->
            <div class="ayuda-publica-header">
                <div class="marca">UnaMatch</div>
                <div class="subtitulo">Guía de uso</div>
            </div>

            <!-- 1. Registro y contraseña temporal -->
            <div class="ayuda-publica-card">
                <h2>1. Registro y contraseña temporal</h2>
                <p>Al registrarte, UnaMatch genera una contraseña temporal segura y te la envía a tu correo electrónico.</p>
                <p class="mb-0 small text-secondary">En tu primer inicio de sesión, el sistema te solicitará obligatoriamente cambiar tu contraseña por una personalizada que cumpla con los requisitos de seguridad.</p>
            </div>

            <!-- 2. Inicio de sesión y biometría facial -->
            <div class="ayuda-publica-card">
                <h2>2. Inicio de sesión y biometría facial</h2>
                <p>Puedes iniciar sesión de dos formas:</p>
                <ul>
                    <li><strong>Tradicional:</strong> con tu sobrenombre y contraseña.</li>
                    <li><strong>Biometría facial:</strong> inicia sesión mirando a la cámara. Cuenta con detección pasiva de vida para proteger tu identidad y evitar fotos o pantallas.</li>
                </ul>
                <p class="mb-0 small text-secondary">Para activarla, ve a tu perfil y presiona <em>"Configurar biometría facial"</em>.</p>
            </div>

            <!-- 3. Contraseña: requisitos -->
            <div class="ayuda-publica-card">
                <h2>3. Requisitos de contraseña</h2>
                <p>Tu contraseña debe cumplir con:</p>
                <ul>
                    <li>Entre 8 y 16 caracteres</li>
                    <li>Mínimo 4 números</li>
                    <li>Mínimo 4 letras</li>
                    <li>No repetir letras o números consecutivos</li>
                    <li>No usar vocales</li>
                </ul>
                <p class="mb-0 small text-secondary">Si olvidaste tu contraseña, contacta al administrador para que te genere una nueva temporal.</p>
            </div>

            <!-- 4. Perfil y descubrimiento de gustos -->
            <div class="ayuda-publica-card">
                <h2>4. Perfil y descubrimiento de gustos</h2>
                <p>Tus afinidades se construyen a partir de tres pilares:</p>
                <ul>
                    <li><strong>🎵 Música:</strong> Al escuchar canciones en la sección <em>Inicio</em>, el sistema registra el tiempo reproducido y genera tu perfil musical.</li>
                    <li><strong>🍔 Comida:</strong> En la sección <em>Comida</em>, desliza (swipe) a la derecha si te gusta o a la izquierda si no, para calcular tus preferencias gastronómicas. Además, compartir un link de recetas de Cookpad o Kiwilimón en cualquier chat la agrega automáticamente al catálogo.</li>
                    <li><strong>⚽ Deporte:</strong> En la sección <em>Deporte</em>, haz swipe sobre disciplinas y actividades para determinar tus gustos deportivos.</li>
                </ul>
                <p class="mb-0 small text-secondary">En tu perfil puedes consultar el resumen de tus gustos musicales, gastronómicos y deportivos.</p>
            </div>

            <!-- 5. Chat y afinidades -->
            <div class="ayuda-publica-card">
                <h2>5. Chat directo y búsqueda de afinidades</h2>
                <p>En la pestaña <em>Chat</em> encontrarás el buscador de afinidades por categoría (Comida, Deporte y Música).</p>
                <p class="mb-0 small text-secondary">El algoritmo compara tus vectores de gustos con los de otros usuarios y te muestra el porcentaje de afinidad. Con solo presionar <em>"Enviar mensaje"</em>, se abrirá una conversación privada.</p>
            </div>

            <!-- 6. Comunidades, Grupos y Notificaciones -->
            <div class="ayuda-publica-card" style="margin-bottom: 2rem;">
                <h2>6. Comunidades, Grupos y Notificaciones</h2>
                <ul>
                    <li><strong>Comunidades:</strong> salas abiertas organizadas por temática (Música, Comida y Deporte) con moderación automática.</li>
                    <li><strong>Grupos:</strong> se crean automáticamente cuando el analizador de discurso detecta intereses compartidos entre dos o más usuarios.</li>
                    <li><strong>Notificaciones:</strong> el ícono de campana te alerta sobre nuevas propuestas de afinidad y mensajes no leídos.</li>
                </ul>
            </div>

        </div>
    </main>
</body>

</html>
