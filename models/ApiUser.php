<?php

class ApiUser
{
    private $conn;
    private $table_users = "api_users";
    private $table_tokens = "api_tokens";

    // Propiedades del usuario autenticado (se llenan tras un login exitoso)
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }


    public function findByUsername($username)
    {
        $query = "SELECT id, username, email, password_hash, status, created_at, updated_at
                  FROM " . $this->table_users . "
                  WHERE username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->id = $row['id'];
        $this->username = $row['username'];
        $this->email = $row['email'];
        $this->password_hash = $row['password_hash'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];

        return true;
    }

    public function verifyPassword($plainPassword)
    {
        return password_verify($plainPassword, $this->password_hash);
    }


    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }


       public function generateToken($lifetimeSeconds = 3600)
    {
        // Invalidar tokens anteriores no revocados de este usuario
        $revoke = $this->conn->prepare(
            "UPDATE " . $this->table_tokens . " SET revoked = 1 WHERE user_id = :user_id AND revoked = 0"
        );
        $revoke->bindParam(':user_id', $this->id);
        $revoke->execute();

        $token = bin2hex(random_bytes(32)); // 32 bytes -> 64 caracteres hex
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetimeSeconds);

        $query = "INSERT INTO " . $this->table_tokens . "
                  (user_id, token, expires_at)
                  VALUES (:user_id, :token, :expires_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        $stmt->execute();

        return [
            'access_token' => $token,
            'expires_at'   => $expiresAt,
        ];
    }


    public function revokeToken($token)
    {
        $query = "UPDATE " . $this->table_tokens . " SET revoked = 1 WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>