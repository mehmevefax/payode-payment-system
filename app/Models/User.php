<?php
// app/Models/User.php
namespace App\Models; // <-- THIS LINE IS CRUCIAL AND MUST BE CORRECT!

use App\Core\Database; // Make sure Database class is also used

use App\Models\User; // En üstteki 'use' blokuna ekle

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?array {
        return $this->db->query("SELECT * FROM users WHERE username = :username LIMIT 1")
                        ->bind(':username', $username)
                        ->getRow();
    }

    public function findById(int $id): ?array {
        return $this->db->query("SELECT * FROM users WHERE id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    public function createUser(array $data): bool {
        // Şifrenin hash'lendiğinden emin ol
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->db->insert('users', $data);
    }

    public function updateUser(int $id, array $data): bool {
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "`{$key}` = :{$key}";
        }
        $setSql = implode(', ', $setClauses);

        $this->db->query("UPDATE users SET {$setSql} WHERE id = :id");
        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteUser(int $id): bool {
        return $this->db->delete('users', "id = {$id}");
    }

    public function getAllUsers(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT id, username, email, namesurname, user_type, is_active, created_at FROM users WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (username LIKE :search OR email LIKE :search OR namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['user_type']) && !empty($filters['user_type'])) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }
        if (isset($filters['parent_user_id']) && !empty($filters['parent_user_id'])) {
             $sql .= " AND parent_user_id = :parent_user_id";
             $params[':parent_user_id'] = $filters['parent_user_id'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    public function getTotalUsers(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (username LIKE :search OR email LIKE :search OR namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['user_type']) && !empty($filters['user_type'])) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }
        if (isset($filters['parent_user_id']) && !empty($filters['parent_user_id'])) {
             $sql .= " AND parent_user_id = :parent_user_id";
             $params[':parent_user_id'] = $filters['parent_user_id'];
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'];
    }
}