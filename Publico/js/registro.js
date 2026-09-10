document.addEventListener('DOMContentLoaded', () => {
    const registroForm = document.getElementById('registroForm');
    const alertaRegistro = document.getElementById('alertaRegistro');
    const panelBiometria = document.getElementById('panelBiometria');
    const btnOmitir = document.getElementById('btnOmitirBiometria');
    const biometriaId = document.getElementById('biometriaId');
    const biometriaToken = document.getElementById('biometriaToken');

    let redirigido = false;

    function redirigirALogin() {
        if (redirigido) return;
        redirigido = true;
        setTimeout(() => {
            window.location.href = 'index.php?vista=login';
        }, 3500);
    }

    function finalizarSinRostro() {
        if (redirigido) return;
        alertaRegistro.innerHTML = `
            <div class="alert alert-success text-dark">
                <p>Registro exitoso. Revisa tu correo para ver tu contraseña temporal.</p>
                <p>Puedes configurar tu rostro después en Mi perfil.</p>
            </div>
        `;
        panelBiometria.classList.add('d-none');
        redirigirALogin();
    }

    function finalizarConRostro() {
        if (redirigido) return;
        alertaRegistro.innerHTML = `
            <div class="alert alert-success text-dark">
                <p>Registro exitoso. Rostro configurado.</p>
                <p>Revisa tu correo para ver tu contraseña temporal.</p>
            </div>
        `;
        panelBiometria.classList.add('d-none');
        redirigirALogin();
    }

    document.addEventListener('registroBiometriaOmitida', finalizarSinRostro);
    document.addEventListener('biometriaGuardada', finalizarConRostro);

    registroForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (redirigido) return;

        const nombre = document.getElementById('nombre').value.trim();
        const correo = document.getElementById('correo').value.trim();

        alertaRegistro.innerHTML = '';

        const datos = new URLSearchParams();
        datos.append('accion', 'registrar');
        datos.append('nombre', nombre);
        datos.append('correo', correo);

        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: datos
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.exito) {

                biometriaId.value = resp.id;
                biometriaToken.value = resp.tokenBiometria || '';

                alertaRegistro.innerHTML = `
                    <div class="alert alert-success text-dark">
                        <p>${resp.mensaje}</p>
                        <p>Paso opcional: configura tu rostro para iniciar sesión con la cámara.</p>
                    </div>
                `;

                panelBiometria.classList.remove('d-none');

                btnOmitir.addEventListener('click', () => {
                    if (typeof cerrarPanel === 'function') {
                        cerrarPanel();
                    }
                }, { once: true });

                if (typeof abrirPanel === 'function') {
                    abrirPanel('registro');
                }

            } else {
                alertaRegistro.innerHTML = `<div class="alert alert-danger">${resp.mensaje}</div>`;
            }
        })
        .catch(err => {
            alertaRegistro.innerHTML = `<div class="alert alert-danger">Error de conexión: ${err}</div>`;
        });
    });
});