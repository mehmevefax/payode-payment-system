<?php
// public/admin.php
// Admin Paneli Ana Giriş Noktası

// Hata raporlamayı aç (geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Otomatik sınıf yükleme (Autoloader)
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Konfigürasyonu yükle (DB bilgileri, BASE_URL vb.)
\App\Config::load();

// Oturumu başlat
\App\Core\Session::start();

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\PaymentController;
use App\Controllers\UserController;
use App\Controllers\SettingController;
use App\Controllers\ReportController;

// Oturum açık değilse veya admin/staff rolünde değilse giriş sayfasına yönlendir
if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
    Helper::redirect(Config::BASE_URL . '/login');
    exit();
}

// Admin Controller örneklerini burada başlat
$adminController = new AdminController();
$authController = new AuthController();
$paymentController = new PaymentController();
$userController = new UserController();
$settingController = new SettingController();
$reportController = new ReportController();

// URL'ye göre sayfa yönlendirme için gerekli değişkenleri tanımla
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$methodSlug = $_GET['method_slug'] ?? null; // Menü aktifliği için

switch ($page) {
    case 'dashboard':
        $adminController->showDashboard();
        break;
    case 'deposits':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $paymentController->addDeposit($_POST);
            } elseif ($action === 'delete') {
                $paymentController->deleteDeposit($_POST);
            } else { // Onay/Red aksiyonları
                $paymentController->processDepositAction($_POST);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'view_detail') {
                $paymentController->viewDepositDetail($_GET);
            } elseif ($action === 'export_pdf') {
                $paymentController->exportDeposits($_GET, 'pdf');
            } elseif ($action === 'export_excel') {
                $paymentController->exportDeposits($_GET, 'csv'); // Şimdilik Excel yerine CSV
            } else {
                $paymentController->listDeposits($_GET);
            }
        }
        break;
    case 'withdrawals':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Buraya add, delete ve edit aksiyonları eklenecek
            // if ($action === 'add') { $paymentController->addWithdrawal($_POST); }
            // elseif ($action === 'delete') { $paymentController->deleteWithdrawal($_POST); }
            // elseif ($action === 'edit') { $paymentController->editWithdrawal($_POST); }
            // else { $paymentController->processWithdrawalAction($_POST); }
            $paymentController->processWithdrawalAction($_POST); // Şimdilik sadece onay/red
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // if ($action === 'view_detail') { $paymentController->viewWithdrawalDetail($_GET); }
            // elseif ($action === 'export_pdf') { $paymentController->exportWithdrawals($_GET, 'pdf'); }
            // elseif ($action === 'export_excel') { $paymentController->exportWithdrawals($_GET, 'csv'); }
            // else { $paymentController->listWithdrawals($_GET); }
            $paymentController->listWithdrawals($_GET); // Şimdilik sadece listeleme
        }
        break;
    case 'accounts':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $paymentController->addAccount($_POST); // Yeni ekleme aksiyonu
            } elseif ($action === 'edit') {
                $paymentController->editAccount($_POST); // Yeni düzenleme aksiyonu
            } elseif ($action === 'delete') {
                $paymentController->deleteAccount($_POST); // Yeni silme aksiyonu
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'view_detail') {
            $paymentController->viewAccountDetail($_GET); // Yeni detay aksiyonu
        } else {
            $paymentController->listAccounts($_GET); // Hesapları listeleme
        }
        break;
    case 'users':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add' || $action === 'edit' || $action === 'delete') {
                $userController->processUserAction($_POST);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'view_detail') {
            $userController->viewUserDetail($_GET);
        } else {
            $userController->listUsers($_GET);
        }
        break;
    case 'permissions':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $userController->addPermission($_POST);
            } elseif ($action === 'edit') {
                $userController->editPermission($_POST);
            } elseif ($action === 'delete') {
                $userController->deletePermission($_POST);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'view_detail') {
            $userController->viewPermissionDetail($_GET);
        } else {
            $userController->listPermissions($_GET);
        }
        break;
    case 'payment_methods':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $settingController->addPaymentMethod($_POST);
            } elseif ($action === 'edit') {
                $settingController->editPaymentMethod($_POST);
            } elseif ($action === 'delete') {
                $settingController->deletePaymentMethod($_POST);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'view_detail') {
            $settingController->viewPaymentMethodDetail($_GET);
        } else {
            $settingController->listPaymentMethods($_GET);
        }
        break;
    case 'api_settings':
        $settingController->listApiCredentials($_GET);
        break;
    case 'settings':
        $settingController->showGeneralSettings($_POST);
        break;
    case 'reports':
        $reportController->showReports($_GET);
        break;
    case 'logs':
        $adminController->showLogs($_GET);
        break;
    case 'logout':
        $authController->logout();
        break;
    default:
        // Eğer page parametresi yoksa veya eşleşmiyorsa dashboard'a yönlendir.
        // Bu, doğrudan public/admin'e erişildiğinde boş sayfa göstermeyi engeller.
        // Helper::redirect(Config::BASE_URL . '/admin.php?page=dashboard');
        // exit();

        // Alternatif olarak, 404 sayfası gösterilebilir.
        header("HTTP/1.0 404 Not Found");
        require_once __DIR__ . '/../app/Views/error/404.php';
        exit();
}