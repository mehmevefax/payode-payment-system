<?php
// app/Models/Log.php
namespace App\Models;

use App\Core\Database;

class Log {
    private $db;
    private $table = 'logs';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Sisteme yeni bir log kaydı ekler.
     * @param int|null $userId İlgili kullanıcı ID'si.
     * @param string $actionType Log türü (örn: 'user_login', 'deposit_approved').
     * @param string $message Log mesajı.
     * @param string $ipAddress İşlemi yapanın IP adresi.
     * @param array $details JSON olarak kaydedilecek ek detaylar.
     * @param string $level Log seviyesi ('info', 'warning', 'error', 'critical').
     * @return bool
     */
    public function addLog(?int $userId, string $actionType, string $message, string $ipAddress, array $details = [], string $level = 'info'): bool {
        $data = [
            'user_id' => $userId,
            'log_type' => $actionType, // 'action_type' yerine 'log_type' sütun adını kullan
            'level' => $level,
            'message' => $message,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
        ];
        return $this->db->insert($this->table, $data);
    }

    /**
     * Son log kaydının tarihini döndürür.
     * @return string
     */
    public function getLastLogTime(): string {
        $log = $this->db->query("SELECT created_at FROM " . $this->table . " ORDER BY created_at DESC LIMIT 1")->getRow();
        return $log['created_at'] ?? 'Henüz yok';
    }

    /**
     * Log kayıtlarını filtrelere göre getirir.
     * @param array $filters Filtreleme kriterleri (start_date, end_date, user_id, action_types, ip_address, search).
     * @param int $limit Sayfa başına kayıt sınırı (varsayılan: 20).
     * @param int $offset Sayfalama başlangıç ofseti (varsayılan: 0).
     * @return array Log kayıtları.
     */
    public function getLogs(array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT 
                l.*, 
                u.username, 
                u.namesurname AS user_namesurname_full, -- users tablosunda namesurname olarak tutuyoruz
                l.created_at AS log_date -- log tablosunda created_at sütununu log_date olarak al
            FROM 
                " . $this->table . " l
            LEFT JOIN 
                users u ON l.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND l.created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND l.created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null && $filters['user_id'] !== '') {
            $sql .= " AND l.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action_types']) && is_array($filters['action_types'])) {
            $placeholders = [];
            foreach ($filters['action_types'] as $i => $type) {
                $placeholderName = ":action_type_" . $i;
                $placeholders[] = $placeholderName;
                $params[$placeholderName] = $type;
            }
            $sql .= " AND l.log_type IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['ip_address'])) {
            $sql .= " AND l.ip_address = :ip_address";
            $params[':ip_address'] = $filters['ip_address'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (l.message LIKE :search OR l.log_type LIKE :search OR u.username LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (!empty($filters['level'])) {
            $sql .= " AND l.level = :level";
            $params[':level'] = $filters['level'];
        }


        $sql .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql); // <-- BURADAKİ HATAYI DÜZELTTİK
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->getRows();
    }

    /**
     * Log kayıtlarının toplam sayısını filtreler.
     * @param array $filters Filtreleme kriterleri.
     * @return int Toplam kayıt sayısı.
     */
    public function getTotalLogs(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM " . $this->table . " l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND l.created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND l.created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== null && $filters['user_id'] !== '') {
            $sql .= " AND l.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action_types']) && is_array($filters['action_types'])) {
            $placeholders = [];
            foreach ($filters['action_types'] as $i => $type) {
                $placeholderName = ":action_type_" . $i;
                $placeholders[] = $placeholderName;
                $params[$placeholderName] = $type;
            }
            $sql .= " AND l.log_type IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['ip_address'])) {
            $sql .= " AND l.ip_address = :ip_address";
            $params[':ip_address'] = $filters['ip_address'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (l.message LIKE :search OR l.log_type LIKE :search OR u.username LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        if (!empty($filters['level'])) {
            $sql .= " AND l.level = :level";
            $params[':level'] = $filters['level'];
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->getRow()['COUNT(*)'] ?? 0;
    }
}