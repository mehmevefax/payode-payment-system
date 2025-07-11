<?php
// app/Models/PaymentMethod.php
namespace App\Models;

use App\Core\Database;

class PaymentMethod {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm ödeme yöntemlerinin toplam sayısını döndürür.
     * @param array $filters Filtreleme kriterleri.
     * @return int
     */
    public function getTotalCount(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM payment_methods WHERE 1=1";
        $params = [];
        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (method_name LIKE :search OR method_slug LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_type']) && !empty($filters['method_type'])) {
            $sql .= " AND method_type = :method_type";
            $params[':method_type'] = $filters['method_type'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }

    /**
     * Aktif ödeme yöntemlerinin toplam sayısını döndürür.
     * @return int
     */
    public function getActiveCount(): int {
        return $this->db->query("SELECT COUNT(*) FROM payment_methods WHERE is_active = TRUE")->getRow()['COUNT(*)'] ?? 0;
    }

    /**
     * Tüm ödeme yöntemlerini filtreler ve sayfalar.
     * @param array $filters Filtreleme kriterleri (search, method_type, is_active).
     * @param int $limit Sayfa başına kayıt sınırı.
     * @param int $offset Sayfalama başlangıç ofseti.
     * @return array
     */
    public function getAllPaymentMethods(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "SELECT * FROM payment_methods WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (method_name LIKE :search OR method_slug LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['method_type']) && !empty($filters['method_type'])) {
            $sql .= " AND method_type = :method_type";
            $params[':method_type'] = $filters['method_type'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === true || $filters['is_active'] === false)) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $sql .= " ORDER BY display_order ASC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    /**
     * Bir ödeme yöntemini slug (benzersiz kod) ile bulur.
     * @param string $slug Ödeme yönteminin slug'ı (örn: 'bank_transfer')
     * @return array|null
     */
    public function getMethodBySlug(string $slug): ?array {
        return $this->db->query("SELECT * FROM payment_methods WHERE method_slug = :slug LIMIT 1")
                        ->bind(':slug', $slug)
                        ->getRow();
    }

    /**
     * Yeni bir ödeme yöntemi ekler.
     * @param array $data Eklenecek veriler (method_name, method_slug, method_type, is_active, display_order)
     * @return bool
     */
    public function create(array $data): bool {
        // is_active değeri HTML select'ten string olarak gelebilir, boolean'a çevir
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        // display_order değeri int olmalı
        if (isset($data['display_order'])) {
            $data['display_order'] = (int)$data['display_order'];
        }
        return $this->db->insert('payment_methods', $data);
    }

    /**
     * Bir ödeme yöntemini ID'sine göre getirir.
     * @param int $id Yöntem ID'si.
     * @return array|null Yöntem verileri veya bulunamazsa null.
     */
    public function getById(int $id): ?array {
        return $this->db->query("SELECT * FROM payment_methods WHERE id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    /**
     * Bir ödeme yöntemini ID'sine göre günceller.
     * @param int $id Güncellenecek yöntemin ID'si
     * @param array $data Güncellenecek veriler
     * @return bool
     */
    public function update(int $id, array $data): bool {
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['display_order'])) {
            $data['display_order'] = (int)$data['display_order'];
        }
        return $this->db->update('payment_methods', $data, "id = {$id}");
    }

    /**
     * Bir ödeme yöntemini ID'sine göre siler.
     * @param int $id Silinecek yöntemin ID'si
     * @return bool
     */
    public function delete(int $id): bool {
        return $this->db->delete('payment_methods', "id = {$id}");
    }
}