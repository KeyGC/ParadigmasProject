document.addEventListener('DOMContentLoaded', () => {
    const registroForm = document.getElementById('registroForm');
    const alertaRegistro = document.getElementById('alertaRegistro');

    registroForm.addEventListener('submit', function (e) {
        e.preventDefault();

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
                // mostrar la contraseña temporal generada para que el usuario la anote
                alertaRegistro.innerHTML = `
                    <div class="alert alert-success text-dark">
                        <p>${resp.mensaje}</p>
                        <p>Tu contraseña temporal es: <strong>${resp.contraTemporal}</strong></p>
                        <p>Guárdala, la necesitarás para iniciar sesión.</p>
                    </div>
                `;
                registroForm.reset();
                setTimeout(() => {
                    window.location.href = 'index.php?vista=login';
                }, 5000);
            } else {
                alertaRegistro.innerHTML = `<div class="alert alert-danger">${resp.mensaje}</div>`;
            }
        })
        .catch(err => {
            alertaRegistro.innerHTML = `<div class="alert alert-danger">Error de conexión: ${err}</div>`;
        });
    });
});