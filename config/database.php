<?php
class Database {
    private $host = "localhost";
    private $db_name = "tienda_juancho_sofi";
    private $username = "root"; // Tu usuario de MySQL
    private $password = "";     // Tu contraseña de MySQL
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Habilitar errores para depuración
        } catch(PDOException $exception) {
            echo "Error de conexión a la base de datos: " . $exception->getMessage();
            // En un entorno de producción, podrías loggear el error en lugar de mostrarlo
            die(); // Terminar la ejecución si no hay conexión
        }
        return $this->conn;
    }
}
?>
