const URL_CONTROLADOR = 'apievento.php';
const URL_GENEROS = 'apigenero.php';
let listaEventos = [];
let listaGeneros = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarGeneros();
    cargarEventos();

    document.getElementById('formEvento').addEventListener('submit', guardarEvento);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarEventos);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
    document.getElementById('formUbicacion').addEventListener('submit', guardarUbicacion);
});


function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';
    document.getElementById('formEvento').reset();
    document.getElementById('eventoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarGeneros() {
    fetch(`${URL_GENEROS}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaGeneros = respuesta.data.filter(g => g.tbgeneroestado == 1);
                const select = document.getElementById('genero');
                listaGeneros.forEach(g => {
                    const option = document.createElement('option');
                    option.value = g.tbgeneroid;
                    option.textContent = g.tbgeneronombre;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Error al cargar géneros:', err));
}

function cargarEventos() {
    fetch(`${URL_CONTROLADOR}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaEventos = respuesta.data;
                pintarTabla(listaEventos);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar eventos:', err));
}

function buscarEventos() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaEventos);
        return;
    }

    const filtrados = listaEventos.filter(e =>
        e.tbeventonombre.toLowerCase().includes(query) ||
        e.tbeventoartista.toLowerCase().includes(query) ||
        e.tbeventoubicaciongeneral.toLowerCase().includes(query)
    );

    pintarTabla(filtrados);
}

function pintarTabla(eventos) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (eventos.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="8" class="text-center">No se encontraron resultados</td></tr>`;
        return;
    }

    eventos.forEach(e => {
        const activo = e.tbeventoestado == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="fw-medium">${e.tbeventonombre}</td>
            <td>${e.tbeventoartista}</td>
            <td>${e.tbgeneronombre}</td>
            <td>${e.tbeventoubicaciongeneral}</td>
            <td>${e.tbeventofecha}</td>
            <td>${e.tbeventohora}</td>
            <td class="estado-celda">
                <span class="badge-estado ${activo ? 'activo' : 'inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
            </td>
            <td>
                <button class="accion-tabla" onclick="editarEvento(${e.tbeventoid})">Editar</button>
                <button class="accion-tabla" onclick="verUbicacionesComida(${e.tbeventoid})">Comida</button>
                <button class="accion-tabla ${activo ? 'accion-desactivar' : 'accion-activar'}" onclick="toggleEstado(${e.tbeventoid})">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

let eventoUbicacionesActivoId = null;

function verUbicacionesComida(eventoId) {
    eventoUbicacionesActivoId = eventoId;
    document.getElementById('ubicacionEventoId').value = eventoId;
    document.getElementById('formUbicacion').reset();
    document.getElementById('ubicacionEventoId').value = eventoId;
    cargarUbicaciones(eventoId);

    const modal = new bootstrap.Modal(document.getElementById('modalUbicaciones'));
    modal.show();
}

function cargarUbicaciones(eventoId) {
    fetch(`${URL_CONTROLADOR}?accion=getUbicaciones&eventoId=${eventoId}&categoria=comida`)
        .then(res => res.json())
        .then(respuesta => {
            const lista = document.getElementById('listaUbicaciones');
            lista.innerHTML = '';

            if (!respuesta.exito || respuesta.data.length === 0) {
                lista.innerHTML = '<li class="list-group-item text-muted">Sin puestos de comida registrados todavía.</li>';
                return;
            }

            respuesta.data.forEach(u => {
                const item = document.createElement('li');
                item.className = 'list-group-item';
                item.textContent = u.tbeventoubicacionnombre;
                lista.appendChild(item);
            });
        })
        .catch(err => console.error('Error al cargar ubicaciones:', err));
}

function guardarUbicacion(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append('eventoId', document.getElementById('ubicacionEventoId').value);
    formData.append('categoria', 'comida');
    formData.append('nombre', document.getElementById('ubicacionNombre').value);
    formData.append('latitud', document.getElementById('ubicacionLatitud').value);
    formData.append('longitud', document.getElementById('ubicacionLongitud').value);

    fetch(`${URL_CONTROLADOR}?accion=insertUbicacion`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('ubicacionNombre').value = '';
                document.getElementById('ubicacionLatitud').value = '';
                document.getElementById('ubicacionLongitud').value = '';
                cargarUbicaciones(eventoUbicacionesActivoId);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al guardar ubicación:', err));
}

function editarEvento(id) {
    fetch(`${URL_CONTROLADOR}?accion=getEvento&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                const e = respuesta.data;
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('eventoId').value = e.tbeventoid;
                document.getElementById('nombre').value = e.tbeventonombre;
                document.getElementById('artista').value = e.tbeventoartista;
                document.getElementById('genero').value = e.tbgeneroid;
                document.getElementById('ubicacion').value = e.tbeventoubicaciongeneral;
                document.getElementById('latitud').value = e.tbeventolatitud;
                document.getElementById('longitud').value = e.tbeventolongitud;
                document.getElementById('fecha').value = e.tbeventofecha;
                document.getElementById('hora').value = e.tbeventohora.substring(0, 5);
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formEvento').reset();
    document.getElementById('eventoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarEvento(e) {
    e.preventDefault();

    const id = document.getElementById('eventoId').value;

    const formData = new FormData();
    formData.append('nombre', document.getElementById('nombre').value);
    formData.append('artista', document.getElementById('artista').value);
    formData.append('generoId', document.getElementById('genero').value);
    formData.append('ubicacion', document.getElementById('ubicacion').value);
    formData.append('latitud', document.getElementById('latitud').value);
    formData.append('longitud', document.getElementById('longitud').value);
    formData.append('fecha', document.getElementById('fecha').value);
    formData.append('hora', document.getElementById('hora').value);

    let accion = 'insert';
    if (id) {
        accion = 'update';
        formData.append('id', id);
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
                cargarEventos();
            }
        })
        .catch(err => console.error('Error al guardar evento:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de este evento?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarEventos();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}