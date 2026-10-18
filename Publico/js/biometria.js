const URL_CONTROLADOR_BIOMETRIA = 'api.php';
const RUTA_MODELOS = 'models';

let streamActivo = null;
let modoActual = null;
let detectandoGesto = false;
let sesionAntispoof = null;

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

function cargarScript(url) {
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = url;
        s.onload = resolve;
        s.onerror = () => reject(new Error('No se pudo cargar ' + url));
        document.head.appendChild(s);
    });
}

async function cargarAntispoof() {
    if (sesionAntispoof) return sesionAntispoof;
    if (typeof ort === 'undefined') {
        await cargarScript('https://cdn.jsdelivr.net/npm/onnxruntime-web@1.20.1/dist/ort.min.js');
    }
    ort.env.wasm.wasmPaths = 'https://cdn.jsdelivr.net/npm/onnxruntime-web@1.20.1/dist/';
    ort.env.wasm.numThreads = 1;
    sesionAntispoof = await ort.InferenceSession.create('models/MiniFASNetV2.onnx', { executionProviders: ['wasm'] });
    return sesionAntispoof;
}

function softmax(a) {
    const m = Math.max(...a);
    const e = a.map(v => Math.exp(v - m));
    const s = e.reduce((x, y) => x + y, 0);
    return e.map(v => v / s);
}

function recorteATensor(video, box) {
    const W = video.videoWidth;
    const H = video.videoHeight;
    const bw = Math.max(1, box.width);
    const bh = Math.max(1, box.height);
    const escala = Math.min((H - 1) / bh, (W - 1) / bw, 2.7);
    const nw = bw * escala;
    const nh = bh * escala;
    const cx = box.x + bw / 2;
    const cy = box.y + bh / 2;

    const x1 = Math.max(0, Math.trunc(cx - nw / 2));
    const y1 = Math.max(0, Math.trunc(cy - nh / 2));
    const x2 = Math.min(W - 1, Math.trunc(cx + nw / 2));
    const y2 = Math.min(H - 1, Math.trunc(cy + nh / 2));

    const canvas = document.createElement('canvas');
    canvas.width = 80;
    canvas.height = 80;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(video, x1, y1, x2 - x1 + 1, y2 - y1 + 1, 0, 0, 80, 80);
    const rgba = ctx.getImageData(0, 0, 80, 80).data;

    const n = 80 * 80;
    const datos = new Float32Array(3 * n);
    for (let i = 0; i < n; i++) {
        datos[i] = rgba[i * 4 + 2];
        datos[n + i] = rgba[i * 4 + 1];
        datos[2 * n + i] = rgba[i * 4];
    }
    return new ort.Tensor('float32', datos, [1, 3, 80, 80]);
}

async function probabilidadReal(video, box) {
    const sesion = await cargarAntispoof();
    const salida = await sesion.run({ [sesion.inputNames[0]]: recorteATensor(video, box) });
    const p = softmax(Array.from(salida[sesion.outputNames[0]].data));
    return p[1];
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

    const btnCapturar = document.getElementById('btnCapturarRostro');
    if (btnCapturar) btnCapturar.classList.add('d-none');

    detectandoGesto = true;
    mostrarMensaje('primary', 'Preparando la verificación...');

    try {
        await cargarAntispoof();
    } catch (err) {
        console.error('Error al cargar el modelo anti-spoofing:', err);
        detectandoGesto = false;
        mostrarMensaje('danger', 'No se pudo cargar la verificación de vida. Revisa tu conexión e inténtalo de nuevo.');
        mostrarReintentar();
        return;
    }

    const tiempoInicio = Date.now();
    const tiempoLimiteMs = 15000;
    const opciones = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.35 });
    const muestras = [];
    let algunaCaraDetectada = false;
    let avisoMostrado = false;

    mostrarMensaje('primary', 'Mira a la cámara y mantente quieto...');

    while (detectandoGesto && muestras.length < 6) {
        if (!video.srcObject) break;

        if (video.readyState < 2 || video.videoWidth === 0) {
            await new Promise(r => requestAnimationFrame(r));
            continue;
        }

        const ahora = Date.now();
        if (ahora - tiempoInicio > tiempoLimiteMs) {
            detectandoGesto = false;
            mostrarMensaje('warning', 'No se pudo completar la verificación a tiempo. Inténtalo de nuevo.');
            mostrarReintentar();
            return;
        }

        if (!algunaCaraDetectada && !avisoMostrado && ahora - tiempoInicio > 4000) {
            mostrarMensaje('warning', 'No se detecta tu rostro. Mejora la iluminación y mira a la cámara.');
            avisoMostrado = true;
        }

        try {
            const det = await faceapi.detectSingleFace(video, opciones)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!det) {
                await new Promise(r => requestAnimationFrame(r));
                continue;
            }

            algunaCaraDetectada = true;
            const box = det.detection.box;

            if (box.width < 100) {
                mostrarMensaje('warning', 'Acércate un poco a la cámara.');
                await new Promise(r => requestAnimationFrame(r));
                continue;
            }

            const pReal = await probabilidadReal(video, box);
            muestras.push({
                pReal,
                score: det.detection.score || 0,
                descriptor: Array.from(det.descriptor)
            });
            mostrarMensaje('primary', `Verificando que seas una persona real... ${muestras.length}/${6}`);

            await new Promise(r => setTimeout(r, 120));
        } catch (err) {
            console.error('Error durante la verificación de vida:', err);
        }
    }

    if (!detectandoGesto || muestras.length === 0) return;
    detectandoGesto = false;

    const promedio = muestras.reduce((a, m) => a + m.pReal, 0) / muestras.length;
    console.log('[Liveness] prob. real por frame:', muestras.map(m => m.pReal.toFixed(3)).join(', '),
        '| promedio:', promedio.toFixed(3), '| umbral:', 0.7);

    if (promedio < 0.7) {
        mostrarMensaje('danger', 'No se pudo confirmar que seas una persona real. Usa buena luz y no muestres fotos ni pantallas.');
        mostrarReintentar();
        return;
    }

    const mejor = muestras.reduce((a, m) => (m.score > a.score ? m : a), muestras[0]);
    mostrarMensaje('success', '¡Verificación de vida correcta! Identificando rostro...');
    enviarVector(mejor.descriptor);
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