<?php
// app/Views/admin/dashboard/index.php
// Bu dosya, admin panelinin Dashboard sayfasının HTML içeriğini içerir.
// $data['user'] gibi değişkenler Controller'dan buraya gönderilir.

use App\Config;
use App\Core\Session;

// Admin Controller'dan gelen veriler
$pageTitle = $data['pageTitle'] ?? 'Yönetici Panosu';
$currentSection = $data['currentSection'] ?? 'Dashboard';
$currentUser = Session::getUser();

// Orijinal temanın PHP_SELF mantığını simüle etmek için (layout.php'de kullanılıyor)
$activeMenu = 'dashboard';

// Çıktı tamponlamayı başlat, böylece içeriği layout'a gönderebiliriz
ob_start();
?>

<div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
    <div class="my-auto mb-2">
        <h2 class="mb-1">Yönetici Panosu</h2>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard"><i class="ti ti-smart-home"></i></a>
                </li>
                <li class="breadcrumb-item">
                    Dashboard
                </li>
                <li class="breadcrumb-item active" aria-current="page">Yönetici Panosu</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
        <div class="me-2 mb-2">
            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                    <i class="ti ti-file-export me-1"></i>Dışa Aktar
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3">
                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>PDF Olarak Aktar</a></li>
                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Excel Olarak Aktar</a></li>
                </ul>
            </div>
        </div>
        <div class="mb-2">
            <div class="input-icon w-120 position-relative">
                <span class="input-icon-addon">
                    <i class="ti ti-calendar text-gray-9"></i>
                </span>
                <input type="text" class="form-control yearpicker" value="<?= date('Y') ?>">
            </div>
        </div>
        <div class="ms-2 head-icons">
            <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                <i class="ti ti-chevrons-up"></i>
            </a>
        </div>
    </div>
</div>
<div class="card border-0">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap pb-1">
        <div class="d-flex align-items-center mb-3">
            <span class="avatar avatar-xl flex-shrink-0">
                <img src="<?= Config::BASE_URL ?>/assets/img/profiles/avatar-31.jpg" class="rounded-circle" alt="img">
            </span>
            <div class="ms-3">
                <h3 class="mb-2">Tekrar Hoş Geldin, <?= htmlspecialchars($currentUser['namesurname'] ?? 'Admin') ?> <a href="javascript:void(0);" class="edit-icon"><i class="ti ti-edit fs-14"></i></a></h3>
                <p>Bekleyen <span class="text-primary text-decoration-underline"><?= $data['pending_deposits_count'] ?? 0 ?></span> Onayın ve <span class="text-primary text-decoration-underline"><?= $data['pending_withdrawals_count'] ?? 0 ?></span> Reddedilecek Talebin var</p>
                </div>
        </div>
        <div class="d-flex align-items-center flex-wrap mb-1">
            <a href="<?= Config::BASE_URL ?>/admin.php?page=deposits&status=pending" class="btn btn-secondary btn-md me-2 mb-2"><i class="ti ti-square-rounded-plus me-1"></i>Onay Bekleyenler</a>
            <a href="<?= Config::BASE_URL ?>/admin.php?page=withdrawals&status=pending" class="btn btn-primary btn-md mb-2"><i class="ti ti-square-rounded-plus me-1"></i>Çekim Talepleri</a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xxl-8 d-flex">
        <div class="row flex-fill">
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-primary mb-2">
                            <i class="ti ti-cash-banknote fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Toplam Yatırma İşlemi</h6>
                        <h3 class="mb-3"><?= $data['total_deposits_count'] ?? 0 ?> <span class="fs-12 fw-medium text-success"><i class="fa-solid fa-caret-up me-1"></i>+X%</span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=deposits" class="link-default">Tümünü Görüntüle</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-secondary mb-2">
                            <i class="ti ti-cash-banknote-off fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Toplam Çekim İşlemi</h6>
                        <h3 class="mb-3"><?= $data['total_withdrawals_count'] ?? 0 ?> <span class="fs-12 fw-medium text-danger"><i class="fa-solid fa-caret-down me-1"></i>-Y%</span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=withdrawals" class="link-default">Tümünü Görüntüle</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-info mb-2">
                            <i class="ti ti-users-group fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Toplam Kullanıcı Sayısı</h6>
                        <h3 class="mb-3"><?= $data['total_users_count'] ?? 0 ?> <span class="fs-12 fw-medium text-success"><i class="fa-solid fa-caret-up me-1"></i>+Z%</span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=users" class="link-default">Tüm Kullanıcılar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-pink mb-2">
                            <i class="ti ti-chart-bar fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Onaylanan Yatırma Miktarı</h6>
                        <h3 class="mb-3">$<?= number_format($data['total_approved_deposits_amount'] ?? 0, 2) ?> <span class="fs-12 fw-medium text-success"><i class="fa-solid fa-caret-up me-1"></i>+A%</span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=reports" class="link-default">Raporları Görüntüle</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-purple mb-2">
                            <i class="ti ti-currency-dollar fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Onaylanan Çekim Miktarı</h6>
                        <h3 class="mb-3">$<?= number_format($data['total_approved_withdrawals_amount'] ?? 0, 2) ?> <span class="fs-12 fw-medium text-danger"><i class="fa-solid fa-caret-down me-1"></i>-B%</span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=reports" class="link-default">Raporları Görüntüle</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-danger mb-2">
                            <i class="ti ti-clock-hour-4 fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Ort. Onay Süresi (sn)</h6>
                        <h3 class="mb-3"><?= number_format($data['avg_processing_time'] ?? 0, 1) ?> <span class="fs-12 fw-medium text-success"><i class="fa-solid fa-caret-up me-1"></i></span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=reports" class="link-default">Raporları Görüntüle</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-success mb-2">
                            <i class="ti ti-credit-card fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Aktif Ödeme Yöntemleri</h6>
                        <h3 class="mb-3"><?= $data['active_payment_methods_count'] ?? 0 ?> / <?= $data['total_payment_methods_count'] ?? 0 ?> <span class="fs-12 fw-medium text-success"><i class="fa-solid fa-caret-up me-1"></i></span></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=payment_methods" class="link-default">Tümünü Yönet</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <span class="avatar rounded-circle bg-dark mb-2">
                            <i class="ti ti-file-text fs-16"></i>
                        </span>
                        <h6 class="fs-13 fw-medium text-default mb-1">Son Log Kaydı</h6>
                        <h3 class="mb-3"><?= $data['last_log_time'] ?? 'Yok' ?></h3>
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=logs" class="link-default">Tüm Loglar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

<?php
$content = ob_get_clean(); // Tamponlanan içeriği al
require_once __DIR__ . '/../layout.php'; // Layout dosyasını dahil et ve içeriği enjekte et
?>