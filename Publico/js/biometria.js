const URL_CONTROLADOR_BIOMETRIA = 'api.php';
const RUTA_MODELOS = 'models';

let streamActivo = null;
let modoActual = null;
let detectandoGesto = false;

document.addEventListener('DOMContentLoaded', async () => {
    const btnLogin = document.getElementById('btnLoginRostro');
    const btnConfigurar = document.getElementById('btnConfigurarBiometria');

    if (btnLogin) {
        btnLogin.addEventListener('click', () => abrirPanel('login'));
    }
    if (btnConfigurar) {
        btnConfigurar.addEventListener('click', () => abrirPanel('configurar'));
    }

    const btnCapturar = document.getElementById('btnCapturarRostro');
    if (btnCapturar) btnCapturar.addEventListener('click', capturarRostro);

    const btnReintentar = document.getElementById('btnReintentarRostro');
    if (btnReintentar) btnReintentar.addEventListener('click', () => {
        detectandoGesto = false;
        mostrarMensaje('', '');
        document.getElementById('btnCapturarRostro').classList.remove('d-none');
        btnReintentar.classList.add('d-none');
        iniciarCamara();
    });

    const btnCancelar = document.getElementById('btnCancelarBiometria');
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarPanel);

    if (typeof faceapi === 'undefined') {
        console.error('face-api.js no se cargó. Verifica el CDN.');
        return;
    }

    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(RUTA_MODELOS),
            faceapi.nets.faceLandmark68Net.loadFromUri(RUTA_MODELOS),
            faceapi.nets.faceRecognitionNet.loadFromUri(RUTA_MODELOS)
        ]);
    } catch (err) {
        console.error('Error al cargar los modelos de biometría:', err);
    }
});

function mostrarMensaje(tipo, texto) {
    const zona = document.getElementById('mensajeBiometria');
    if (!zona) return;
    if (!texto) {
        zona.innerHTML = '';
    } else {
        zona.innerHTML = `<div class="alert alert-${tipo} py-2 px-3 mb-0">${texto}</div>`;
    }
}

function mostrarReintentar() {
    detectandoGesto = false;
    const btnCapturar = document.getElementById('btnCapturarRostro');
    const btnReintentar = document.getElementById('btnReintentarRostro');
    if (btnCapturar) btnCapturar.classList.add('d-none');
    if (btnReintentar) btnReintentar.classList.remove('d-none');
}

async function abrirPanel(modo) {
    modoActual = modo;
    mostrarMensaje('', '');
    document.getElementById('panelBiometria').classList.remove('d-none');
    document.getElementById('btnCapturarRostro').classList.remove('d-none');
    document.getElementById('btnReintentarRostro').classList.add('d-none');
    await iniciarCamara();
}

async function iniciarCamara() {
    detenerCamara();
    try {
        streamActivo = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });
        document.getElementById('videoCamara').srcObject = streamActivo;
    } catch (err) {
        mostrarMensaje('danger', 'No se pudo acceder a la cámara. Verifica los permisos del navegador.');
    }
}

function detenerCamara() {
    detectandoGesto = false;
    if (streamActivo) {
        streamActivo.getTracks().forEach(t => t.stop());
        streamActivo = null;
    }
}

function cerrarPanel() {
    detectandoGesto = false;
    const cerrandoRegistro = modoActual === 'registro';
    detenerCamara();
    const panel = document.getElementById('panelBiometria');
    if (panel) panel.classList.add('d-none');
    mostrarMensaje('', '');
    const btnCapturar = document.getElementById('btnCapturarRostro');
    if (btnCapturar) btnCapturar.classList.remove('d-none');
    const btnReintentar = document.getElementById('btnReintentarRostro');
    if (btnReintentar) btnReintentar.classList.add('d-none');
    modoActual = null;
    if (cerrandoRegistro) {
        document.dispatchEvent(new CustomEvent('registroBiometriaOmitida'));
    }
}

function dist(p1, p2) {
    return Math.hypot(p1.x - p2.x, p1.y - p2.y);
}

function calcularEAR(pts) {
    const dIzq1 = dist(pts[37], pts[41]);
    const dIzq2 = dist(pts[38], pts[40]);
    const dIzqH = dist(pts[36], pts[39]);
    const earIzq = (dIzq1 + dIzq2) / (2.0 * (dIzqH || 1));

    const dDer1 = dist(pts[43], pts[47]);
    const dDer2 = dist(pts[44], pts[46]);
    const dDerH = dist(pts[42], pts[45]);
    const earDer = (dDer1 + dDer2) / (2.0 * (dDerH || 1));

    return (earIzq + earDer) / 2.0;
}

