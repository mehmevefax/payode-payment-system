<?php
// app/Views/admin/layout.php
namespace App\Views\Admin;

use App\Config;
use App\Core\Session;

// AdminController veya diğer Controller'lardan gelen veriler
// Örneğin: $data = ['pageTitle' => 'Dashboard', 'currentSection' => 'Yönetici Panosu', 'activeMenu' => 'dashboard', 'activeMethodSlug' => null, 'activeMenuSub' => null];
// Varsayılan değerler
$pageTitle = $data['pageTitle'] ?? Config::APP_NAME . ' Admin Panel';
$currentSection = $data['currentSection'] ?? 'Dashboard'; // Breadcrumb için
$activeMenu = $data['activeMenu'] ?? ''; // Sidebar ana menüsünde hangi öğenin aktif olacağını belirler (örn: 'dashboard', 'deposits')
$activeMethodSlug = $data['activeMethodSlug'] ?? null; // Ödeme yöntemi menüsünde hangi slug'ın aktif olacağını belirler (örn: 'bank_transfer')
$activeMenuSub = $data['activeMenuSub'] ?? null; // Ödeme yöntemi alt menüsünde hangi alt öğenin aktif olacağını belirler (örn: 'deposits', 'withdrawals', 'accounts')

$currentUser = Session::getUser(); // Oturumdaki kullanıcı bilgileri

// Orijinal temanın PHP_SELF mantığını simüle etmek için:
// Bizim sistemimizde 'page' parametresi (örn. admin.php?page=deposits) kullanıldığı için,
// tema içindeki $_SERVER['PHP_SELF'] yerine bu $page değişkenini kullanıyoruz.
// Bu değişken, temadaki eski koşulların çalışmasını sağlar (örn. $page == 'login.php').
$page = $activeMenu . '.php'; // Varsayılan olarak activeMenu'yu dosya adı gibi kullan

