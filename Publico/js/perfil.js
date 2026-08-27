const URL_CONTROLADOR = 'api.php';
let listaPerfiles = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarPerfiles();

    document.getElementById('formPerfil').addEventListener('submit', guardarPerfil);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarPerfiles);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
});

function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';

    document.getElementById('formPerfil').reset();
    document.getElementById('perfilId').value = '';
    document.getElementById('ubicacionEstado').value = '1';

    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarPerfiles() {
    fetch(`${URL_CONTROLADOR}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaPerfiles = respuesta.data;
                pintarTabla(listaPerfiles);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar perfiles:', err));
}

function buscarPerfiles() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaPerfiles);
        return;
    }

    const filtrados = listaPerfiles.filter(p =>
        p.tbperfilnombre.toLowerCase().includes(query)
    );

    pintarTabla(filtrados);
}

function pintarTabla(perfiles) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (perfiles.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="5" class="text-center">No se encontraron resultados</td></tr>`;
        return;
    }

    perfiles.forEach(p => {
        const activo = p.tbperfilactivo == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="fw-medium">${p.tbperfilnombre}</td>
            <td>${p.tbperfilcontra}</td>
            <td>${p.tbperfilcorreo}</td>
            <td class="estado-celda">
                <span class="badge-estado ${activo ? 'activo' : 'inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
            </td>
            <td>
                <button class="accion-tabla" onclick="editarPerfil(${p.tbperfilid})">Editar</button>
                <button class="accion-tabla ${activo ? 'accion-desactivar' : 'accion-activar'}" onclick="toggleEstado(${p.tbperfilid})">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
                <button class="accion-tabla" onclick="verAccesos(${p.tbperfilid})">Accesos</button>
                <button class="accion-tabla" onclick="verReproducciones(${p.tbperfilid})">Reproducciones</button>
                <button class="accion-tabla" onclick="verUbicaciones(${p.tbperfilid})">Ubicaciones</button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

function editarPerfil(id) {
    fetch(`${URL_CONTROLADOR}?accion=getPerfil&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('perfilId').value = respuesta.data.tbperfilid;
                document.getElementById('nombre').value = respuesta.data.tbperfilnombre;
                document.getElementById('contra').value = respuesta.data.tbperfilcontra;
                document.getElementById('correo').value = respuesta.data.tbperfilcorreo;
                document.getElementById('ubicacionEstado').value = respuesta.data.tbubicacionestado ? '1' : '0';
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formPerfil').reset();
    document.getElementById('perfilId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarPerfil(e) {
    e.preventDefault();

    const id = document.getElementById('perfilId').value;
    const nombre = document.getElementById('nombre').value;
    const contra = document.getElementById('contra').value;
    const correo = document.getElementById('correo').value;
    const ubicacionEstado = document.getElementById('ubicacionEstado').value;

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('contra', contra);
    formData.append('correo', correo);

    let accion = 'insert';
    if (id) {
        accion = 'update';
        formData.append('id', id);
        formData.append('ubicacionEstado', ubicacionEstado);
    }

    fetch(`${URL_CONTROLADOR}?accion=${accion}`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            alert(respuesta.mensaje);
            if (respuesta.exito) {
                cancelarEdicion();
                document.getElementById('buscador').value = '';
                cargarPerfiles();
            }
        })
        .catch(err => console.error('Error al guardar perfil:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de este perfil?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarPerfiles();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}

function verAccesos(id) {
    window.location.href = `index.php?vista=perfilaccesos&id=${id}`;
}

function verReproducciones(id) {
    window.location.href = `index.php?vista=perfilreproducciones&id=${id}`;
}

function verUbicaciones(id) {
    window.location.href = `index.php?vista=perfilubicaciones&id=${id}`;
}