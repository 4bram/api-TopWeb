<?php

class Tarea
{
    private $conn;
    private $table = "tareas";

    public $id;
    public $titulo;
    public $completada;
    public $fecha_creacion;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT id, titulo, completada, fecha_creacion FROM " . $this->table . " ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne()
    {
        $query = "SELECT id, titulo, completada, fecha_creacion FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->titulo = $row['titulo'];
        $this->completada = (bool)$row['completada'];
        $this->fecha_creacion = $row['fecha_creacion'];
        return true;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table . " (titulo, completada) VALUES (:titulo, :completada)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':completada', $this->completada, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update()
    {
        $query = "UPDATE " . $this->table . " SET titulo = :titulo, completada = :completada WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':completada', $this->completada, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
?>