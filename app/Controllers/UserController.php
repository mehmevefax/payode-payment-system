<?php
// app/Controllers/UserController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Models\User;
use App\Models\Permission; // İzinler için model
use App\Services\Logger;

class UserController {
    private $userModel;
    private $permissionModel;
    private $logger;

    public function __construct() {
        $this->userModel = new User();
        $this->permissionModel = new Permission(); // Permission modelini başlat
        $this->logger = new Logger('user_actions.log');
    }

    // Kullanıcıları Listeleme (Aynı kalacak)
    public function listUsers(array $queryParams) {
        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin')) {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 20);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'user_type' => Helper::sanitizeInput($queryParams['user_type'] ?? ''),
            'parent_user_id' => Helper::sanitizeInput($queryParams['parent_user_id'] ?? ''),
        ];

        $users = $this->userModel->getAllUsers($filters, $limit, $offset);
        $totalUsers = $this->userModel->getTotalUsers($filters);

        $data = [
            'pageTitle' => 'Kullanıcılar',
            'currentSection' => 'Kullanıcı Yönetimi',
            'activeMenu' => 'users',
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalUsers / $limit),
                'total_records' => $totalUsers,
                'limit' => $limit,
            ],
            'filters' => $filters,
        ];

        $this->render('admin/users/list', $data);
    }

    // Kullanıcı Ekleme/Düzenleme/Silme İşlemleri (Aynı kalacak)
    public function processUserAction(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz işlem denemesi: Kullanıcı yönetimi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        $action = Helper::sanitizeInput($postData['action'] ?? '');
        $userId = (int)($postData['id'] ?? 0);

        switch ($action) {
            case 'add':
                $username = Helper::sanitizeInput($postData['username'] ?? '');
                $email = Helper::sanitizeInput($postData['email'] ?? '');
                $password = $postData['password'] ?? '';
                $namesurname = Helper::sanitizeInput($postData['namesurname'] ?? '');
                $userType = Helper::sanitizeInput($postData['user_type'] ?? 'sub_user');
                $parentUserId = (int)($postData['parent_user_id'] ?? null);
                $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN);

                if (empty($username) || empty($email) || empty($password) || empty($namesurname)) {
                    echo json_encode(["status" => "error", "message" => "Tüm alanları doldurmanız gerekmektedir."]);
                    exit();
                }

                $data = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'namesurname' => $namesurname,
                    'user_type' => $userType,
                    'parent_user_id' => ($parentUserId > 0) ? $parentUserId : null,
                    'is_active' => $isActive
                ];

                if ($this->userModel->createUser($data)) {
                    $this->logger->info("Yeni kullanıcı eklendi: " . $username, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "success", "message" => "Kullanıcı başarıyla eklendi."]);
                } else {
                    $this->logger->error("Kullanıcı ekleme hatası: " . $username, ['admin_id' => Session::getUser('id'), 'error' => $this->userModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "error", "message" => "Kullanıcı eklenirken bir hata oluştu."]);
                }
                break;
            case 'edit':
                if ($userId === 0) {
                    echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı ID."]);
                    exit();
                }
                $updateData = [];
                if (isset($postData['username'])) $updateData['username'] = Helper::sanitizeInput($postData['username']);
                if (isset($postData['email'])) $updateData['email'] = Helper::sanitizeInput($postData['email']);
                if (isset($postData['namesurname'])) $updateData['namesurname'] = Helper::sanitizeInput($postData['namesurname']);
                if (isset($postData['user_type'])) $updateData['user_type'] = Helper::sanitizeInput($postData['user_type']);
                if (isset($postData['is_active'])) $updateData['is_active'] = filter_var($postData['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (isset($postData['parent_user_id'])) $updateData['parent_user_id'] = (int)$postData['parent_user_id'] > 0 ? (int)$postData['parent_user_id'] : null;
                if (!empty($postData['password'])) {
                    $updateData['password'] = $postData['password'];
                }

                if ($this->userModel->updateUser($userId, $updateData)) {
                    $this->logger->info("Kullanıcı güncellendi: ID " . $userId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "success", "message" => "Kullanıcı başarıyla güncellendi."]);
                } else {
                    $this->logger->error("Kullanıcı güncelleme hatası: ID " . $userId, ['admin_id' => Session::getUser('id'), 'error' => $this->userModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "error", "message" => "Kullanıcı güncellenirken bir hata oluştu."]);
                }
                break;
            case 'delete':
                if ($userId === 0) {
                    echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı ID."]);
                    exit();
                }
                if ($userId == Session::getUser('id')) {
                    echo json_encode(["status" => "error", "message" => "Kendi hesabınızı silemezsiniz."]);
                    exit();
                }

                if ($this->userModel->deleteUser($userId)) {
                    $this->logger->info("Kullanıcı silindi: ID " . $userId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "success", "message" => "Kullanıcı başarıyla silindi."]);
                } else {
                    $this->logger->error("Kullanıcı silme hatası: ID " . $userId, ['admin_id' => Session::getUser('id'), 'error' => $this->userModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    echo json_encode(["status" => "error", "message" => "Kullanıcı silinirken bir hata oluştu."]);
                }
                break;
            default:
                echo json_encode(["status" => "error", "message" => "Geçersiz işlem."]);
                break;
        }
        exit();
    }

    // Kullanıcı Detayını Görüntüleme (AJAX ile)
    public function viewUserDetail(array $queryParams) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }

        $userId = (int)($queryParams['id'] ?? 0);
        if ($userId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        $user = $this->userModel->findById($userId);

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "Kullanıcı bulunamadı."]);
            exit();
        }

        // Parent user'ı bulma
        $parentUsername = null;
        if ($user['parent_user_id']) {
            $parentUser = $this->userModel->findById($user['parent_user_id']);
            $parentUsername = $parentUser['username'] ?? null;
        }

        // Frontend için ek formatlamalar
        $user['is_active_text'] = $user['is_active'] ? 'Aktif' : 'Pasif';
        $user['status_class'] = $user['is_active'] ? 'bg-success' : 'bg-danger';
        $user['created_at_formatted'] = date('d M Y H:i:s', strtotime($user['created_at']));
        $user['parent_username'] = $parentUsername;

        echo json_encode(["status" => "success", "data" => $user]);
        exit();
    }

    // Rol ve Yetkileri Listeleme
    public function listPermissions(array $queryParams) {
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 20);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'user_id' => Helper::sanitizeInput($queryParams['user_id'] ?? ''),
            'is_enabled' => isset($queryParams['is_enabled']) ? (bool)$queryParams['is_enabled'] : null,
        ];

        $permissions = $this->permissionModel->getAllPermissions($filters, $limit, $offset);
        $totalPermissions = $this->permissionModel->getTotalPermissions($filters);

        $data = [
            'pageTitle' => 'Roller & Yetkiler',
            'currentSection' => 'Yetkilendirme',
            'activeMenu' => 'permissions',
            'permissions' => $permissions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalPermissions / $limit),
                'total_records' => $totalPermissions,
                'limit' => $limit,
            ],
            'filters' => $filters,
        ];
        $this->render('admin/users/permissions', $data);
    }

    // Yeni Yetki Ekleme
    public function addPermission(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz yetki ekleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Yetki ekleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $permissionKey = Helper::sanitizeInput($postData['permission_key'] ?? '');
        $userId = (int)($postData['user_id'] ?? null);
        $isEnabled = filter_var($postData['is_enabled'] ?? 1, FILTER_VALIDATE_BOOLEAN);

        if (empty($permissionKey)) {
            echo json_encode(["status" => "error", "message" => "Yetki anahtarı boş bırakılamaz."]);
            exit();
        }

        $dataToInsert = [
            'permission_key' => $permissionKey,
            'user_id' => ($userId > 0) ? $userId : null,
            'is_enabled' => $isEnabled,
        ];

        if ($this->permissionModel->create($dataToInsert)) {
            $this->logger->info("Yeni yetki eklendi: " . $permissionKey, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Yetki başarıyla eklendi."]);
        } else {
            $this->logger->error("Yetki ekleme hatası: " . $permissionKey, ['admin_id' => Session::getUser('id'), 'error' => $this->permissionModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yetki eklenirken bir hata oluştu."]);
        }
        exit();
    }

    // Yetki Detayını Görüntüleme
    public function viewPermissionDetail(array $queryParams) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }
        $permissionId = (int)($queryParams['id'] ?? 0);
        if ($permissionId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }
        $permission = $this->permissionModel->getById($permissionId);
        if (!$permission) {
            echo json_encode(["status" => "error", "message" => "Yetki bulunamadı."]);
            exit();
        }
        // Frontend için ek formatlamalar
        $permission['is_enabled_text'] = $permission['is_enabled'] ? 'Evet' : 'Hayır';
        $permission['status_class'] = $permission['is_enabled'] ? 'bg-success' : 'bg-danger';
        $permission['created_at_formatted'] = date('d M Y H:i:s', strtotime($permission['created_at']));
        $permission['username'] = $permission['username'] ?? 'Genel/Tanımsız';
        echo json_encode(["status" => "success", "data" => $permission]);
        exit();
    }

    // Yetki Düzenleme
    public function editPermission(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz yetki düzenleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Yetki düzenleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $permissionId = (int)($postData['id'] ?? 0);
        $permissionKey = Helper::sanitizeInput($postData['permission_key'] ?? '');
        $userId = (int)($postData['user_id'] ?? null);
        $isEnabled = filter_var($postData['is_enabled'] ?? 1, FILTER_VALIDATE_BOOLEAN);

        if ($permissionId === 0 || empty($permissionKey)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun."]);
            exit();
        }

        $dataToUpdate = [
            'permission_key' => $permissionKey,
            'user_id' => ($userId > 0) ? $userId : null,
            'is_enabled' => $isEnabled,
        ];
        
        $updateSuccess = $this->permissionModel->update($permissionId, $dataToUpdate, "id = {$permissionId}");

        if ($updateSuccess) {
            $this->logger->info("Yetki güncellendi: ID " . $permissionId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Yetki başarıyla güncellendi."]);
        } else {
            $this->logger->error("Yetki güncelleme hatası: ID " . $permissionId, ['admin_id' => Session::getUser('id'), 'error' => $this->permissionModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yetki güncellenirken bir hata oluştu."]);
        }
        exit();
    }

    // Yetki Silme
    public function deletePermission(array $postData) {
        header('Content-Type: application/json');
        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz yetki silme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Yetki silme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $permissionId = (int)($postData['id'] ?? 0);
        if ($permissionId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        if ($this->permissionModel->delete($permissionId)) {
            $this->logger->info("Yetki silindi: ID " . $permissionId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Yetki başarıyla silindi."]);
        } else {
            $this->logger->error("Yetki silme hatası: ID " . $permissionId, ['admin_id' => Session::getUser('id'), 'error' => $this->permissionModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yetki silinirken bir hata oluştu."]);
        }
        exit();
    }

    protected function render(string $viewPath, array $data = []) {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}