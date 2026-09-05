<?php

require_once '../models/ApiUser.php';
require_once '../config/database.php';
require_once '../models/User.php';

class UserResourceV2
{
    private $db;
    private $user;
    private $apiUser;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->apiUser = new ApiUser($this->db);
    }

        // POST /login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
            return;
        }

        $found = $this->apiUser->findByUsername($data->username);

        // Mensaje genérico en todos los casos de fallo (no revelar cuál fue)
        if (!$found || !$this->apiUser->isActive() || !$this->apiUser->verifyPassword($data->password)) {
            http_response_code(401);
            echo json_encode(array(
                "error" => "invalid_credentials",
                "message" => "Usuario o contraseña incorrectos"
            ));
            return;
        }

        $tokenData = $this->apiUser->generateToken();

        http_response_code(200);
        echo json_encode(array(
            "access_token" => $tokenData['access_token'],
            "token_type"   => "Bearer",
            "expires_at"   => $tokenData['expires_at']
        ));
    }

        private $authUser;

    public function setAuthUser($authUser)
    {
        $this->authUser = $authUser;
    }

    // POST /logout
    public function logout()
    {
        header("Content-Type: application/json");
        // $this->authUser ya viene validado por el AuthMiddleware antes de llegar aquí
        $this->apiUser->revokeToken($this->authUser['token']);

        http_response_code(200);
        echo json_encode(["message" => "Sesión cerrada exitosamente"]);
    }

    // GET /me
    public function me()
    {
        header("Content-Type: application/json");
        echo json_encode([
            "id"       => $this->authUser['id'],
            "username" => $this->authUser['username'],
            "email"    => $this->authUser['email'],
            "status"   => $this->authUser['status'],
        ]);
    }
    
    // GET /api/v2/users
    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->user->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $users_arr = array();
            $users_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user_item = array(
                    "id" => $id,
                    "nombre" => $nombre,
                    "email" => $email,
                    "fecha_registro" => $fecha_registro
                );
                array_push($users_arr["records"], $user_item);
            }

            http_response_code(200);
            echo json_encode($users_arr);
        } else {
            http_response_code(200);
            echo json_encode(array("records" => array()));
        }
    }

    // GET /api/v2/users/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->readOne()) {
            $user_arr = array(
                "id" => $this->user->id,
                "nombre" => $this->user->nombre,
                "email" => $this->user->email,
                "fecha_registro" => $this->user->fecha_registro
            );

            http_response_code(200);
            echo json_encode($user_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Usuario no encontrado"));
        }
    }

    // POST /api/v2/users
    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nombre) && !empty($data->email) && !empty($data->password)) {
            $this->user->nombre = $data->nombre;
            $this->user->email = $data->email;
            $this->user->password = $data->password;

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Usuario creado exitosamente",
                    "id" => $this->user->id
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el usuario"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
    }

    // PUT /api/v2/users/{id}
    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        $this->user->id = $id;

        if (!empty($data->nombre) && !empty($data->email)) {
            $this->user->nombre = $data->nombre;
            $this->user->email = $data->email;

            if ($this->user->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Usuario actualizado exitosamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el usuario"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
    }

    // DELETE /api/v2/users/{id}
    public function destroy($id)
    {
        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Usuario eliminado exitosamente"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar el usuario"));
        }
    }
}
?>