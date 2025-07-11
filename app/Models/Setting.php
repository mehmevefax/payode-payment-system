<?php
// app/Models/Setting.php
namespace App\Models;

use App\Core\Database;

class Setting {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Yeni bir ayar kaydı oluşturur.
     * @param string $key Ayar anahtarı.
     * @param string $value Ayar değeri.
     * @param string $description Açıklama.
     * @param bool $isPublic Herkese açık mı.
     * @return bool
     */
    public function create(string $key, string $value, string $description = '', bool $isPublic = false): bool {
        $data = [
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description,
            'is_public' => $isPublic,
        ];
        return $this->db->insert('settings', $data);
    }

    /**
     * Bir ayarı anahtarına göre getirir.
     * @param string $key Ayar anahtarı.
     * @return array|null Ayar verisi veya null.
     */
    public function getByKey(string $key): ?array {
        return $this->db->query("SELECT * FROM settings WHERE setting_key = :key LIMIT 1")
                        ->bind(':key', $key)
                        ->getRow();
    }

    /**
     * Bir ayarı anahtarına göre günceller. Yoksa oluşturur.
     * @param string $key Ayar anahtarı.
     * @param string $value Yeni ayar değeri.
     * @param string $description Açıklama (güncelleme sırasında opsiyonel).
     * @param bool $isPublic Herkese açık mı (güncelleme sırasında opsiyonel).
     * @return bool
     */
    public function updateSetting(string $key, string $value, string $description = '', bool $isPublic = false): bool {
        $existingSetting = $this->getByKey($key);
        if ($existingSetting) {
            $dataToUpdate = ['setting_value' => $value];
            if (!empty($description)) $dataToUpdate['description'] = $description;
            if (func_num_args() > 3) {
                 $dataToUpdate['is_public'] = $isPublic;
            }
            return $this->db->update('settings', $dataToUpdate, "setting_key = '{$key}'");
        } else {
            return $this->create($key, $value, $description, $isPublic);
        }
    }

    /**
     * Tüm ayarları bir dizi olarak getirir (anahtar-değer çiftleri).
     * @return array Anahtar-değer çiftleri olarak ayarlar.
     */
    public function getAllSettings(): array {
        $settings = $this->db->query("SELECT setting_key, setting_value FROM settings")->getRows();
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = $setting['setting_value'];
        }
        return $formattedSettings;
    }

    /**
     * Bir ayarı siler.
     * @param string $key Silinecek ayar anahtarı.
     * @return bool
     */
    public function delete(string $key): bool {
        return $this->db->delete('settings', "setting_key = '{$key}'");
    }
}