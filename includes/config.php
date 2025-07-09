<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

class Database {
    private $host = 'localhost';
    private $db_name = 'kopiluvium_inventory';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Koneksi database error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
