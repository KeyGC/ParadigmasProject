<?php

require_once __DIR__ . '/../Modelo/perfilmodelo.php';
require_once __DIR__ . '/../Modelo/ubicacionmodelo.php';
require_once __DIR__ . '/../Modelo/reproduccionmodelo.php';
require_once __DIR__ . '/../Utilidades/enviarcorreo.php';
require_once __DIR__ . '/../Modelo/perfilaccesomodelo.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new PerfilModelo();
$accion = $_REQUEST['accion'] ?? '';

function generarContraTemporal($nombre)
{
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre));
    $base = substr($base, 0, 4);
    $aleatorio = substr(bin2hex(random_bytes(4)), 0, 6);
    return $base . $aleatorio;
}

function validarContra($contra)
{
    if (strlen($contra) < 8 || strlen($contra) > 16) {
        return "La contraseña debe tener entre 8 y 16 caracteres";
    }
    if (preg_match('/[aeiouAEIOU]/', $contra)) {
        return "La contraseña no puede contener vocales";
    }
    preg_match_all('/[a-zA-Z]/', $contra, $letras);
    if (count($letras[0]) < 4) {
        return "La contraseña debe tener mínimo 4 letras";
    }
    preg_match_all('/[0-9]/', $contra, $numeros);
    if (count($numeros[0]) < 4) {
        return "La contraseña debe tener mínimo 4 números";
    }
    if (preg_match('/(.)\1/', $contra)) {
        return "No puede tener letras o números repetidos consecutivamente";
    }
    return null;
}

