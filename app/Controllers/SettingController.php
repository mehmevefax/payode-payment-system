<?php
// app/Controllers/SettingController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Models\PaymentMethod;
use App\Models\ApiCredential; // ApiCredential modelini dahil et
use App\Models\Setting;
use App\Services\Logger;

class SettingController {
    private $paymentMethodModel;
    private $apiCredentialModel; // Tanımla
    private $settingModel;
    private $logger;

    public function __construct() {
        $this->paymentMethodModel = new PaymentMethod();
        $this->apiCredentialModel = new ApiCredential(); // Başlat
        // $this->settingModel = new Setting(); // Henüz aktif etmedik, hata vermesin diye yorumda
        $this->logger = new Logger('settings_actions.log');
    }

    // Ödeme Yöntemlerini Listeleme/Yönetme (Aynı kalacak)
    public function listPaymentMethods(array $queryParams) {
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 10);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'method_type' => Helper::sanitizeInput($queryParams['method_type'] ?? ''),
            'is_active' => isset($queryParams['is_active']) ? (bool)$queryParams['is_active'] : null,
        ];

        $methods = $this->paymentMethodModel->getAllPaymentMethods($filters, $limit, $offset);
        $totalMethods = $this->paymentMethodModel->getTotalCount($filters);

        $data = [
            'pageTitle' => 'Ödeme Yöntemleri',
            'currentSection' => 'Ödeme Yöntemleri',
            'activeMenu' => 'payment_methods',
            'methods' => $methods,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalMethods / $limit),
                'total_records' => $totalMethods,
                'limit' => $limit,
            ],
            'filters' => $filters,
        ];
        $this->render('admin/payment_methods/list', $data);
    }

    // Yeni Ödeme Yöntemi Ekleme (Aynı kalacak)
    public function addPaymentMethod(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz ödeme yöntemi ekleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Ödeme yöntemi ekleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $methodName = Helper::sanitizeInput($postData['method_name'] ?? '');
        $methodSlug = Helper::sanitizeInput($postData['method_slug'] ?? '');
        $methodType = Helper::sanitizeInput($postData['method_type'] ?? '');
        $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN);
        $displayOrder = (int)($postData['display_order'] ?? 0);

        if (empty($methodName) || empty($methodSlug) || empty($methodType)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun."]);
            exit();
        }

        $dataToInsert = [
            'method_name' => $methodName,
            'method_slug' => $methodSlug,
            'method_type' => $methodType,
            'is_active' => $isActive,
            'display_order' => $displayOrder,
        ];

        if ($this->paymentMethodModel->create($dataToInsert)) {
            $this->logger->info("Yeni ödeme yöntemi eklendi: " . $methodName, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Ödeme yöntemi başarıyla eklendi."]);
        } else {
            $this->logger->error("Ödeme yöntemi ekleme hatası: " . $methodName, ['admin_id' => Session::getUser('id'), 'error' => $this->paymentMethodModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Ödeme yöntemi eklenirken bir hata oluştu."]);
        }
        exit();
    }

    // Ödeme Yöntemi Detayını Görüntüleme (AJAX ile) (Aynı kalacak)
    public function viewPaymentMethodDetail(array $queryParams) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }
        $methodId = (int)($queryParams['id'] ?? 0);
        if ($methodId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }
        $method = $this->paymentMethodModel->getById($methodId);
        if (!$method) {
            echo json_encode(["status" => "error", "message" => "Ödeme yöntemi bulunamadı."]);
            exit();
        }
        $method['is_active_text'] = $method['is_active'] ? 'Evet' : 'Hayır';
        $method['status_class'] = $method['is_active'] ? 'bg-success' : 'bg-danger';
        $method['created_at_formatted'] = date('d M Y H:i:s', strtotime($method['created_at']));
        echo json_encode(["status" => "success", "data" => $method]);
        exit();
    }

    // Ödeme Yöntemi Düzenleme (Aynı kalacak)
    public function editPaymentMethod(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz ödeme yöntemi düzenleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Ödeme yöntemi düzenleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $methodId = (int)($postData['id'] ?? 0);
        $methodName = Helper::sanitizeInput($postData['method_name'] ?? '');
        $methodSlug = Helper::sanitizeInput($postData['method_slug'] ?? '');
        $methodType = Helper::sanitizeInput($postData['method_type'] ?? '');
        $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN);
        $displayOrder = (int)($postData['display_order'] ?? 0);

        if ($methodId === 0 || empty($methodName) || empty($methodSlug) || empty($methodType)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun."]);
            exit();
        }

        $dataToUpdate = [
            'method_name' => $methodName,
            'method_slug' => $methodSlug,
            'method_type' => $methodType,
            'is_active' => $isActive,
            'display_order' => $displayOrder,
        ];
        
        $updateSuccess = $this->paymentMethodModel->update($methodId, $dataToUpdate, "id = {$methodId}");

        if ($updateSuccess) {
            $this->logger->info("Ödeme yöntemi güncellendi: ID " . $methodId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Ödeme yöntemi başarıyla güncellendi."]);
        } else {
            $this->logger->error("Ödeme yöntemi güncelleme hatası: ID " . $methodId, ['admin_id' => Session::getUser('id'), 'error' => $this->paymentMethodModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Ödeme yöntemi güncellenirken bir hata oluştu."]);
        }
        exit();
    }

    // Ödeme Yöntemi Silme (Aynı kalacak)
    public function deletePaymentMethod(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz ödeme yöntemi silme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Ödeme yöntemi silme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $methodId = (int)($postData['id'] ?? 0);
        if ($methodId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        if ($this->paymentMethodModel->delete($methodId)) {
            $this->logger->info("Ödeme yöntemi silindi: ID " . $methodId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Ödeme yöntemi başarıyla silindi."]);
        } else {
            $this->logger->error("Ödeme yöntemi silme hatası: ID " . $methodId, ['admin_id' => Session::getUser('id'), 'error' => $this->paymentMethodModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Ödeme yöntemi silinirken bir hata oluştu."]);
        }
        exit();
    }

    // API Kimlik Bilgilerini Listeleme/Yönetme
    public function listApiCredentials(array $queryParams) {
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 10);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'method_id' => Helper::sanitizeInput($queryParams['method_id'] ?? ''),
            'is_active' => isset($queryParams['is_active']) ? (bool)$queryParams['is_active'] : null,
        ];

        $credentials = $this->apiCredentialModel->getAllCredentials($filters, $limit, $offset);
        $totalCredentials = $this->apiCredentialModel->getTotalCredentials($filters);

        $data = [
            'pageTitle' => 'API Ayarları',
            'currentSection' => 'API Ayarları',
            'activeMenu' => 'api_settings',
            'credentials' => $credentials,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCredentials / $limit),
                'total_records' => $totalCredentials,
                'limit' => $limit,
            ],
            'filters' => $filters,
        ];
        $this->render('admin/api_settings/list', $data);
    }

    // Yeni API Ayarı Ekleme
    public function addApiCredential(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz API ayarı ekleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: API ayarı ekleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $methodId = (int)($postData['method_id'] ?? 0);
        $apiKey = Helper::sanitizeInput($postData['api_key'] ?? '');
        $apiSecret = Helper::sanitizeInput($postData['api_secret'] ?? '');
        $apiEndpoint = Helper::sanitizeInput($postData['api_endpoint'] ?? '');
        $otherConfigRaw = $postData['other_config'] ?? '{}';
        $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN);

        $otherConfig = json_decode($otherConfigRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE && !empty($otherConfigRaw)) {
            $otherConfig = ['raw_input' => $otherConfigRaw];
        } else if (json_last_error() !== JSON_ERROR_NONE && empty($otherConfigRaw)) {
            $otherConfig = [];
        }

        if ($methodId === 0 || empty($apiKey) || empty($apiSecret) || empty($apiEndpoint)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun."]);
            exit();
        }

        $dataToInsert = [
            'method_id' => $methodId,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'api_endpoint' => $apiEndpoint,
            'other_config' => json_encode($otherConfig),
            'is_active' => $isActive,
        ];

        if ($this->apiCredentialModel->create($dataToInsert)) {
            $this->logger->info("Yeni API ayarı eklendi: Method ID " . $methodId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "API ayarı başarıyla eklendi."]);
        } else {
            $this->logger->error("API ayarı ekleme hatası: Method ID " . $methodId, ['admin_id' => Session::getUser('id'), 'error' => $this->apiCredentialModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "API ayarı eklenirken bir hata oluştu."]);
        }
        exit();
    }

    // API Ayarı Detayını Görüntüleme
    public function viewApiCredentialDetail(array $queryParams) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }
        $credentialId = (int)($queryParams['id'] ?? 0);
        if ($credentialId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }
        $credential = $this->apiCredentialModel->getById($credentialId);
        if (!$credential) {
            echo json_encode(["status" => "error", "message" => "API ayarı bulunamadı."]);
            exit();
        }
        $credential['other_config'] = json_decode($credential['other_config'] ?? '{}', true);
        $credential['is_active_text'] = $credential['is_active'] ? 'Aktif' : 'Pasif';
        $credential['status_class'] = $credential['is_active'] ? 'bg-success' : 'bg-danger';
        $credential['created_at_formatted'] = date('d M Y H:i:s', strtotime($credential['created_at']));
        echo json_encode(["status" => "success", "data" => $credential]);
        exit();
    }

    // API Ayarı Düzenleme
    public function editApiCredential(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz API ayarı düzenleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: API ayarı düzenleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $credentialId = (int)($postData['id'] ?? 0);
        $methodId = (int)($postData['method_id'] ?? 0);
        $apiKey = Helper::sanitizeInput($postData['api_key'] ?? '');
        $apiSecret = Helper::sanitizeInput($postData['api_secret'] ?? '');
        $apiEndpoint = Helper::sanitizeInput($postData['api_endpoint'] ?? '');
        $otherConfigRaw = $postData['other_config'] ?? '{}';
        $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN);

        $otherConfig = json_decode($otherConfigRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $otherConfig = ['raw_input' => $otherConfigRaw];
        }

        if ($credentialId === 0 || $methodId === 0 || empty($apiKey) || empty($apiSecret) || empty($apiEndpoint)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun."]);
            exit();
        }

        $dataToUpdate = [
            'method_id' => $methodId,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'api_endpoint' => $apiEndpoint,
            'other_config' => json_encode($otherConfig),
            'is_active' => $isActive,
        ];
        
        $updateSuccess = $this->apiCredentialModel->update($credentialId, $dataToUpdate, "id = {$credentialId}");

        if ($updateSuccess) {
            $this->logger->info("API ayarı güncellendi: ID " . $credentialId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "API ayarı başarıyla güncellendi."]);
        } else {
            $this->logger->error("API ayarı güncelleme hatası: ID " . $credentialId, ['admin_id' => Session::getUser('id'), 'error' => $this->apiCredentialModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "API ayarı güncellenirken bir hata oluştu."]);
        }
        exit();
    }

    // API Ayarı Silme
    public function deleteApiCredential(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz API ayarı silme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: API ayarı silme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $credentialId = (int)($postData['id'] ?? 0);
        if ($credentialId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        if ($this->apiCredentialModel->delete($credentialId)) {
            $this->logger->info("API ayarı silindi: ID " . $credentialId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "API ayarı başarıyla silindi."]);
        } else {
            $this->logger->error("API ayarı silme hatası: ID " . $credentialId, ['admin_id' => Session::getUser('id'), 'error' => $this->apiCredentialModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "API ayarı silinirken bir hata oluştu."]);
        }
        exit();
    }

    // Genel Sistem Ayarlarını Görüntüleme/Güncelleme
    public function showGeneralSettings(array $postData = []) {
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        // Setting modelini kullanacağız
        // $settingModel = new \App\Models\Setting();
        // $settings = $settingModel->getAllSettings();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ayarları kaydetme mantığı
            // $settingModel->updateSetting($key, $value);
            $this->logger->info("Genel ayarlar güncellendi.", ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            Session::setFlash('success', 'Ayarlar başarıyla kaydedildi.');
            Helper::redirect(Config::BASE_URL . '/admin.php?page=settings');
        }

        $data = [
            'pageTitle' => 'Genel Ayarlar',
            'currentSection' => 'Genel Ayarlar',
            'activeMenu' => 'general_settings',
            'settings' => [], // Geçici olarak boş
        ];
        $this->render('admin/settings/general', $data);
    }

    protected function render(string $viewPath, array $data = []) {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}