<?php

class Conexion
{
    public $servidor = "localhost";
    public $usuario = "postgres";
    public $password = "fatima0904";
    public $database = "Base_prueba";
    public $port = "5432";

    public function conectar()
    {
        $conexion = pg_connect(
            "host=$this->servidor " .
            "port=$this->port " .
            "dbname=$this->database " .
            "user=$this->usuario " .
            "password=$this->password"
        );

        if (!$conexion) {
            die("Error: no se pudo conectar a PostgreSQL.");
        }

        return $conexion;
    }
}