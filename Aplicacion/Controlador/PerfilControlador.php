<?php
// Aplicacion/Controlador/PerfilControlador.php
require_once __DIR__ . '/../Modelo/PerfilModelo.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new PerfilModelo();
$accion = $_REQUEST['accion'] ?? '';

// genera una contraseña temporal aleatoria,para q no caiga null 
function generarContraTemporal($nombre) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre));
    $base = substr($base, 0, 4);
    $aleatorio = substr(bin2hex(random_bytes(4)), 0, 6);
    return $base . $aleatorio;
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

    // registrar
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

        // la contraseña se genera en el backend
        $contraTemporal = generarContraTemporal($nombre);

        $perfil = new Perfil(null, $nombre, $contraTemporal, $correo);

        try {
            $id = $modelo->insert($perfil);

            if ($id) {
                echo json_encode([
                    "exito" => true,
                    "mensaje" => "Registro exitoso",
                    "id" => $id,
                    "contraTemporal" => $contraTemporal
                ]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al registrar"]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(["exito" => false, "mensaje" => "El nombre ya está en uso"]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al registrar: " . $e->getMessage()]);
            }
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

        $perfil = new Perfil(null, $nombre, $contra, $correo);
        try {
            $id = $modelo->insert($perfil);

            if ($id) {
                echo json_encode(["exito" => true, "mensaje" => "Perfil creado correctamente", "id" => $id]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al crear el perfil"]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // codigo de error de clave única
                echo json_encode(["exito" => false, "mensaje" => "El nombre ya está en uso"]);
            } else {
                echo json_encode(["exito" => false, "mensaje" => "Error al crear el perfil: " . $e->getMessage()]);
            }
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

        $perfil = new Perfil($id, $nombre, $contra, $correo);
        if ($modelo->update($perfil)) {
            echo json_encode(["exito" => true, "mensaje" => "Perfil actualizado correctamente"]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar el perfil"]);
        }
        break;

    case 'delete':
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        if ($modelo->delete($id)) {
            echo json_encode(["exito" => true, "mensaje" => "Perfil eliminado correctamente"]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Error al eliminar el perfil"]);
        }
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}