const URL_CONTROLADOR = 'apicancion.php';
let listaCanciones = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarGenerosSelect();
    cargarCanciones();

    document.getElementById('formCancion').addEventListener('submit', guardarCancion);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarCanciones);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
});

function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';
    document.getElementById('formCancion').reset();
    document.getElementById('cancionId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarGenerosSelect() {
    fetch(`${URL_CONTROLADOR}?accion=getGenerosDisponibles`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                const select = document.getElementById('generoId');
                respuesta.data.forEach(g => {
                    select.innerHTML += `<option value="${g.tbgeneroid}">${g.tbgeneronombre}</option>`;
                });
            }
        });
}

function cargarCanciones() {
    fetch(`${URL_CONTROLADOR}?accion=getListAdmin`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaCanciones = respuesta.data;
                pintarTabla(listaCanciones);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar canciones:', err));
}

function buscarCanciones() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaCanciones);
        return;
    }

    const filtradas = listaCanciones.filter(c =>
        c.tbcancionnombre.toLowerCase().includes(query) ||
        c.tbcancionartista.toLowerCase().includes(query)
    );

    pintarTabla(filtradas);
}

function pintarTabla(canciones) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (canciones.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="6" style="text-align:center;">No se encontraron resultados</td></tr>`;
        return;
    }

    canciones.forEach(c => {
        const activo = c.tbcancionactivo == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${c.tbcancionnombre}</td>
            <td>${c.tbcancionartista}</td>
            <td>${c.tbgeneronombre}</td>
            <td>${activo ? 'Activa' : 'Inactiva'}</td>
            <td>
                <button onclick="editarCancion(${c.tbcancionid})" style="margin: 5px; padding: 5px 10px; color: white; background-color: #2C5F8A; border: none; cursor: pointer;">Editar</button>
                <button onclick="toggleEstado(${c.tbcancionid})" style="margin: 5px; padding: 5px 10px; color: white; background-color: ${activo ? '#DC3545' : '#28A745'}; border: none; cursor: pointer;">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

function editarCancion(id) {
    fetch(`${URL_CONTROLADOR}?accion=getCancion&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('cancionId').value = respuesta.data.tbcancionid;
                document.getElementById('generoId').value = respuesta.data.tbgeneroid;
                document.getElementById('nombre').value = respuesta.data.tbcancionnombre;
                document.getElementById('artista').value = respuesta.data.tbcancionartista;
                document.getElementById('url').value = respuesta.data.tbcancionurl;
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formCancion').reset();
    document.getElementById('cancionId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarCancion(e) {
    e.preventDefault();

    const id = document.getElementById('cancionId').value;
    const generoId = document.getElementById('generoId').value;
    const nombre = document.getElementById('nombre').value;
    const artista = document.getElementById('artista').value;
    const url = document.getElementById('url').value;

    const formData = new FormData();
    formData.append('generoId', generoId);
    formData.append('nombre', nombre);
    formData.append('artista', artista);
    formData.append('url', url);

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
                cargarCanciones();
            }
        })
        .catch(err => console.error('Error al guardar canción:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de esta canción?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarCanciones();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}