switch ($accion) {

    case 'getList':
        $perfiles = $modelo->getList();
        echo json_encode(["exito" => true, "data" => $perfiles]);
        break;

    case 'getPerfil':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $perfil = $modelo->getPerfil($id);
        if ($perfil) {
            echo json_encode(["exito" => true, "data" => $perfil]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
        }
        break;

    case 'registrar':
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $correo === '') {
            echo json_encode(["exito" => false, "mensaje" => "Nombre y correo son obligatorios"]);
            break;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["exito" => false, "mensaje" => "Correo inválido"]);
            break;
        }

        $perfilExistente = $modelo->getPerfilByNombre($nombre);
        if ($perfilExistente) {
            echo json_encode(["exito" => false, "mensaje" => "El nombre ya está en uso"]);
            break;
        }

        
        $ubicacionModelo = new UbicacionModelo();
        $ubicacionId = $ubicacionModelo->insert(null, null, null, null, null);

        if (!$ubicacionId) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear la ubicación"]);
            break;
        }

        $contraTemporal = generarContraTemporal($nombre);
        $perfil = new Perfil(null, $nombre, $contraTemporal, $correo, 0, $ubicacionId, 'cliente', true);

        try {
            $id = $modelo->insert($perfil);

            if ($id) {
                $correoEnviado = enviarContrasenaTemporal($correo, $nombre, $contraTemporal);

                echo json_encode([
                    "exito" => true,
                    "mensaje" => $correoEnviado
                        ? "Registro exitoso. Revisa tu correo para ver tu contraseña temporal."
                        : "Registro exitoso, pero hubo un problema al enviar el correo.",
                    "id" => $id
                ]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al registrar"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al registrar: " . $e->getMessage()]);
        }

        break;

    case 'insert':
        $nombre = trim($_POST['nombre'] ?? '');
        $contra = trim($_POST['contra'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $contra === '' || $correo === '') {
            echo json_encode(["exito" => false, "mensaje" => "Todos los campos son obligatorios"]);
            break;
        }

        $perfilExistente = $modelo->getPerfilByNombre($nombre);
        if ($perfilExistente) {
            echo json_encode(["exito" => false, "mensaje" => "El nombre ya está en uso"]);
            break;
        }

        $ubicacionModelo = new UbicacionModelo();
        $ubicacionId = $ubicacionModelo->insert(null, null, null, null, null);

        if (!$ubicacionId) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear la ubicación"]);
            break;
        }

        $perfil = new Perfil(null, $nombre, $contra, $correo, 0, $ubicacionId, 'cliente', true);

        try {
            $id = $modelo->insert($perfil);

            if ($id) {
                echo json_encode(["exito" => true, "mensaje" => "Perfil creado correctamente", "id" => $id]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al crear el perfil"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear el perfil: " . $e->getMessage()]);
        }

        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $contra = trim($_POST['contra'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if (!$id || $nombre === '' || $contra === '' || $correo === '') {
            echo json_encode(["exito" => false, "mensaje" => "Datos incompletos"]);
            break;
        }

        $perfilActual = $modelo->getPerfil($id);
        if (!$perfilActual) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $perfil = new Perfil(
            $id,
            $nombre,
            $contra,
            $correo,
            $perfilActual['tbperfilcambiocontra'],
            $perfilActual['tbubicacionid'],
            $perfilActual['tbperfilrol'],
            $perfilActual['tbperfilactivo']
        );

        if ($modelo->update($perfil)) {
            echo json_encode(["exito" => true, "mensaje" => "Perfil actualizado correctamente"]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar el perfil"]);
        }

        break;

    case 'login':
        $nombre = trim($_POST['nombre'] ?? '');
        $contra = trim($_POST['contra'] ?? '');

        if ($nombre === '' || $contra === '') {
            echo json_encode(["exito" => false, "mensaje" => "Nombre y contraseña son obligatorios"]);
            break;
        }

        $perfil = $modelo->login($nombre, $contra);

        if ($perfil) {

            if (!$perfil['tbperfilactivo']) {
                echo json_encode(["exito" => false, "mensaje" => "Su cuenta está inactiva. Contacte al administrador."]);
                break;
            }

            $_SESSION['perfil'] = $perfil;

            $accesoModelo = new PerfilAccesoModelo();
            $accesoModelo->registrarAcceso($perfil['tbperfilid']);  

            if ($perfil['tbperfilcambiocontra'] == 0) {
                echo json_encode([
                    "exito" => true,
                    "cambiarContra" => true,
                    "mensaje" => "Debe cambiar su contraseña temporal"
                ]);
            } else {
                echo json_encode([
                    "exito" => true,
                    "cambiarContra" => false,
                    "mensaje" => "Login exitoso"
                ]);
            }
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Nombre o contraseña incorrectos"]);
        }

        break;
    case 'cambiarContra':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $id = $_SESSION['perfil']['tbperfilid'];
        $contra = trim($_POST['contra'] ?? '');

        if ($contra === '') {
            echo json_encode(["exito" => false, "mensaje" => "La contraseña es obligatoria"]);
            break;
        }

        $errorContra = validarContra($contra);
        if ($errorContra) {
            echo json_encode(["exito" => false, "mensaje" => $errorContra]);
            break;
        }

        $perfilActual = $modelo->getPerfil($id);
        if (!$perfilActual) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $perfil = new Perfil(
            $id,
            $perfilActual['tbperfilnombre'],
            $contra,
            $perfilActual['tbperfilcorreo'],
            1,
            $perfilActual['tbubicacionid'],
            $perfilActual['tbperfilrol'],
            $perfilActual['tbperfilactivo']
        );

        if ($modelo->update($perfil)) {
            $_SESSION['perfil'] = $perfil->toArray();
            echo json_encode(["exito" => true, "mensaje" => "Contraseña actualizada correctamente"]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar la contraseña"]);
        }

        break;

    case 'actualizarPerfil':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $id = $_SESSION['perfil']['tbperfilid'];
        $nombre = trim($_POST['nombre'] ?? '');
        $contra = trim($_POST['contra'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $contra === '' || $correo === '') {
            echo json_encode(["exito" => false, "mensaje" => "Todos los campos son obligatorios"]);
            break;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["exito" => false, "mensaje" => "Correo inválido"]);
            break;
        }

        $errorContra = validarContra($contra);
        if ($errorContra) {
            echo json_encode(["exito" => false, "mensaje" => $errorContra]);
            break;
        }

        $perfilActual = $modelo->getPerfil($id);
        if (!$perfilActual) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $perfil = new Perfil(
            $id,
            $nombre,
            $contra,
            $correo,
            $perfilActual['tbperfilcambiocontra'],
            $perfilActual['tbubicacionid'],
            $perfilActual['tbperfilactivo']
        );

        try {
            if ($modelo->update($perfil)) {
                $_SESSION['perfil'] = $perfil->toArray();
                echo json_encode(["exito" => true, "mensaje" => "Perfil actualizado correctamente"]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al actualizar el perfil"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar: " . $e->getMessage()]);
        }

        break;

    case 'getMiPerfil':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $id = $_SESSION['perfil']['tbperfilid'];
        $perfil = $modelo->getPerfil($id);

        if ($perfil) {
            echo json_encode(["exito" => true, "data" => $perfil]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
        }

        break;


        case 'delete':
        // Solo un admin puede eliminar perfiles
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        // Evita que un admin se elimine a sí mismo por accidente y quede sin sesión válida
        if ((int)$id === (int)$_SESSION['perfil']['tbperfilid']) {
            echo json_encode(["exito" => false, "mensaje" => "No puede eliminar su propio perfil"]);
            break;
        }

        $perfilAEliminar = $modelo->getPerfil($id);
        if (!$perfilAEliminar) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $ubicacionId = $perfilAEliminar['tbubicacionid'];

        try {
            $reproduccionModelo = new ReproduccionModelo();
            $ubicacionModelo = new UbicacionModelo();

            // 1. Borra el historial de reproducciones ligado a este perfil (FK)
            $reproduccionModelo->deleteByPerfilId($id);

            // 2. Borra el perfil en sí
            $perfilEliminado = $modelo->delete($id);

            if (!$perfilEliminado) {
                echo json_encode(["exito" => false, "mensaje" => "Error al eliminar el perfil"]);
                break;
            }

            // 3. Borra la ubicación asociada (ya no depende de nada más)
            if ($ubicacionId) {
                $ubicacionModelo->delete($ubicacionId);
            }

            echo json_encode(["exito" => true, "mensaje" => "Perfil eliminado correctamente junto con sus datos asociados"]);

        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al eliminar: " . $e->getMessage()]);
        }

        break;
    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}