// Eğer özel bir durum varsa, $page değişkenini daha spesifik hale getirebiliriz:
if ($activeMenu === 'payment_methods') {
    $page = 'payment-gateways.php';
} elseif ($activeMenu === 'api_settings') {
    $page = 'api-keys.php';
} elseif ($activeMenu === 'settings') {
    $page = 'profile-settings.php'; // Genel ayarlar sayfasının temadaki adı
} elseif ($activeMenu === 'users') {
    $page = 'users.php';
} elseif ($activeMenu === 'permissions') {
    $page = 'roles-permissions.php';
} elseif ($activeMenu === 'reports') {
    $page = 'expenses-report.php'; // Raporlar için bir örnek
} elseif ($activeMenu === 'logs') {
    $page = 'activity.php'; // Loglar için bir örnek
}
// Diğer özel sayfa adları için de benzer şekilde eşleştirme yapılabilir.
// Veya temanın koşullarını bizim sayfa isimlerimize göre uyarlayabiliriz.
?>
<!DOCTYPE html>
<html lang="tr" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="<?= Config::APP_NAME ?> Admin Paneli">
    <meta name="keywords" content="ödeme sistemi, api, ödeme yönetimi, admin panel">
    <meta name="author" content="<?= Config::APP_NAME ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= Config::APP_NAME ?></title>

    <link rel="shortcut icon" type="image/x-icon" href="<?= Config::BASE_URL ?>/assets/img/favicon.png">

    <?php
    $noThemeScriptPages = [
        'layout-horizontal.php', 'layout-detached.php', 'layout-modern.php', 'layout-box.php',
        'layout-two-column.php', 'layout-hovered.php', 'layout-horizontal-single.php',
        'layout-horizontal-overlay.php', 'layout-horizontal-box.php', 'layout-horizontal-sidemenu.php',
        'layout-without-header.php', 'layout-dark.php', 'reset-password-success-3.php',
        'two-step-verification.php', 'two-step-verification-2.php', 'two-step-verification-3.php',
        'under-maintenance.php', '404-error.php', '500-error.php', 'blank-page.php', 'coming-soon.php',
        'login.php', 'login-2.php', 'login-3.php', 'register.php', 'register-2.php', 'register-3.php',
        'forgot-password.php', 'forgot-password-2.php', 'forgot-password-3.php', 'email-verification.php',
        'email-verification-2.php', 'email-verification-3.php', 'lock-screen.php', 'error-500.php',
        'error-404.php', 'success.php', 'success-2.php', 'success-3.php', 'reset-password.php',
        'reset-password-2.php', 'reset-password-3.php', 'job-details.php'
    ];

    if (!in_array($page, $noThemeScriptPages)) { ?>
        <script src="<?= Config::BASE_URL ?>/assets/js/theme-script.js"></script>
    <?php } ?>
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/feather/feather.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/tabler-icons/tabler-icons.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/feather.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/@simonwep/pickr/themes/nano.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/summernote/summernote-lite.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/bootstrap-datetimepicker.min.css">
    <?php if ($page == 'ui-rangeslider.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/ion-rangeslider/css/ion.rangeSlider.min.css">
    <?php } ?>
    <?php if ($page == 'ui-stickynote.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/stickynote/sticky.css">
    <?php } ?>
    <?php if ($page == 'chart-c3.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/c3-chart/c3.min.css">
    <?php } ?>
    <?php if ($page == 'ui-scrollbar.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/scrollbar/scroll.min.css">
    <?php } ?>
    <?php if ($page == 'chart-morris.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/morris/morris.css">
    <?php } ?>
    <?php if ($page == 'form-wizard.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/twitter-bootstrap-wizard/form-wizard.css">
    <?php } ?>
    <?php if ($page == 'icon-flag.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/flags/flags.css">
    <?php } ?>
    <?php if ($page == 'icon-ionic.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/ionic/ionicons.css">
    <?php } ?>
    <?php if ($page == 'icon-material.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/material/materialdesignicons.css">
    <?php } ?>
    <?php if ($page == 'icon-pe7.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/pe7/pe-icon-7.css">
    <?php } ?>
    <?php if ($page == 'icon-simpleline.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/simpleline/simple-line-icons.css">
    <?php } ?>
    <?php if ($page == 'icon-themify.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/themify/themify.css">
    <?php } ?>
    <?php if ($page == 'icon-typicon.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/typicons/typicons.css">
    <?php } ?>
    <?php if ($page == 'icon-weather.php') { ?>
        <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/icons/weather/weathericons.css">
    <?php } ?>
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div id="global-loader">
        <div class="page-loader"></div>
    </div>
    <div class="main-wrapper">
        <div class="header">
            <div class="main-header">
                <div class="header-left">
                    <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="logo">
                        <img src="<?= Config::BASE_URL ?>/assets/img/logo.svg" alt="Logo">
                    </a>
                    <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="dark-logo">
                        <img src="<?= Config::BASE_URL ?>/assets/img/logo-white.svg" alt="Logo">
                    </a>
                </div>
                <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                    <span class="bar-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </a>
                <div class="header-user">
                    <div class="nav user-menu nav-list">
                        <div class="me-auto d-flex align-items-center" id="header-search">
                            <a id="toggle_btn" href="javascript:void(0);" class="btn btn-menubar me-1">
                                <i class="ti ti-arrow-bar-to-left"></i>
                            </a>
                            <div class="input-group input-group-flat d-inline-flex me-1">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="HRMS'de Ara">
                                <span class="input-group-text">
                                    <kbd>CTRL + / </kbd>
                                </span>
                            </div>
                            </div>
                        <div class="me-1">
                            <a href="<?= Config::BASE_URL ?>/admin.php?page=logs" class="btn btn-menubar">
                                <i class="ti ti-mail"></i>
                            </a>
                        </div>
                        <div class="me-1 notification_item">
                            <a href="#" class="btn btn-menubar position-relative me-1" id="notification_popup"
                                data-bs-toggle="dropdown">
                                <i class="ti ti-bell"></i>
                                <span class="notification-status-dot"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown p-4">
                                <div class="d-flex align-items-center justify-content-between border-bottom p-0 pb-3 mb-3">
                                    <h4 class="notification-title">Bildirimler (0)</h4> <div class="d-flex align-items-center">
                                        <a href="#" class="text-primary fs-15 me-3 lh-1">Tümünü okundu işaretle</a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                                <i class="ti ti-calendar-due me-1"></i>Bugün
                                            </a>
                                            <ul class="dropdown-menu mt-2 p-3">
                                                <li><a href="javascript:void(0);" class="dropdown-item rounded-1">Bu Hafta</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item rounded-1">Geçen Hafta</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item rounded-1">Geçen Ay</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="noti-content">
                                    <div class="d-flex flex-column">
                                        <div class="border-bottom mb-3 pb-3">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=logs">
                                                <div class="d-flex">
                                                    <span class="avatar avatar-lg me-2 flex-shrink-0">
                                                        <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-27.jpg" alt="Profile">
                                                    </span>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1"><span class="text-dark fw-semibold">Sistem</span> yeni bir log kaydı oluşturdu.</p>
                                                        <span>Az Önce</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex p-0">
                                    <a href="#" class="btn btn-light w-100 me-2">İptal</a>
                                    <a href="<?= Config::BASE_URL ?>/admin.php?page=logs" class="btn btn-primary w-100">Tümünü Görüntüle</a>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown profile-dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                                <span class="avatar avatar-sm online">
                                    <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-12.jpg" alt="Img" class="img-fluid rounded-circle">
                                </span>
                            </a>
                            <div class="dropdown-menu shadow-none">
                                <div class="card mb-0">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-lg me-2 avatar-rounded">
                                                <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-12.jpg" alt="img">
                                            </span>
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($currentUser['namesurname'] ?? 'Kullanıcı Adı') ?></h5>
                                                <p class="fs-12 fw-medium mb-0"><?= htmlspecialchars($currentUser['email'] ?? 'email@example.com') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <a class="dropdown-item d-inline-flex align-items-center p-0 py-2" href="<?= Config::BASE_URL ?>/admin.php?page=profile">
                                            <i class="ti ti-user-circle me-1"></i>Profilim
                                        </a>
                                        <a class="dropdown-item d-inline-flex align-items-center p-0 py-2" href="<?= Config::BASE_URL ?>/admin.php?page=settings">
                                            <i class="ti ti-settings me-1"></i>Ayarlar
                                        </a>
                                        <a class="dropdown-item d-inline-flex align-items-center p-0 py-2" href="<?= Config::BASE_URL ?>/admin.php?page=logs">
                                            <i class="ti ti-status-change me-1"></i>Hareket Logları
                                        </a>
                                    </div>
                                    <div class="card-footer">
                                        <a class="dropdown-item d-inline-flex align-items-center p-0 py-2" href="<?= Config::BASE_URL ?>/logout">
                                            <i class="ti ti-login me-2"></i>Çıkış Yap
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown mobile-user-menu">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="<?= Config::BASE_URL ?>/admin.php?page=profile">Profilim</a>
                        <a class="dropdown-item" href="<?= Config::BASE_URL ?>/admin.php?page=settings">Ayarlar</a>
                        <a class="dropdown-item" href="<?= Config::BASE_URL ?>/logout">Çıkış Yap</a>
                    </div>
                </div>
                </div>
        </div>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="logo logo-normal">
                    <img src="<?= Config::BASE_URL ?>/assets/img/logo.svg" alt="Logo">
                </a>
                <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="logo-small">
                    <img src="<?= Config::BASE_URL ?>/assets/img/logo-small.svg" alt="Logo">
                </a>
                <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="dark-logo">
                    <img src="<?= Config::BASE_URL ?>/assets/img/logo-white.svg" alt="Logo">
                </a>
            </div>
            <div class="modern-profile p-3 pb-0">
                <div class="text-center rounded bg-light p-3 mb-4 user-profile">
                    <div class="avatar avatar-lg online mb-3">
                        <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-02.jpg" alt="Img" class="img-fluid rounded-circle">
                    </div>
                    <h6 class="fs-12 fw-normal mb-1"><?= htmlspecialchars($currentUser['namesurname'] ?? 'Kullanıcı') ?></h6>
                    <p class="fs-10"><?= htmlspecialchars(ucfirst($currentUser['user_type'] ?? 'Tip')) ?></p>
                </div>
                <div class="sidebar-nav mb-3">
                    <ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent" role="tablist">
                        <li class="nav-item"><a class="nav-link active border-0" href="#">Menü</a></li>
                        <li class="nav-item"><a class="nav-link border-0" href="<?= Config::BASE_URL ?>/admin.php?page=logs">Loglar</a></li>
                    </ul>
                </div>
            </div>
            <div class="sidebar-header p-3 pb-0 pt-2">
                <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile d-flex align-items-center">
                    <div class="avatar avatar-md onlin">
                        <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-02.jpg" alt="Img" class="img-fluid rounded-circle">
                    </div>
                    <div class="text-start sidebar-profile-info ms-2">
                        <h6 class="fs-12 fw-normal mb-1"><?= htmlspecialchars($currentUser['namesurname'] ?? 'Kullanıcı') ?></h6>
                        <p class="fs-10"><?= htmlspecialchars(ucfirst($currentUser['user_type'] ?? 'Tip')) ?></p>
                    </div>
                </div>
                <div class="input-group input-group-flat d-inline-flex mb-4">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Ara...">
                    <span class="input-group-text">
                        <kbd>CTRL + / </kbd>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between menu-item mb-3">
                    <div class="me-3">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard" class="btn btn-menubar">
                            <i class="ti ti-layout-grid-remove"></i>
                        </a>
                    </div>
                    <div class="me-3">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=deposits&status=pending" class="btn btn-menubar position-relative">
                            <i class="ti ti-bell"></i>
                            <span class="badge bg-info rounded-pill d-flex align-items-center justify-content-center header-badge">5</span></a>
                    </div>
                    <div class="me-3 notification-item">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=logs" class="btn btn-menubar position-relative me-1">
                            <i class="ti ti-bell"></i>
                            <span class="notification-status-dot"></span>
                        </a>
                    </div>
                    <div class="me-0">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=logs" class="btn btn-menubar">
                            <i class="ti ti-message"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li class="menu-title"><span>ANA MENÜ</span></li>
                        <li>
                            <ul>
                                <li class="<?= ($activeMenu == 'dashboard' ? 'active' : '') ?>">
                                    <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard">
                                        <i class="ti ti-smart-home"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                
                                <li class="menu-title"><span>ÖDEME YÖNTEMLERİ</span></li>
                                <?php
                                // paymentMethodModel daha önce AdminController'da başlatılmamıştı.
                                // Layout içinde doğrudan model kullanmak yerine, Controller'dan $data array'i ile göndermek daha temizdir.
                                // Ancak hızlı entegrasyon için burada basitçe başlatabiliriz.
                                // NOT: Bu, daha sonra AdminController veya PaymentController'dan çekilen verilere dönüştürülmelidir.
                                $paymentMethodModel = new \App\Models\PaymentMethod();
                                // Aktif ödeme yöntemlerini çek
                                $allPaymentMethods = $paymentMethodModel->getAllPaymentMethods(['is_active' => true], 100, 0); 
                                
                                foreach ($allPaymentMethods as $method):
                                    // Ödeme yöntemine göre ikon seçimi (örnek)
                                    $iconClass = 'ti ti-wallet'; // Varsayılan ikon
                                    if ($method['method_slug'] === 'bank_transfer') $iconClass = 'ti ti-building-bank';
                                    elseif ($method['method_slug'] === 'papara') $iconClass = 'ti ti-brand-cashapp';
                                    elseif ($method['method_slug'] === 'payfix') $iconClass = 'ti ti-device-mobile-message'; // Örnek
                                    elseif ($method['method_slug'] === 'credit_card') $iconClass = 'ti ti-credit-card';
                                    elseif ($method['method_slug'] === 'cryptocurrency') $iconClass = 'ti ti-currency-bitcoin';
                                    elseif ($method['method_slug'] === 'pep') $iconClass = 'ti ti-cash';
                                    elseif ($method['method_slug'] === 'paycell') $iconClass = 'ti ti-mobiledata';
                                    elseif ($method['method_slug'] === 'hayhay') $iconClass = 'ti ti-moneybag';
                                    elseif ($method['method_slug'] === 'parazula') $iconClass = 'ti ti-currency-dollar';
                                    elseif ($method['method_slug'] === 'parolapara') $iconClass = 'ti ti-key';
                                    elseif ($method['method_slug'] === 'kassa') $iconClass = 'ti ti-coin';
                                    elseif ($method['method_slug'] === 'mefete') $iconClass = 'ti ti-wallet'; // Alternatif
                                    elseif ($method['method_slug'] === 'ozanpay') $iconClass = 'ti ti-credit-card-pay';
                                    elseif ($method['method_slug'] === 'payco') $iconClass = 'ti ti-shopping-cart';
                                    elseif ($method['method_slug'] === 'paybol') $iconClass = 'ti ti-credit-card-plus';
                                    elseif ($method['method_slug'] === 'popypara') $iconClass = 'ti ti-circle-dollar';
                                    elseif ($method['method_slug'] === 'swap_transfer') $iconClass = 'ti ti-arrows-left-right';
                                    // Diğer slug'lar için de benzer ikonlar ekleyebilirsin
                                ?>
                                <li class="submenu <?= (isset($activeMethodSlug) && $activeMethodSlug == $method['method_slug'] ? 'active subdrop' : '') ?>">
                                    <a href="javascript:void(0);">
                                        <i class="<?= $iconClass ?>"></i>
                                        <span><?= htmlspecialchars($method['method_name']) ?></span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <?php if ($method['method_type'] === 'deposit' || $method['method_type'] === 'both'): ?>
                                            <li class="<?= (isset($activeMethodSlug) && $activeMethodSlug == $method['method_slug'] && $activeMenuSub == 'deposits' ? 'active' : '') ?>">
                                                <a href="<?= Config::BASE_URL ?>/admin.php?page=deposits&method_slug=<?= htmlspecialchars($method['method_slug']) ?>">
                                                    <?= htmlspecialchars($method['method_name']) ?> Yatırma
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($method['method_type'] === 'withdrawal' || $method['method_type'] === 'both'): ?>
                                            <li class="<?= (isset($activeMethodSlug) && $activeMethodSlug == $method['method_slug'] && $activeMenuSub == 'withdrawals' ? 'active' : '') ?>">
                                                <a href="<?= Config::BASE_URL ?>/admin.php?page=withdrawals&method_slug=<?= htmlspecialchars($method['method_slug']) ?>">
                                                    <?= htmlspecialchars($method['method_name']) ?> Çekme
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li class="<?= (isset($activeMethodSlug) && $activeMethodSlug == $method['method_slug'] && $activeMenuSub == 'accounts' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=accounts&method_slug=<?= htmlspecialchars($method['method_slug']) ?>">
                                                <?= htmlspecialchars($method['method_name']) ?> Hesapları
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <?php endforeach; ?>
                                <li class="menu-title"><span>YÖNETİM</span></li>
                                <li class="submenu <?= (in_array($activeMenu, ['users', 'permissions']) ? 'active subdrop' : '') ?>">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-users"></i><span>Kullanıcı Yönetimi</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="<?= ($activeMenu == 'users' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=users">Kullanıcılar</a>
                                        </li>
                                        <li class="<?= ($activeMenu == 'permissions' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=permissions">Rol & Yetkiler</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="submenu <?= (in_array($activeMenu, ['payment_methods', 'api_settings', 'general_settings']) ? 'active subdrop' : '') ?>">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-settings"></i><span>Sistem Ayarları</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="<?= ($activeMenu == 'payment_methods' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=payment_methods">Ödeme Yöntemleri</a>
                                        </li>
                                        <li class="<?= ($activeMenu == 'api_settings' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=api_settings">API Ayarları</a>
                                        </li>
                                        <li class="<?= ($activeMenu == 'general_settings' ? 'active' : '') ?>">
                                            <a href="<?= Config::BASE_URL ?>/admin.php?page=settings">Genel Ayarlar</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="<?= ($activeMenu == 'reports' ? 'active' : '') ?>">
                                    <a href="<?= Config::BASE_URL ?>/admin.php?page=reports">
                                        <i class="ti ti-chart-bar"></i><span>Raporlar</span>
                                    </a>
                                </li>
                                <li class="<?= ($activeMenu == 'logs' ? 'active' : '') ?>">
                                    <a href="<?= Config::BASE_URL ?>/admin.php?page=logs">
                                        <i class="ti ti-list"></i><span>Log Kayıtları</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0"><?= htmlspecialchars($pageTitle) ?></h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Ana Sayfa</a></li>
                                    <li class="breadcrumb-item active"><?= htmlspecialchars($currentSection) ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <?= $content ?? '' ?>
            </div>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> © <?= Config::APP_NAME ?>.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Design & Develop by Your Company Name
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            </div>
        </div>
    <script src="<?= Config::BASE_URL ?>/assets/js/jquery-3.7.1.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/feather.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/jquery.slimscroll.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/summernote/summernote-lite.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/@simonwep/pickr/pickr.es5.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/jquery.dataTables.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/moment.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/daterangepicker/daterangepicker.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/bootstrap-datetimepicker.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/select2/js/select2.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/apexchart/apexcharts.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/apexchart/chart-data.js"></script>

    <?php if ($page == 'ui-counter.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/countup/jquery.counterup.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/countup/jquery.waypoints.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/countup/jquery.missofis-countdown.js"></script>
    <?php } ?>
    <?php if ($page == 'form-wizard.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/twitter-bootstrap-wizard/prettify.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/twitter-bootstrap-wizard/form-wizard.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-drag-drop.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/dragula/js/dragula.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/dragula/js/drag-drop.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/dragula/js/draggable-cards.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-rating.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/raty/jquery.raty.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/raty/custom.raty.js"></script>
    <?php } ?>
    <?php if ($page == 'form-validation.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/js/form-validation.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-lightbox.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/lightbox/glightbox.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/lightbox/lightbox.js"></script>
    <?php } ?>
    <?php if ($page == 'chart-c3.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/c3-chart/d3.v5.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/c3-chart/c3.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/c3-chart/chart-data.js"></script>
    <?php } ?>
    <?php if ($page == 'chart-flot.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/flot/jquery.flot.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/flot/jquery.flot.fillbetween.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/flot/jquery.flot.pie.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/flot/chart-data.js"></script>
    <?php } ?>
    <?php if ($page == 'chart-js.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/chartjs/chart.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/chartjs/chart-data.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-rangeslider.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/ion-rangeslider/js/ion.rangeSlider.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/ion-rangeslider/js/custom-rangeslider.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-scrollbar.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/scrollbar/scrollbar.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/scrollbar/custom-scroll.js"></script>
    <?php } ?>
    <?php if ($page == 'ui-stickynote.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/js/jquery-ui.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/stickynote/sticky.js"></script>
    <?php } ?>
    <?php if ($page == 'chart-morris.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/morris/raphael-min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/morris/morris.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/morris/chart-data.js"></script>
    <?php } ?>
    <?php if ($page == 'chart-peity.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/peity/jquery.peity.min.js"></script>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/peity/chart-data.js"></script>
    <?php } ?>
    <?php if ($page == 'form-fileupload.php') { ?>
        <script src="<?= Config::BASE_URL ?>/assets/plugins/fileupload/fileupload.min.js"></script>
    <?php } ?>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/plugins/theia-sticky-sidebar/ResizeSensor.min.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/script.js"></script>
    <script src="<?= Config::BASE_URL ?>/assets/js/theme-colorpicker.js"></script>
    <script>
        $(document).on('click', '.btnonay', function() {
            const islemID = $(this).data('islemid');
            if (confirm('Bu işlemi onaylamak istediğinize emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=approve',
                    data: { islemID: islemID },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Hata: ' + response.message + (response.error_code ? ' (Code: ' + response.error_code + ')' : ''));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error, xhr.responseText);
                        alert('Bir hata oluştu: ' + xhr.responseText);
                    }
                });
            }
        });

        $(document).on('click', '.btnred', function() {
            const islemID = $(this).data('islemid');
            const reason = prompt('Reddetme sebebini giriniz:');
            if (reason !== null && reason.trim() !== '') {
                if (confirm('Bu işlemi reddetmek istediğinize emin misiniz?')) {
                    $.ajax({
                        type: 'POST',
                        url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=reject',
                        data: { islemID: islemID, reason: reason },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Hata: ' + response.message + (response.error_code ? ' (Code: ' + response.error_code + ')' : ''));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error: ", status, error, xhr.responseText);
                            alert('Bir hata oluştu: ' + xhr.responseText);
                        }
                    });
                }
            } else if (reason === '') {
                alert('Reddetme sebebi boş bırakılamaz.');
            }
        });
    </script>
</body>
</html>