async function capturarRostro() {
    const video = document.getElementById('videoCamara');

    if (typeof faceapi === 'undefined') {
        mostrarMensaje('danger', 'La librería de reconocimiento facial no se cargó. Revisa tu conexión.');
        return;
    }

    if (!video || !video.srcObject) {
        mostrarMensaje('danger', 'La cámara no está activa. Intenta de nuevo.');
        return;
    }

    mostrarMensaje('primary', 'Prueba de vida: <strong>Parpadea una vez mirando a la cámara</strong>');

    const btnCapturar = document.getElementById('btnCapturarRostro');
    if (btnCapturar) btnCapturar.classList.add('d-none');

    detectandoGesto = true;
    const tiempoInicio = Date.now();
    const tiempoLimiteMs = 10000;

    let algunaCaraDetectada = false;
    let avisoIluminacionMostrado = false;
    let framesCalibracion = [];
    let baseEAR = null;
    let descriptorFrontal = null;
    let ojosCerrados = false;
    let framesCerrado = 0;
    let earMinimo = Infinity;

    const detectorOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 224 });

    while (detectandoGesto) {
        if (!video.srcObject || !detectandoGesto) {
            break;
        }

        const ahora = Date.now();

        if (ahora - tiempoInicio > tiempoLimiteMs) {
            detectandoGesto = false;
            console.log(`[Liveness] Tiempo límite alcanzado (10s). EAR base: ${baseEAR ? baseEAR.toFixed(4) : 'N/A'}, EAR mínimo: ${isFinite(earMinimo) ? earMinimo.toFixed(4) : 'N/A'}`);
            mostrarMensaje('warning', 'No se detectó el parpadeo a tiempo. Inténtalo de nuevo.');
            mostrarReintentar();
            break;
        }

        if (!algunaCaraDetectada && (ahora - tiempoInicio > 3000) && !avisoIluminacionMostrado) {
            mostrarMensaje('warning', 'No se detecta tu rostro. Mejora la iluminación y mira a la cámara.');
            avisoIluminacionMostrado = true;
        }

        try {
            // Fase 1: Calibración promediando ~5 frames con ojos abiertos (EAR >= 0.20)
            if (baseEAR === null) {
                const deteccion = await faceapi.detectSingleFace(video, detectorOptions)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!deteccion) {
                    await new Promise(r => requestAnimationFrame(r));
                    continue;
                }

                algunaCaraDetectada = true;
                const pts = deteccion.landmarks.positions;
                const ear = calcularEAR(pts);

                if (ear >= 0.20) {
                    framesCalibracion.push(ear);
                    if (!descriptorFrontal) {
                        descriptorFrontal = Array.from(deteccion.descriptor);
                    }
                }

                if (framesCalibracion.length >= 5) {
                    const suma = framesCalibracion.reduce((acc, v) => acc + v, 0);
                    baseEAR = suma / framesCalibracion.length;
                    console.log(`[Liveness] Calibración de reposo lista. EAR base promedio: ${baseEAR.toFixed(4)}`);
                    mostrarMensaje('primary', 'Prueba de vida: <strong>Parpadea una vez mirando a la cámara</strong>');
                }

                await new Promise(r => requestAnimationFrame(r));
                continue;
            }

            // Fase 2: Detección de parpadeo a máxima velocidad (solo landmarks, sin descriptor)
            const deteccion = await faceapi.detectSingleFace(video, detectorOptions)
                .withFaceLandmarks();

            if (!deteccion) {
                // Si no se detecta cara en un frame, ignorarlo sin reiniciar el proceso
                await new Promise(r => requestAnimationFrame(r));
                continue;
            }

            algunaCaraDetectada = true;
            const pts = deteccion.landmarks.positions;
            const ear = calcularEAR(pts);

            if (ear < earMinimo) {
                earMinimo = ear;
            }

            // Parpadeo válido: caída por debajo del 75% de la base y recuperación por encima del 90% de la base (cierre de 1 a 8 frames)
            if (ear < baseEAR * 0.75) {
                ojosCerrados = true;
                framesCerrado++;
                if (framesCerrado > 8) {
                    // Cierre excesivamente prolongado (no es un parpadeo natural)
                    ojosCerrados = false;
                    framesCerrado = 0;
                }
            } else if (ojosCerrados) {
                if (ear > baseEAR * 0.90) {
                    if (framesCerrado >= 1 && framesCerrado <= 8) {
                        detectandoGesto = false;
                        console.log(`[Liveness] ¡Parpadeo válido! EAR base: ${baseEAR.toFixed(4)}, EAR mínimo: ${earMinimo.toFixed(4)}, frames cerrado: ${framesCerrado}`);
                        mostrarMensaje('success', '¡Parpadeo detectado! Identificando rostro...');
                        enviarVector(descriptorFrontal);
                        break;
                    } else {
                        ojosCerrados = false;
                        framesCerrado = 0;
                    }
                }
            }

        } catch (err) {
            console.error('Error durante la detección de liveness:', err);
        }

        await new Promise(r => requestAnimationFrame(r));
    }
}

function enviarVector(vector) {
    let accion;
    let cuerpo;

    if (modoActual === 'configurar') {
        accion = 'guardarBiometria';
        cuerpo = { vector: vector };
    } else if (modoActual === 'registro') {
        accion = 'guardarBiometriaRegistro';
        cuerpo = {
            vector: vector,
            tbperfilid: parseInt(document.getElementById('biometriaId').value),
            token: document.getElementById('biometriaToken').value
        };
    } else {
        accion = 'loginBiometrico';
        cuerpo = { vector: vector };
    }

    peticion(`${URL_CONTROLADOR_BIOMETRIA}?accion=${accion}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(cuerpo)
    })
        .then(resp => {
            if (!resp.exito) {
                mostrarMensaje('danger', resp.mensaje);
                mostrarReintentar();
                return;
            }

            if (modoActual === 'configurar') {
                mostrarMensaje('success', 'Biometría configurada correctamente.');
                setTimeout(cerrarPanel, 1500);
                return;
            }

            if (modoActual === 'registro') {
                mostrarMensaje('success', resp.mensaje);
                detenerCamara();
                document.dispatchEvent(new CustomEvent('biometriaGuardada', { detail: resp }));
                return;
            }

            detenerCamara();
            sessionStorage.setItem('ubicacionAutoPendiente', '1');

            if (resp.cambiarContra) {
                window.location.href = 'index.php?vista=cambiarContra';
            } else if (resp.rol === 'admin') {
                window.location.href = 'index.php?vista=perfiles';
            } else {
                window.location.href = 'index.php?vista=cliente';
            }
        })
        .catch(err => {
            mostrarMensaje('danger', err.message);
            mostrarReintentar();
        });
}