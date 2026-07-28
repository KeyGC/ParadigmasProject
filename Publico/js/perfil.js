// Público/js/perfil.js

const URL_CONTROLADOR = 'api.php';

document.addEventListener('DOMContentLoaded', () => {
    cargarPerfiles();

    document.getElementById('formPerfil').addEventListener('submit', guardarPerfil);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
});

// GETLIST
function cargarPerfiles() {
    fetch(`${URL_CONTROLADOR}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                pintarTabla(respuesta.data);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar perfiles:', err));
}

function pintarTabla(perfiles) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    perfiles.forEach(p => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${p.id}</td>
            <td>${p.nickname}</td>
            <td>${p.password}</td>
            <td>
                <button onclick="editarPerfil(${p.id})" style="margin: 5px; padding: 5px 10px; color: white; background-color: #2C5F8A; border: none; cursor: pointer;">Editar</button>
                <button onclick="eliminarPerfil(${p.id})" style="margin: 5px; padding: 5px 10px; color: white; background-color: #DC3545; border: none; cursor: pointer;">Eliminar</button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

// GETPERFIL (para editar)
function editarPerfil(id) {
    fetch(`${URL_CONTROLADOR}?accion=getPerfil&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('perfilId').value = respuesta.data.id;
                document.getElementById('nickname').value = respuesta.data.nickname;
                document.getElementById('password').value = respuesta.data.password;
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
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
}

// INSERT o UPDATE (según si hay id)
function guardarPerfil(e) {
    e.preventDefault();

    const id = document.getElementById('perfilId').value;
    const nickname = document.getElementById('nickname').value;
    const password = document.getElementById('password').value;

    const formData = new FormData();
    formData.append('nickname', nickname);
    formData.append('password', password);

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
                cargarPerfiles(); // recarga solo la tabla, no la página
            }
        })
        .catch(err => console.error('Error al guardar perfil:', err));
}

// DELETE
function eliminarPerfil(id) {
    if (!confirm('¿Seguro que deseas eliminar este perfil?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=delete`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            alert(respuesta.mensaje);
            if (respuesta.exito) {
                cargarPerfiles();
            }
        })
        .catch(err => console.error('Error al eliminar perfil:', err));
}