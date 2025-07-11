<?php
// app/Models/Withdrawal.php
namespace App\Models;

use App\Core\Database;

class Withdrawal {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ... (create, getById, updateStatus, delete metotları aynı kalacak) ...

    /**
     * Para çekme işlemlerini filtreler ve sayfalar.
     * @param array $filters Filtreleme kriterleri (search, status, method_id, user_id, start_date, end_date).
     * @param int $limit Sayfa başına kayıt sınırı.
     * @param int $offset Sayfalama başlangıç ofseti.
     * @param int|null $currentUserId Mevcut oturumdaki kullanıcının ID'si.
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi.
     * @return array İşlem kayıtları.
     */
    public function getWithdrawals(array $filters = [], int $limit = 20, int $offset = 0, int $currentUserId = null, string $currentUserType = 'admin'): array {
        $sql = "SELECT w.*, u.username as user_username, u.namesurname as user_namesurname_full, pm.method_name as payment_method_name, app_by.username as approved_by_username
                FROM withdrawals w
                LEFT JOIN users u ON w.user_id = u.id
                LEFT JOIN payment_methods pm ON w.method_id = pm.id
                LEFT JOIN users app_by ON w.approved_by = app_by.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (w.ref_id LIKE :search OR w.user_username LIKE :search OR w.user_namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND w.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND w.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $sql .= " AND w.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }

        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND w.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND w.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Kullanıcı bazlı yetkilendirme filtresi
        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND w.user_id = :current_user_id";
            $params[':current_user_id'] = $currentUserId;
        } elseif ($currentUserType === 'staff' && $currentUserId !== null) {
            // Personel için özel filtreleme mantığı
        }

        $sql .= " ORDER BY w.transaction_date DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    /**
     * Toplam para çekme işlemi sayısını filtreler.
     * @param array $filters Filtreleme kriterleri. (status, method_id, user_id, start_date, end_date)
     * @param int|null $currentUserId Mevcut oturumdaki kullanıcının ID'si.
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi.
     * @return int Toplam kayıt sayısı.
     */
    public function getTotalWithdrawals(array $filters = [], int $currentUserId = null, string $currentUserType = 'admin'): int {
        $sql = "SELECT COUNT(*) FROM withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (w.ref_id LIKE :search OR u.username LIKE :search OR u.namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND w.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND w.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $sql .= " AND w.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        
        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND w.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND w.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Kullanıcı bazlı yetkilendirme filtresi
        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND w.user_id = :current_user_id";
            $params[':current_user_id'] = $currentUserId;
        } elseif ($currentUserType === 'staff' && $currentUserId !== null) {
            // Personel için özel filtreleme mantığı
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }

    /**
     * Onaylanmış para çekme işlemlerinin toplam miktarını filtreler.
     * Bu metot artık tüm statüleri ve tarihleri filtreleyebilir.
     * @param array $filters Filtreleme kriterleri (status, method_id, user_id, start_date, end_date).
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi.
     * @return float Toplam onaylanmış miktar.
     */
    public function getTotalApprovedAmount(array $filters = [], string $currentUserType = 'admin'): float {
        $sql = "SELECT SUM(amount) FROM withdrawals WHERE status = 'approved'";
        $params = [];

        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $sql .= " AND user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        
        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        // Yönetici rolü için ek filtreleme (gerekirse)
        if ($currentUserType === 'sub_user' && Session::isLoggedIn()) { // Kendi işlemlerini görmek için
            $sql .= " AND user_id = :current_user_id";
            $params[':current_user_id'] = Session::getUser('id');
        } elseif ($currentUserType === 'staff' && Session::isLoggedIn()) {
            // Personel için özel filtreleme mantığı
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['SUM(amount)'] ?? 0.00;
    }

    // ... (getAverageProcessingTime metodu aynı kalacak) ...
}