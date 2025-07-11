<?php
// public/index.php
// Uygulamanın Ana Giriş Noktası

// Hata raporlamayı ayarla (Config sınıfından)
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Config::load(); // Konfigürasyonu yükle

// Oturumu başlat
\App\Core\Session::start();

use App\Config;
use App\Core\Session;
use App\Core\Helper;
use App\Controllers\AuthController;

// Güvenlik: CSRF token kontrolü (sadece POST istekleri için)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !Session::verifyCsrfToken($_POST['csrf_token']))) {
    Session::setFlash('error', 'Güvenlik hatası: Geçersiz istek.');
    Helper::redirect(Config::BASE_URL . '/login');
    exit();
}

// Router/Yönlendirici
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUrlPath = parse_url(Config::BASE_URL, PHP_URL_PATH);

// Base URL'yi çıkararak temiz yolu al
$path = substr($requestUri, strlen($baseUrlPath));
$path = trim($path, '/'); // Başındaki ve sonundaki '/' işaretlerini kaldır

// API istekleri doğrudan 'public/api.php' üzerinden işlenecek.
// admin paneli istekleri doğrudan 'public/admin.php' üzerinden işlenecek.
// Bu dosya (index.php) esasen public (kullanıcı) tarafını yönetecek.

switch ($path) {
    case '': // Ana dizine erişim (yani http://localhost/payode/public/)
    case 'login': // Giriş sayfası
        $controller = new AuthController();
        $controller->showLogin();
        break;
    case 'logout': // Çıkış işlemi
        $controller = new AuthController();
        $controller->logout();
        break;
    // Buraya diğer kullanıcıya açık sayfalar eklenebilir (örn: register, dashboard)
    case 'dashboard': // Kullanıcı dashboard'u (eğer admin değilse)
        // Eğer kullanıcı login ise dashboard'a yönlendir, değilse login'e
        if (Session::isLoggedIn()) {
            // Kullanıcı tipine göre yönlendirme yapılabilir (admin ise admin paneli, sub_user ise kendi paneli)
            if (Session::getUser('user_type') === 'admin' || Session::getUser('user_type') === 'staff') {
                Helper::redirect(Config::BASE_URL . '/admin');
            } else {
                // Alt kullanıcı dashboard'u
                echo "Merhaba, alt kullanıcı paneline hoş geldiniz!";
            }
        } else {
            Helper::redirect(Config::BASE_URL . '/login');
        }
        break;
    default:
        // Diğer eşleşmeyen yollar için 404
        header("HTTP/1.0 404 Not Found");
        require_once __DIR__ . '/../app/Views/error/404.php';
        exit();
}