<?php

class Basedatos {
private static $conexion = null;

public static function conectar() {
if (self::$conexion === null) {
    try {
        $host = getenv('DB_HOST') ?: 'mysqlmatchlove-matchloveweb.k.aivencloud.com';
        $port = (int) (getenv('DB_PORT') ?: 12589);
        $dbname = getenv('DB_NAME') ?: 'dbrrsscita';
        $usuario = getenv('DB_USER') ?: 'avnadmin';
        $password = getenv('DB_PASS') ?: 'CAMBIAR_EN_ENV';

        self::$conexion = new PDO(
            "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $dbname . ";charset=utf8mb4",
            $usuario,
            $password,
            [
                // Aiven exige TLS; sin certificado CA local se acepta la conexión sin verificar el servidor.
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
            ]
        );

        // Configurar PDO
        self::$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        

    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

return self::$conexion;
}
}