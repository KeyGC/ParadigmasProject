const URL_CONTROLADOR_BIOMETRIA = 'api.php';
const RUTA_MODELOS = 'models';

let streamActivo = null;
let modoActual = null;

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
    if (streamActivo) {
        streamActivo.getTracks().forEach(t => t.stop());
        streamActivo = null;
    }
}

function cerrarPanel() {
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

async function capturarRostro() {
    const video = document.getElementById('videoCamara');

    if (typeof faceapi === 'undefined') {
        mostrarMensaje('danger', 'La librería de reconocimiento facial no se cargó. Revisa tu conexión.');
        return;
    }

    if (!video.srcObject) {
        mostrarMensaje('danger', 'La cámara no está activa. Intenta de nuevo.');
        return;
    }

    mostrarMensaje('info', 'Procesando rostro...');

    try {
        const deteccion = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!deteccion) {
            mostrarMensaje('warning', 'No se detectó ningún rostro. Inténtalo de nuevo con buena iluminación.');
            mostrarReintentar();
            return;
        }

        const vector = Array.from(deteccion.descriptor);
        enviarVector(vector);
    } catch (err) {
        console.error('Error al procesar el rostro:', err);
        mostrarMensaje('danger', 'Hubo un problema al procesar el rostro. Intenta de nuevo.');
        mostrarReintentar();
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

    fetch(`${URL_CONTROLADOR_BIOMETRIA}?accion=${accion}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(cuerpo)
    })
        .then(res => res.json())
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
            mostrarMensaje('danger', 'Error de conexión: ' + err);
            mostrarReintentar();
        });
}