<?php
// app/Models/Permission.php
namespace App\Models;

use App\Core\Database;

class Permission {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Yeni bir yetki kaydı oluşturur.
     * @param array $data Eklenecek yetki verileri (permission_key, user_id, is_enabled).
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function create(array $data): bool {
        return $this->db->insert('user_permissions', $data);
    }

    /**
     * Bir yetkiyi ID'sine göre getirir.
     * @param int $id Yetki ID'si.
     * @return array|null Yetki verileri veya bulunamazsa null.
     */
    public function getById(int $id): ?array {
        return $this->db->query("SELECT p.*, u.username FROM user_permissions p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    /**
     * Bir yetki kaydını günceller.
     * @param int $id Güncellenecek yetkinin ID'si.
     * @param array $data Güncellenecek veriler.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function update(int $id, array $data): bool {
        return $this->db->update('user_permissions', $data, "id = {$id}");
    }

    /**
     * Bir yetki kaydını siler.
     * @param int $id Silinecek yetkinin ID'si.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function delete(int $id): bool {
        return $this->db->delete('user_permissions', "id = {$id}");
    }

    /**
     * Tüm yetkileri filtreler ve sayfalar.
     * @param array $filters Filtreleme kriterleri (search, user_id, is_enabled).
     * @param int $limit Sayfa başına kayıt sınırı.
     * @param int $offset Sayfalama başlangıç ofseti.
     * @return array Yetki kayıtları.
     */
    public function getAllPermissions(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT p.*, u.username FROM user_permissions p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (p.permission_key LIKE :search OR u.username LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $sql .= " AND p.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (isset($filters['is_enabled']) && ($filters['is_enabled'] === true || $filters['is_enabled'] === false)) {
            $sql .= " AND p.is_enabled = :is_enabled";
            $params[':is_enabled'] = $filters['is_enabled'];
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    /**
     * Toplam yetki sayısını filtreler.
     * @param array $filters Filtreleme kriterleri.
     * @return int Toplam yetki sayısı.
     */
    public function getTotalPermissions(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM user_permissions p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (p.permission_key LIKE :search OR u.username LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $sql .= " AND p.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (isset($filters['is_enabled']) && ($filters['is_enabled'] === true || $filters['is_enabled'] === false)) {
            $sql .= " AND p.is_enabled = :is_enabled";
            $params[':is_enabled'] = $filters['is_enabled'];
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }
}