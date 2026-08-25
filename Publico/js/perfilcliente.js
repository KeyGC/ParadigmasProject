const URL_PERFIL = 'api.php';

document.addEventListener('DOMContentLoaded', () => {

    cargarMiPerfil();

    document.getElementById('btnEditar').addEventListener('click', activarEdicion);
    document.getElementById('btnCancelarPerfil').addEventListener('click', cancelarEdicion);
    document.getElementById('formPerfil').addEventListener('submit', guardarPerfil);

});


function validarContra(contra) {

    if (contra.length < 8 || contra.length > 16) {
        return "La contraseña debe tener entre 8 y 16 caracteres";
    }

    if (/[aeiouAEIOU]/.test(contra)) {
        return "La contraseña no puede contener vocales";
    }

    const letras = contra.match(/[a-zA-Z]/g);

    if (!letras || letras.length < 4) {
        return "La contraseña debe tener mínimo 4 letras";
    }

    const numeros = contra.match(/[0-9]/g);

    if (!numeros || numeros.length < 4) {
        return "La contraseña debe tener mínimo 4 números";
    }

    if (/(.)\1/.test(contra)) {
        return "No puede tener letras o números repetidos consecutivamente";
    }

    return null;
}

function cargarMiPerfil() {

    const alerta = document.getElementById('alertaPerfil');

    fetch(`${URL_PERFIL}?accion=getMiPerfil`)
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                document.getElementById('nombre').value = resp.data.tbperfilnombre;
                document.getElementById('contra').value = resp.data.tbperfilcontra;
                document.getElementById('correo').value = resp.data.tbperfilcorreo;

            } else {

                alerta.innerHTML = `
                    <div class="alert alert-danger">
                        ${resp.mensaje}
                    </div>
                `;

            }

        })
        .catch(err => {

            alerta.innerHTML = `
                <div class="alert alert-danger">
                    Error de conexión: ${err}
                </div>
            `;

        });

}

function activarEdicion() {

    document.getElementById('nombre').disabled = false;
    document.getElementById('contra').disabled = false;
    document.getElementById('correo').disabled = false;

    document.getElementById('btnEditar').style.display = 'none';
    document.getElementById('btnGuardarPerfil').style.display = 'inline-block';
    document.getElementById('btnCancelarPerfil').style.display = 'inline-block';

}

function cancelarEdicion() {

    document.getElementById('alertaPerfil').innerHTML = '';

    cargarMiPerfil();

    document.getElementById('nombre').disabled = true;
    document.getElementById('contra').disabled = true;
    document.getElementById('correo').disabled = true;

    document.getElementById('btnEditar').style.display = 'inline-block';
    document.getElementById('btnGuardarPerfil').style.display = 'none';
    document.getElementById('btnCancelarPerfil').style.display = 'none';

}

function guardarPerfil(e) {

    e.preventDefault();

    const nombre = document.getElementById('nombre').value.trim();
    const contra = document.getElementById('contra').value.trim();
    const correo = document.getElementById('correo').value.trim();

    const alerta = document.getElementById('alertaPerfil');

    if (!nombre || !contra || !correo) {

        alerta.innerHTML = `
            <div class="alert alert-danger">
                Todos los campos son obligatorios
            </div>
        `;

        return;

    }

    const errorContra = validarContra(contra);

    if (errorContra) {

        alerta.innerHTML = `
            <div class="alert alert-danger">
                ${errorContra}
            </div>
        `;

        return;

    }

    const formData = new FormData();

    formData.append('nombre', nombre);
    formData.append('contra', contra);
    formData.append('correo', correo);

    fetch(`${URL_PERFIL}?accion=actualizarPerfil`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                alerta.innerHTML = `
                    <div class="alert alert-success">
                        ${resp.mensaje}
                    </div>
                `;

                cancelarEdicion();

            } else {

                alerta.innerHTML = `
                    <div class="alert alert-danger">
                        ${resp.mensaje}
                    </div>
                `;

            }

        })
        .catch(err => {

            alerta.innerHTML = `
                <div class="alert alert-danger">
                    Error de conexión: ${err}
                </div>
            `;

        });

}