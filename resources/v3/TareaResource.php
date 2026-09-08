<?php

require_once '../config/database.php';
require_once '../models/Tarea.php';

class TareaResource
{
    private $db;
    private $tarea;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->tarea = new Tarea($this->db);
    }

    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->tarea->read();
        $tareas = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tareas[] = array(
                "id" => (int)$row['id'],
                "titulo" => $row['titulo'],
                "completada" => (bool)$row['completada'],
                "fecha_creacion" => $row['fecha_creacion']
            );
        }

        http_response_code(200);
        echo json_encode(array("records" => $tareas));
    }

    public function show($id)
    {
        header("Content-Type: application/json");

        $this->tarea->id = $id;

        if ($this->tarea->readOne()) {
            http_response_code(200);
            echo json_encode(array(
                "id" => (int)$this->tarea->id,
                "titulo" => $this->tarea->titulo,
                "completada" => $this->tarea->completada,
                "fecha_creacion" => $this->tarea->fecha_creacion
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Tarea no encontrada"));
        }
    }

    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo) || !isset($data->completada)) {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos: se requieren titulo y completada"));
            return;
        }

        $this->tarea->titulo = $data->titulo;
        $this->tarea->completada = $data->completada;

        if ($this->tarea->create()) {
            $this->tarea->readOne();
            http_response_code(201);
            echo json_encode(array(
                "id" => (int)$this->tarea->id,
                "titulo" => $this->tarea->titulo,
                "completada" => $this->tarea->completada,
                "fecha_creacion" => $this->tarea->fecha_creacion
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo crear la tarea"));
        }
    }

    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo) || !isset($data->completada)) {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos: se requieren titulo y completada"));
            return;
        }

        $this->tarea->id = $id;
        $this->tarea->titulo = $data->titulo;
        $this->tarea->completada = $data->completada;

        if ($this->tarea->update()) {
            $this->tarea->readOne();
            http_response_code(200);
            echo json_encode(array(
                "id" => (int)$this->tarea->id,
                "titulo" => $this->tarea->titulo,
                "completada" => $this->tarea->completada,
                "fecha_creacion" => $this->tarea->fecha_creacion
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Tarea no encontrada"));
        }
    }
}
?>