const URL_CONTROLADOR = 'api.php';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formCambiarContra').addEventListener('submit', cambiarContra);
});


function validarContra(contra) {

    // Entre 8 y 16 caracteres
    if (contra.length < 8 || contra.length > 16) {
        return "La contraseña debe tener entre 8 y 16 caracteres";
    }

    // No puede contener vocales
    if (/[aeiouAEIOU]/.test(contra)) {
        return "La contraseña no puede contener vocales";
    }

    // Mínimo 4 letras
    const letras = contra.match(/[a-zA-Z]/g);

    if (!letras || letras.length < 4) {
        return "La contraseña debe tener mínimo 4 letras";
    }


    // Mínimo 4 números
    const numeros = contra.match(/[0-9]/g);

    if (!numeros || numeros.length < 4) {
        return "La contraseña debe tener mínimo 4 números";
    }


    // No permite caracteres consecutivos repetidos
    if (/(.)\1/.test(contra)) {
        return "No puede tener letras o números repetidos consecutivamente";
    }


    return null;
}



function cambiarContra(e) {

    e.preventDefault();


    const nuevaContra = document.getElementById('nuevaContra').value.trim();
    const confirmarContra = document.getElementById('confirmarContra').value.trim();


    const alerta = document.getElementById('alertaContra');


    if (nuevaContra !== confirmarContra) {

        alerta.innerHTML = `
            <div class="alert alert-danger">
                Las contraseñas no coinciden
            </div>
        `;

        return;
    }


    const error = validarContra(nuevaContra);


    if (error) {

        alerta.innerHTML = `
            <div class="alert alert-danger">
                ${error}
            </div>
        `;

        return;
    }



    const formData = new FormData();

    formData.append('contra', nuevaContra);



    fetch(`${URL_CONTROLADOR}?accion=cambiarContra`, {

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


                setTimeout(() => {

                    window.location.href = 'index.php?vista=cliente';

                }, 1500);



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