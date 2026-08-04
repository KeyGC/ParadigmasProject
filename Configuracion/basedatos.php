<?php

class Basedatos {
    private static $host = "127.0.0.1";
    private static $dbname = "dbrrsscita";
    private static $usuario = "key";
    private static $password = "2323";
    private static $conexion = null;

    public static function conectar() {
        if (self::$conexion === null) {
            try {
                self::$conexion = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4",
                    self::$usuario,
                    self::$password
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