<?php
// app/Models/ApiCredential.php
namespace App\Models;

use App\Core\Database;

class ApiCredential {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Yeni bir API kimlik bilgisi kaydı oluşturur.
     * @param array $data Eklenecek veriler (method_id, api_key, api_secret, api_endpoint, other_config, is_active).
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function create(array $data): bool {
        // other_config'i JSON string'e dönüştür
        if (isset($data['other_config']) && is_array($data['other_config'])) {
            $data['other_config'] = json_encode($data['other_config']);
        } elseif (isset($data['other_config']) && is_string($data['other_config']) && !empty($data['other_config'])) {
            // Eğer string olarak geldiyse ve boş değilse, JSON parse etmeye çalışalım
            $decoded = json_decode($data['other_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['other_config'] = json_encode($decoded);
            } else {
                // Geçerli JSON değilse, string'i bir JSON objesi içine saralım
                $data['other_config'] = json_encode(['raw_input' => $data['other_config']]);
            }
        } else {
            $data['other_config'] = '{}';
        }
        
        // is_active değeri boolean'a çevir
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        
        return $this->db->insert('api_credentials', $data);
    }

    /**
     * Bir API kimlik bilgisini ID'sine göre getirir.
     * @param int $id Kimlik bilgisi ID'si.
     * @return array|null Veriler veya bulunamazsa null.
     */
    public function getById(int $id): ?array {
        return $this->db->query("SELECT ac.*, pm.method_name as payment_method_name FROM api_credentials ac LEFT JOIN payment_methods pm ON ac.method_id = pm.id WHERE ac.id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    /**
     * Bir API kimlik bilgisini günceller.
     * @param int $id Güncellenecek kaydın ID'si.
     * @param array $data Güncellenecek veriler.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function update(int $id, array $data): bool {
        if (isset($data['other_config']) && is_array($data['other_config'])) {
            $data['other_config'] = json_encode($data['other_config']);
        } elseif (isset($data['other_config']) && is_string($data['other_config']) && !empty($data['other_config'])) {
            $decoded = json_decode($data['other_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['other_config'] = json_encode($decoded);
            } else {
                $data['other_config'] = json_encode(['raw_input' => $data['other_config']]);
            }
        }
        
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $this->db->update('api_credentials', $data, "id = {$id}");
    }

    /**
     * Bir API kimlik bilgisini siler.
     * @param int $id Silinecek kaydın ID'si.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function delete(int $id): bool {
        return $this->db->delete('api_credentials', "id = {$id}");
    }

    /**
     * Tüm API kimlik bilgilerini filtreler ve sayfalar.
     * @param array $filters Filtreleme kriterleri.
     * @param int $limit Sayfa başına kayıt sınırı.
     * @param int $offset Sayfalama başlangıç ofseti.
     * @return array
     */
    public function getAllCredentials(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT ac.*, pm.method_name as payment_method_name FROM api_credentials ac LEFT JOIN payment_methods pm ON ac.method_id = pm.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (ac.api_key LIKE :search OR ac.api_secret LIKE :search OR ac.api_endpoint LIKE :search OR pm.method_name LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND ac.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND ac.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $sql .= " ORDER BY ac.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);
        return $this->db->getRows();
    }

    /**
     * Toplam API kimlik bilgisi sayısını filtreler.
     * @param array $filters Filtreleme kriterleri.
     * @return int Toplam kayıt sayısı.
     */
    public function getTotalCredentials(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM api_credentials ac LEFT JOIN payment_methods pm ON ac.method_id = pm.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (ac.api_key LIKE :search OR ac.api_secret LIKE :search OR ac.api_endpoint LIKE :search OR pm.method_name LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND ac.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND ac.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }
}