const URL_CONTROLADOR_BIOMETRIA = 'api.php';
const RUTA_MODELOS = 'models';

// Umbrales de la prueba de vida (grados / proporciones)
const LIVENESS_UMBRAL_INCLINACION = 8;  // inclinación mínima (a cualquier lado)
const LIVENESS_UMBRAL_RETORNO = 7;      // vuelve al centro si |delta| <= este valor
const LIVENESS_TOLERANCIA_OJOS = 0.30;  // cambio máximo permitido de la distancia entre ojos

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

function mediana(arr) {
    const s = [...arr].sort((a, b) => a - b);
    return s[Math.floor(s.length / 2)];
}

// Ángulo roll = atan2 entre los puntos 36 (ojo derecho del usuario) y 45 (ojo izquierdo del usuario).
// Los landmarks se calculan sobre la imagen original de la cámara (sin espejo), así que el signo
// depende del cuerpo del usuario y no de si la vista previa se ve en espejo:
// - Inclinar hacia TU hombro derecho baja el ojo 36 -> deltaRoll negativo.
// - Inclinar hacia TU hombro izquierdo baja el ojo 45 -> deltaRoll positivo.
function calcularAnguloRoll(pts) {
    const p36 = pts[36];
    const p45 = pts[45];
    return Math.atan2(p45.y - p36.y, p45.x - p36.x) * (180 / Math.PI);
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

    mostrarMensaje('primary', 'Mantén el rostro al frente, calibrando...');

    const btnCapturar = document.getElementById('btnCapturarRostro');
    if (btnCapturar) btnCapturar.classList.add('d-none');

    detectandoGesto = true;
    const tiempoInicio = Date.now();
    const tiempoLimiteMs = 20000;

    let algunaCaraDetectada = false;
    let avisoIluminacionMostrado = false;
    const framesCalibracion = [];
    let baseRoll = null;
    let distOjosBase = null;
    let descriptorInicial = null;
    let mejorScore = -1;

    let fase = 'inclinando'; // 'inclinando' -> 'volviendo'
    let maxInclinacion = 0;
    let framesInclinado = 0;

    const opciones = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.35 });

    while (detectandoGesto) {
        if (!video.srcObject || !detectandoGesto) break;

        if (video.readyState < 2 || video.videoWidth === 0) {
            await new Promise(r => requestAnimationFrame(r));
            continue;
        }

        const ahora = Date.now();

        if (ahora - tiempoInicio > tiempoLimiteMs) {
            detectandoGesto = false;
            console.log(`[Liveness] Tiempo agotado | fase ${fase} | inclinación máx: ${maxInclinacion.toFixed(1)}°`);
            mostrarMensaje('warning', 'No se completó la prueba de vida a tiempo. Inténtalo de nuevo.');
            mostrarReintentar();
            break;
        }

        if (!algunaCaraDetectada && (ahora - tiempoInicio > 3000) && !avisoIluminacionMostrado) {
            mostrarMensaje('warning', 'No se detecta tu rostro. Mejora la iluminación y mira a la cámara.');
            avisoIluminacionMostrado = true;
        }

        try {
            // Calibración en reposo: 3 frames, se guarda el mejor descriptor
            if (baseRoll === null) {
                const det = await faceapi.detectSingleFace(video, opciones)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!det) {
                    await new Promise(r => requestAnimationFrame(r));
                    continue;
                }

                algunaCaraDetectada = true;
                const pts = det.landmarks.positions;
                framesCalibracion.push({ roll: calcularAnguloRoll(pts), dOjos: dist(pts[36], pts[45]) });

                const score = (det.detection && det.detection.score) ? det.detection.score : 0;
                if (score > mejorScore || !descriptorInicial) {
                    mejorScore = score;
                    descriptorInicial = Array.from(det.descriptor);
                }

                if (framesCalibracion.length >= 3) {
                    baseRoll = mediana(framesCalibracion.map(f => f.roll));
                    distOjosBase = mediana(framesCalibracion.map(f => f.dOjos));
                    console.log(`[Liveness] Calibración lista. Base roll: ${baseRoll.toFixed(1)}°`);
                    mostrarMensaje('primary', 'Inclina la cabeza hacia un lado y vuelve al centro');
                }
                continue;
            }

            // Gesto: solo detector + landmarks
            const det = await faceapi.detectSingleFace(video, opciones).withFaceLandmarks();

            if (!det) {
                await new Promise(r => requestAnimationFrame(r));
                continue;
            }

            algunaCaraDetectada = true;
            const pts = det.landmarks.positions;

            // Descarta el frame si la cara se acerca o aleja mucho (foto movida hacia/desde la cámara)
            if (Math.abs(dist(pts[36], pts[45]) - distOjosBase) / distOjosBase > LIVENESS_TOLERANCIA_OJOS) {
                continue;
            }

            const inclinacion = Math.abs(calcularAnguloRoll(pts) - baseRoll);
            maxInclinacion = Math.max(maxInclinacion, inclinacion);

            if (fase === 'inclinando') {
                mostrarMensaje('primary',
                    `Inclina la cabeza hacia un lado y vuelve al centro<br><small>Inclinación: ${inclinacion.toFixed(0)}° / ${LIVENESS_UMBRAL_INCLINACION}°</small>`);

                // 2 frames seguidos sobre el umbral para ignorar ruido
                framesInclinado = inclinacion >= LIVENESS_UMBRAL_INCLINACION ? framesInclinado + 1 : 0;
                if (framesInclinado >= 2) {
                    fase = 'volviendo';
                    mostrarMensaje('info', '¡Bien! Ahora vuelve al centro.');
                }
            } else if (inclinacion <= LIVENESS_UMBRAL_RETORNO) {
                mostrarMensaje('info', 'Verificando rostro...');

                let detFinal = null;
                for (let i = 0; i < 5 && !detFinal; i++) {
                    detFinal = await faceapi.detectSingleFace(video, opciones)
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                    if (!detFinal) await new Promise(r => requestAnimationFrame(r));
                }

                if (!detFinal) {
                    detectandoGesto = false;
                    mostrarMensaje('warning', 'No se pudo verificar tu rostro. Inténtalo de nuevo.');
                    mostrarReintentar();
                    break;
                }

                const distDesc = faceapi.euclideanDistance(descriptorInicial, Array.from(detFinal.descriptor));
                console.log(`[Liveness] Inclinación máx: ${maxInclinacion.toFixed(1)}° | Distancia descriptores: ${distDesc.toFixed(4)}`);

                // Misma persona (< 0.5) y no idénticos (> 0.005)
                if (distDesc >= 0.5 || distDesc <= 0.005) {
                    detectandoGesto = false;
                    mostrarMensaje('warning', 'No se pudo verificar tu rostro. Inténtalo de nuevo.');
                    mostrarReintentar();
                    break;
                }

                detectandoGesto = false;
                mostrarMensaje('success', '¡Prueba de vida verificada! Identificando rostro...');
                enviarVector(descriptorInicial);
                break;
            }

        } catch (err) {
            console.error('Error durante la detección de liveness:', err);
        }
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