const URL_PERFIL = 'api.php';

document.addEventListener('DOMContentLoaded', () => {

    cargarMiPerfil();

    document.getElementById('btnEditar').addEventListener('click', activarEdicion);
    document.getElementById('btnCancelarPerfil').addEventListener('click', cancelarEdicion);
    document.getElementById('formPerfil').addEventListener('submit', guardarPerfil);

});

// Cargar datos del perfil
function cargarMiPerfil() {

    const perfilId = document.getElementById('perfilId').value;

    fetch(`${URL_PERFIL}?accion=getPerfil&id=${perfilId}`)
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                document.getElementById('perfilId').value = resp.data.tbperfilid;
                document.getElementById('nombre').value = resp.data.tbperfilnombre;
                document.getElementById('contra').value = resp.data.tbperfilcontra;
                document.getElementById('correo').value = resp.data.tbperfilcorreo;

            } else {

                alert(resp.mensaje);

            }

        })
        .catch(err => console.error(err));

}

// Activar edición
function activarEdicion() {

    document.getElementById('nombre').disabled = false;
    document.getElementById('contra').disabled = false;
    document.getElementById('correo').disabled = false;

    document.getElementById('btnEditar').style.display = 'none';
    document.getElementById('btnGuardarPerfil').style.display = 'inline-block';
    document.getElementById('btnCancelarPerfil').style.display = 'inline-block';

}

// Cancelar edición
function cancelarEdicion() {

    cargarMiPerfil();

    document.getElementById('nombre').disabled = true;
    document.getElementById('contra').disabled = true;
    document.getElementById('correo').disabled = true;

    document.getElementById('btnEditar').style.display = 'inline-block';
    document.getElementById('btnGuardarPerfil').style.display = 'none';
    document.getElementById('btnCancelarPerfil').style.display = 'none';

}

// Guardar cambios
function guardarPerfil(e) {

    e.preventDefault();

    const id = document.getElementById('perfilId').value;
    const nombre = document.getElementById('nombre').value.trim();
    const contra = document.getElementById('contra').value.trim();
    const correo = document.getElementById('correo').value.trim();

    if (!nombre || !contra || !correo) {

        alert('Todos los campos son obligatorios');
        return;

    }

    const formData = new FormData();

    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('contra', contra);
    formData.append('correo', correo);

    fetch(`${URL_PERFIL}?accion=update`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(resp => {

            alert(resp.mensaje);

            if (resp.exito) {
                cancelarEdicion();
            }

        })
        .catch(err => console.error(err));

}