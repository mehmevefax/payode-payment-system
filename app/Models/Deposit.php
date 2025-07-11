<?php
// app/Models/Deposit.php
namespace App\Models;

use App\Core\Database;

class Deposit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Yeni bir para yatırma işlemi kaydı oluşturur.
     * @param array $data Eklenecek yatırma verileri.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function create(array $data): bool {
        if (isset($data['account_details'])) {
            if (is_string($data['account_details']) && !empty($data['account_details'])) {
                $decoded = json_decode($data['account_details'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['account_details'] = json_encode($decoded);
                } else {
                    $data['account_details'] = json_encode(['raw_string' => $data['account_details']]);
                }
            } elseif (is_array($data['account_details'])) {
                $data['account_details'] = json_encode($data['account_details']);
            } else {
                $data['account_details'] = '{}';
            }
        } else {
            $data['account_details'] = '{}';
        }

        if (isset($data['client_info'])) {
            if (is_string($data['client_info']) && !empty($data['client_info'])) {
                $decoded = json_decode($data['client_info'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['client_info'] = json_encode($decoded);
                } else {
                    $data['client_info'] = json_encode(['raw_string' => $data['client_info']]);
                }
            } elseif (is_array($data['client_info'])) {
                $data['client_info'] = json_encode($data['client_info']);
            } else {
                $data['client_info'] = '{}';
            }
        } else {
            $data['client_info'] = '{}';
        }
        
        return $this->db->insert('deposits', $data);
    }

    /**
     * Bir para yatırma işlemini ID'sine göre getirir.
     * @param int $id İşlem ID'si.
     * @return array|null İşlem verileri veya bulunamazsa null.
     */
    public function getById(int $id): ?array {
        return $this->db->query("SELECT d.*, u.username as user_username, u.namesurname as user_namesurname_full, pm.method_name as payment_method_name, app_by.username as approved_by_username
                               FROM deposits d
                               LEFT JOIN users u ON d.user_id = u.id
                               LEFT JOIN payment_methods pm ON d.method_id = pm.id
                               LEFT JOIN users app_by ON d.approved_by = app_by.id
                               WHERE d.id = :id LIMIT 1")
                        ->bind(':id', $id)
                        ->getRow();
    }

    /**
     * Bir para yatırma işleminin durumunu günceller.
     * @param int $id İşlem ID'si.
     * @param string $status Yeni durum ('approved', 'rejected', 'cancelled').
     * @param int|null $approvedById Onaylayan/Reddeden kullanıcının ID'si.
     * @param string|null $rejectionReason Reddetme nedeni.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function updateStatus(int $id, string $status, int $approvedById = null, string $rejectionReason = null): bool {
        $data = [
            'status' => $status,
            'approved_by' => $approvedById,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $rejectionReason
        ];
        return $this->db->update('deposits', $data, "id = {$id}");
    }

    /**
     * Bir para yatırma işlemini ID'sine göre siler.
     * @param int $id Silinecek işlemin ID'si.
     * @return bool İşlem başarılıysa true, değilse false.
     */
    public function delete(int $id): bool {
        return $this->db->delete('deposits', "id = {$id}");
    }

    /**
     * Para yatırma işlemlerini filtreler ve sayfalar.
     * @param array $filters Filtreleme kriterleri (search, status, method_id, user_id, start_date, end_date).
     * @param int $limit Sayfa başına kayıt sınırı.
     * @param int $offset Sayfalama başlangıç ofseti.
     * @param int|null $currentUserId Mevcut oturumdaki kullanıcının ID'si (yetkilendirme için).
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi ('admin', 'sub_user', 'staff').
     * @return array İşlem kayıtları.
     */
    public function getDeposits(array $filters = [], int $limit = 20, int $offset = 0, int $currentUserId = null, string $currentUserType = 'admin'): array {
        $sql = "SELECT d.*, u.username as user_username, u.namesurname as user_namesurname_full, pm.method_name as payment_method_name, app_by.username as approved_by_username
                FROM deposits d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN payment_methods pm ON d.method_id = pm.id
                LEFT JOIN users app_by ON d.approved_by = app_by.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (d.ref_id LIKE :search OR d.user_username LIKE :search OR d.user_namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND d.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null && $filters['user_id'] !== '') {
            $sql .= " AND d.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }

        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND d.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND d.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Kullanıcı bazlı yetkilendirme filtresi (AdminController'dan bağımsız çalışabilmesi için)
        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND d.user_id = :current_user_id";
            $params[':current_user_id'] = $currentUserId;
        } elseif ($currentUserType === 'staff' && $currentUserId !== null) {
            // Personel için özel filtreleme mantığı buraya eklenebilir.
        }

        $sql .= " ORDER BY d.transaction_date DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    /**
     * Toplam para yatırma işlemi sayısını filtreler.
     * @param array $filters Filtreleme kriterleri. (status, method_id, user_id, start_date, end_date)
     * @param int|null $currentUserId Mevcut oturumdaki kullanıcının ID'si.
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi.
     * @return int Toplam kayıt sayısı.
     */
    public function getTotalDeposits(array $filters = [], int $currentUserId = null, string $currentUserType = 'admin'): int {
        $sql = "SELECT COUNT(*) FROM deposits d LEFT JOIN users u ON d.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (d.ref_id LIKE :search OR u.username LIKE :search OR u.namesurname LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND d.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null && $filters['user_id'] !== '') {
            $sql .= " AND d.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        
        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND d.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND d.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Kullanıcı bazlı yetkilendirme filtresi
        if ($currentUserType === 'sub_user' && $currentUserId !== null) {
            $sql .= " AND d.user_id = :current_user_id";
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
     * Onaylanmış para yatırma işlemlerinin toplam miktarını filtreler.
     * Bu metot artık tüm statüleri ve tarihleri filtreleyebilir.
     * @param array $filters Filtreleme kriterleri (status, method_id, user_id, start_date, end_date).
     * @param string $currentUserType Mevcut oturumdaki kullanıcının tipi.
     * @return float Toplam onaylanmış miktar.
     */
    public function getTotalApprovedAmount(array $filters = [], string $currentUserType = 'admin'): float {
        $sql = "SELECT SUM(amount) FROM deposits d LEFT JOIN users u ON d.user_id = u.id WHERE 1=1";
        $params = [];

        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['method_id']) && !empty($filters['method_id'])) {
            $sql .= " AND d.method_id = :method_id";
            $params[':method_id'] = $filters['method_id'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $sql .= " AND d.user_id = :filter_user_id";
            $params[':filter_user_id'] = $filters['user_id'];
        }
        
        // Tarih filtreleri
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND d.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND d.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        // Yönetici rolü için ek filtreleme (gerekirse)
        if ($currentUserType === 'sub_user' && Session::isLoggedIn()) { // Kendi işlemlerini görmek için
            $sql .= " AND d.user_id = :current_user_id";
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

    /**
     * Para yatırma işlemlerinin ortalama onay süresini döndürür.
     * @return float Ortalama süre (saniye cinsinden).
     */
    public function getAverageProcessingTime(): float {
        // Buradaki hata giderildi: '$this->db->table' yerine doğrudan 'deposits' kullanıldı
        return $this->db->query("SELECT AVG(processing_time_seconds) FROM deposits WHERE status IN ('approved', 'rejected') AND processing_time_seconds IS NOT NULL")->getRow()['AVG(processing_time_seconds)'] ?? 0.00;
    }
}