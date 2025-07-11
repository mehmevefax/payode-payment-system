<?php
// app/Models/Account.php
namespace App\Models;

use App\Core\Database;

class Account {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): bool {
        // account_details JSON alanını PHP array'inden JSON string'e dönüştürme
        // Gelen 'account_details' stringini JSON olarak yorumlamaya çalışalım
        if (isset($data['account_details'])) {
            $decodedAccountDetails = json_decode($data['account_details'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['account_details'] = json_encode($decodedAccountDetails);
            } else {
                // Eğer geçerli bir JSON değilse, ama bir string geldiyse, onu bir JSON objesi içine saralım
                $data['account_details'] = json_encode(['raw_input' => $data['account_details']]);
            }
        } else {
            $data['account_details'] = '{}'; // Varsayılan boş JSON
        }
        
        return $this->db->insert('accounts', $data);
    }

    public function getById(int $id): ?array {
        return $this->db->query("SELECT a.*, u.username as user_username, pm.method_name as payment_method_name
                               FROM accounts a
                               LEFT JOIN users u ON a.user_id = u.id
                               LEFT JOIN payment_methods pm ON a.method_id = pm.id
                               WHERE a.id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    public function update(int $id, array $data): bool {
        // account_details JSON alanını güncelleme sırasında işleme
        if (isset($data['account_details'])) {
            $decodedAccountDetails = json_decode($data['account_details'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['account_details'] = json_encode($decodedAccountDetails);
            } else {
                $data['account_details'] = json_encode(['raw_input' => $data['account_details']]);
            }
        }
        return $this->db->update('accounts', $data, "id = {$id}");
    }

    public function delete(int $id): bool {
        return $this->db->delete('accounts', "id = {$id}");
    }

    public function getAccounts(array $filters = [], int $limit = 20, int $offset = 0, int $currentUserId = null, string $currentUserType = 'admin'): array {
        $sql = "SELECT a.*, u.username as user_username, pm.method_name as payment_method_name
                FROM accounts a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN payment_methods pm ON a.method_id = pm.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (a.account_name LIKE :search OR u.username LIKE :search OR a.account_details LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND a.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND a.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        // Kullanıcı bazlı filtreleme
        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND a.user_id = :user_id_filter";
            $params[':user_id_filter'] = $currentUserId;
        } elseif ($currentUserType === 'staff' && $currentUserId !== null) {
            // Staff için özel filtreleme
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);
        return $this->db->getRows();
    }

    public function getTotalAccounts(array $filters = [], int $currentUserId = null, string $currentUserType = 'admin'): int {
        $sql = "SELECT COUNT(*) FROM accounts a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (a.account_name LIKE :search OR u.username LIKE :search OR a.account_details LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND a.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND a.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND a.user_id = :user_id_filter";
            $params[':user_id_filter'] = $currentUserId;
        } elseif ($currentUserType === 'staff' && $currentUserId !== null) {
            // Staff için özel filtreleme
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }
}