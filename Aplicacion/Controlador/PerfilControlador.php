<?php
// Aplicacion/Controlador/PerfilControlador.php
require_once __DIR__ . '/../Modelo/PerfilModelo.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new PerfilModelo();
$accion = $_REQUEST['accion'] ?? '';

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

    case 'insert':
        $nombre = trim($_POST['nombre'] ?? '');
        $contra = trim($_POST['contra'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if ($nombre === '' || $contra === '' || $correo === '') {
            echo json_encode(["exito" => false, "mensaje" => "Todos los campos son obligatorios"]);
            break;
        }

        $perfil = new Perfil(null, $nombre, $contra, $correo);
        try{
        $id = $modelo->insert($perfil);

        if ($id) {
            echo json_encode(["exito" => true, "mensaje" => "Perfil creado correctamente", "id" => $id]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear el perfil"]);
        }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Código de error para violación de restricción de clave única
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