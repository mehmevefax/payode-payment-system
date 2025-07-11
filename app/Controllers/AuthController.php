<?php
// app/Controllers/AuthController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Models\User;
use App\Services\Logger; // Loglama için

class AuthController {
    private $userModel;
    private $logger;

    public function __construct() {
        $this->userModel = new User();
        $this->logger = new Logger('auth.log');
    }

    public function showLogin() {
        // Zaten giriş yapmışsa admin paneline yönlendir
        if (Session::isLoggedIn()) {
            if (Session::getUser('user_type') === 'admin' || Session::getUser('user_type') === 'staff') {
                Helper::redirect(Config::BASE_URL . '/admin');
            } else {
                // Alt kullanıcılar için dashboard (ileride yapılacak)
                // Helper::redirect(Config::BASE_URL . '/user/dashboard');
                echo "Kullanıcı paneli buraya gelecek."; // Şimdilik basit bir mesaj
                exit();
            }
        }

        // POST isteği ile login denemesi
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->loginUser($_POST);
        } else {
            // Login sayfasını göster
            require_once __DIR__ . '/../Views/auth/login.php';
        }
    }

    private function loginUser(array $postData) {
        $username = Helper::sanitizeInput($postData['username'] ?? '');
        $password = $postData['password'] ?? ''; // Şifre sanitize edilmez, direkt hashlenir/doğrulanır

        if (empty($username) || empty($password)) {
            Session::setFlash('error', 'Kullanıcı adı ve şifre boş bırakılamaz.');
            Helper::redirect(Config::BASE_URL . '/login');
            return;
        }

        $user = $this->userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                // Oturum bilgilerini ayarla
                Session::set('user_id', $user['id']);
                Session::set('user_data', [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'namesurname' => $user['namesurname'],
                    'user_type' => $user['user_type'],
                    'parent_user_id' => $user['parent_user_id']
                ]);
                $this->logger->info("Kullanıcı giriş yaptı: " . $username, ['user_id' => $user['id'], 'ip' => $_SERVER['REMOTE_ADDR']]);

                // Kullanıcı tipine göre yönlendirme
                if ($user['user_type'] === 'admin' || $user['user_type'] === 'staff') {
                    Helper::redirect(Config::BASE_URL . '/admin');
                } else {
                    Helper::redirect(Config::BASE_URL . '/dashboard'); // Alt kullanıcı paneli
                }
            } else {
                Session::setFlash('error', 'Hesabınız aktif değil. Lütfen yöneticinizle iletişime geçin.');
                $this->logger->warning("Pasif kullanıcı giriş denemesi: " . $username, ['ip' => $_SERVER['REMOTE_ADDR']]);
                Helper::redirect(Config::BASE_URL . '/login');
            }
        } else {
            Session::setFlash('error', 'Geçersiz kullanıcı adı veya şifre.');
            $this->logger->warning("Başarısız giriş denemesi: " . $username, ['ip' => $_SERVER['REMOTE_ADDR']]);
            Helper::redirect(Config::BASE_URL . '/login');
        }
    }

    public function logout() {
        $username = Session::getUser('username');
        $userId = Session::getUser('id');
        Session::destroy();
        $this->logger->info("Kullanıcı çıkış yaptı: " . $username, ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR']]);
        Helper::redirect(Config::BASE_URL . '/login');
    }
}