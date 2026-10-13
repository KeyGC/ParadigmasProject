const URL_CONTROLADOR = 'apideporte.php';
let listaItems = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarTiposSelect();
    cargarItems();

    document.getElementById('formItem').addEventListener('submit', guardarItem);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarItems);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
});

function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';
    document.getElementById('formItem').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarTiposSelect() {
    peticion(`${URL_CONTROLADOR}?accion=getTiposDisponibles`)
        .then(respuesta => {
            if (respuesta.exito) {
                const select = document.getElementById('tipoId');
                respuesta.data.forEach(t => {
                    select.innerHTML += `<option value="${t.tbtipodeporteid}">${t.tbtipodeportenombre}</option>`;
                });
            }
        })
        .catch(err => console.error('Error al cargar tipos:', err));
}

function cargarItems() {
    peticion(`${URL_CONTROLADOR}?accion=getListAdmin`)
        .then(respuesta => {
            if (respuesta.exito) {
                listaItems = respuesta.data;
                pintarTabla(listaItems);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar elementos:', err));
}

function buscarItems() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaItems);
        return;
    }

    const filtradas = listaItems.filter(c =>
        c.nombre.toLowerCase().includes(query) ||
        (c.tipoNombre && c.tipoNombre.toLowerCase().includes(query))
    );

    pintarTabla(filtradas);
}

function pintarTabla(items) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (items.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="4" class="text-center">No se encontraron resultados</td></tr>`;
        return;
    }

    items.forEach(c => {
        const activo = c.activo == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="fw-medium">${c.nombre}</td>
            <td>${c.tipoNombre}</td>
            <td class="estado-celda">
                <span class="badge-estado ${activo ? 'activo' : 'inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
            </td>
            <td>
                <button class="accion-tabla" onclick="editarItem(${c.id})">Editar</button>
                <button class="accion-tabla ${activo ? 'accion-desactivar' : 'accion-activar'}" onclick="toggleEstado(${c.id})">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
                <button class="accion-tabla accion-eliminar" onclick="eliminarItem(${c.id})">Eliminar</button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

function editarItem(id) {
    peticion(`${URL_CONTROLADOR}?accion=getItemAdmin&id=${id}`)
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('itemId').value = respuesta.data.id;
                document.getElementById('tipoId').value = respuesta.data.tbtipodeporteid;
                document.getElementById('nombre').value = respuesta.data.nombre;
                document.getElementById('imagenUrl').value = respuesta.data.imagenUrl || '';
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formItem').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarItem(e) {
    e.preventDefault();

    const id = document.getElementById('itemId').value;
    const tipoId = document.getElementById('tipoId').value;
    const nombre = document.getElementById('nombre').value;
    const imagenUrl = document.getElementById('imagenUrl').value;

    const formData = new FormData();
    formData.append('tipoId', tipoId);
    formData.append('nombre', nombre);
    formData.append('imagenUrl', imagenUrl);

    let accion = 'insert';
    if (id) {
        accion = 'update';
        formData.append('id', id);
    }

    peticion(`${URL_CONTROLADOR}?accion=${accion}`, {
        method: 'POST',
        body: formData
    })
        .then(respuesta => {
            alert(respuesta.mensaje);
            if (respuesta.exito) {
                cancelarEdicion();
                document.getElementById('buscador').value = '';
                cargarItems();
            }
        })
        .catch(err => console.error('Error al guardar:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de este elemento?')) return;

    const formData = new FormData();
    formData.append('id', id);

    peticion(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(respuesta => {
            if (respuesta.exito) {
                cargarItems();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}

function eliminarItem(id) {
    if (!confirm('¿Confirma que desea eliminar este elemento? Esta acción no se puede deshacer.')) return;

    const formData = new FormData();
    formData.append('id', id);

    peticion(`${URL_CONTROLADOR}?accion=delete`, {
        method: 'POST',
        body: formData
    })
        .then(respuesta => {
            alert(respuesta.mensaje);
            if (respuesta.exito) {
                cargarItems();
            }
        })
        .catch(err => console.error('Error al eliminar:', err));
}