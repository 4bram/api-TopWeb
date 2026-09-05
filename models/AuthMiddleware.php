<?php

class AuthMiddleware
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Valida el header Authorization: Bearer <token>.
     * Si es válido, devuelve un array con los datos del usuario autenticado.
     * Si falla, responde 401 y termina la ejecución (exit).
     */
    public function authenticate()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            $this->unauthorized();
        }

        $token = trim(substr($authHeader, 7));

        if (empty($token)) {
            $this->unauthorized();
        }

        $query = "SELECT t.expires_at, t.revoked, u.id, u.username, u.email, u.status
                  FROM api_tokens t
                  JOIN api_users u ON u.id = t.user_id
                  WHERE t.token = :token
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->unauthorized();
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int)$row['revoked'] === 1) {
            $this->unauthorized();
        }

        if (strtotime($row['expires_at']) < time()) {
            $this->unauthorized();
        }

        return [
            'id'       => $row['id'],
            'username' => $row['username'],
            'email'    => $row['email'],
            'status'   => $row['status'],
            'token'    => $token,
        ];
    }

    private function unauthorized()
    {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode([
            "error"   => "unauthorized",
            "message" => "Token inválido, expirado o no proporcionado"
        ]);
        exit;
    }
}
?>