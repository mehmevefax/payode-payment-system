<?php
// app/Views/admin/accounts/list.php
namespace App\Views\Admin\Accounts;

use App\Config;
use App\Core\Session;
use App\Models\PaymentMethod; // Ödeme yöntemlerini çekmek için
use App\Models\User; // Kullanıcıları çekmek için

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $accounts, $pagination, $filters, $pageTitle, $currentSection, $activeMenu, $activeMethodSlug, $activeMenuSub
// $active_accounts_count, $inactive_accounts_count, $user_linked_accounts_count
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .account-list-section {
        padding: 0px 20px 20px 20px; /* İçeriğe genel boşluk */
        margin-top: 0px; /* Üstteki boşluğu ayarla */
    }
    .page-breadcrumb {
        margin-bottom: 20px !important;
    }
    .main-card {
        margin-bottom: 20px;
    }

    /* Tablo ve kapsayıcıları için doğrudan ve güçlü CSS kuralları */
    .account-table-wrapper {
        overflow-x: auto; /* Yatay kaydırma çubuğu */
        margin-top: 20px;
        border: 1px solid #dee2e6; /* Bootstrap table border */
        border-radius: 0.25rem; /* Bootstrap border-radius */
        box-sizing: border-box; /* padding ve border genişlik içinde olsun */
    }
    .account-table {
        width: 100% !important; /* Temanın diğer stillerini ezmek için */
        min-width: 1100px; /* Sütunların sığması için minimum genişlik, biraz artırıldı */
        border-collapse: collapse;
        table-layout: fixed; /* Sütun genişliklerini sabitle */
    }
    .account-table th, .account-table td {
        white-space: nowrap; /* Metinlerin satır atlamasını engelle */
        padding: 12px 15px; /* Daha fazla padding */
        vertical-align: middle;
        border: 1px solid #dee2e6; /* Bootstrap table cell border */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .account-table th {
        background-color: #f8f9fa; /* Başlık arka planı */
        font-weight: 600; /* Kalın font */
        text-align: left;
        color: #495057; /* Koyu gri yazı */
    }
    .account-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc; /* Tek satırlar için hafif arka plan */
    }
    .account-table tbody tr:hover {
        background-color: #f0f0f0; /* Hover efekti */
    }

    /* Checkbox ve ikonlar için hizalama */
    .account-table th:first-child,
    .account-table td:first-child {
        width: 40px; /* Checkbox sütunu genişliği */
        text-align: center;
    }
    .account-table th:last-child,
    .account-table td:last-child {
        width: 150px; /* İşlemler sütunu genişliği */
        text-align: center;
    }
    /* Diğer sütunlara varsayılan eşit genişlik, veya ihtiyaç olursa ayarla */
    .account-table th:nth-child(2) { width: 150px; } /* Hesap Adı */
    .account-table th:nth-child(3) { width: 120px; } /* Yöntem */
    .account-table th:nth-child(4) { width: 250px; } /* Hesap Detayları (daha geniş) */
    .account-table th:nth-child(5) { width: 120px; } /* Kullanıcı */
    .account-table th:nth-child(6) { width: 100px; } /* Durum */
    .account-table th:nth-child(7) { width: 150px; } /* Oluşturulma Tarihi */

    /* Action butonları */
    .action-icon .btn {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
        min-width: 30px;
    }
    .action-icon .btn i {
        font-size: 1rem;
    }
    .action-icon .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: white; }
    .action-icon .btn-primary { background-color: #007bff; border-color: #007bff; color: white; }
    .action-icon .btn-success { background-color: #28a745; border-color: #28a745; color: white; }
    .action-icon .btn-danger { background-color: #dc3545; border-color: #dc3545; color: white; }
    .action-icon .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: white; }

    /* Badge stilleri */
    .badge {
        font-size: 0.8em;
        padding: .4em .7em;
        border-radius: .25rem;
    }
    .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
    .badge.bg-info { background-color: #17a2b8 !important; color: #fff !important; }

    /* Filtre ve Arama Alanları */
    .filter-section-card {
        background-color: #ffffff;
        padding: 15px;
        border-radius: 0.25rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }
    .filter-section-card .form-control,
    .filter-section-card .btn {
        height: 38px;
    }

    /* Modal form elemanları */
    .modal-body .form-control, .modal-body .select2-container .select2-selection--single {
        height: 45px; /* Modal inputlarının yüksekliğini ayarla */
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    .modal-body .form-label {
        font-weight: 500;
        margin-bottom: 8px;
    }
    /* Temanın varsayılan input stillerini ezmek için */
    .h-\[55px\] { height: 45px !important; } /* Daha küçük ve Bootstrap'e uyumlu */
    .px-\[17px\] { padding-left: 1rem !important; padding-right: 1rem !important; } /* Bootstrap padding */
    .rounded-md { border-radius: 0.25rem !important; } /* Bootstrap border-radius */
    .border-gray-200 { border-color: #dee2e6 !important; } /* Bootstrap border-color */
    .bg-white { background-color: #ffffff !important; }

    /* Modal footer butonları */
    .modal-footer .btn {
        min-width: 100px;
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
</style>

<div class="account-list-section">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1"><?= htmlspecialchars($pageTitle) ?></h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard"><i class="ti ti-smart-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">
                        Ödeme İşlemleri
                    </li>
                    <?php if (!empty($filters['method_slug'])): ?>
                        <li class="breadcrumb-item">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $filters['method_slug']))) ?>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($currentSection) ?></li>
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
                        <li><a href="<?= Config::BASE_URL ?>/admin.php?page=accounts&action=export_pdf&<?= http_build_query($filters) ?>" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>PDF Olarak Aktar</a></li>
                        <li><a href="<?= Config::BASE_URL ?>/admin.php?page=accounts&action=export_excel&<?= http_build_query($filters) ?>" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Excel Olarak Aktar</a></li>
                    </ul>
                </div>
            </div>
            <div class="mb-2">
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_account_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni Hesap Ekle</a>
            </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                    <i class="ti ti-chevrons-up"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill main-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-primary flex-shrink-0">
                            <i class="ti ti-wallet fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Toplam Hesap</p>
                            <h4><?= $pagination['total_records'] ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill main-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-success flex-shrink-0">
                            <i class="ti ti-check-circle fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Aktif Hesap</p>
                            <h4><?= $active_accounts_count ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill main-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-danger flex-shrink-0">
                            <i class="ti ti-x-circle fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Pasif Hesap</p>
                            <h4><?= $inactive_accounts_count ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill main-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-info flex-shrink-0">
                            <i class="ti ti-user-plus fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Kullanıcı Hesapları</p>
                            <h4><?= $user_linked_accounts_count ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
            <h5>Hesaplar Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center filter-form-container">
                    <input type="hidden" name="page" value="accounts">
                    <input type="hidden" name="method_slug" value="<?= htmlspecialchars($filters['method_slug'] ?? '') ?>">

                    <div class="me-3">
                        <select class="form-control" name="sayfada" onchange="this.form.submit()">
                            <option value="10" <?= (($filters['sayfada'] ?? 10) == 10 ? 'selected' : '') ?>>10</option>
                            <option value="20" <?= (($filters['sayfada'] ?? 10) == 20 ? 'selected' : '') ?>>20</option>
                            <option value="50" <?= (($filters['sayfada'] ?? 10) == 50 ? 'selected' : '') ?>>50</option>
                            <option value="100" <?= (($filters['sayfada'] ?? 10) == 100 ? 'selected' : '') ?>>100</option>
                        </select>
                    </div>
                    <div class="me-3">
                        <input type="text" name="search" class="form-control" placeholder="Ara..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                    <div class="me-3">
                        <select class="form-control" name="status">
                            <option value="">Tüm Durumlar</option>
                            <option value="1" <?= (($filters['is_active'] ?? '') == '1' ? 'selected' : '') ?>>Aktif</option>
                            <option value="0" <?= (($filters['is_active'] ?? '') == '0' ? 'selected' : '') ?>>Pasif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive-custom">
                <table class="table table-hover table-striped custom-styled-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-main-table">
                                </div>
                            </th>
                            <th>Hesap Adı</th>
                            <th>Yöntem</th>
                            <th>Hesap Detayları</th>
                            <th>Kullanıcı</th>
                            <th>Durum</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($accounts)): ?>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $account['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                                    <td><?= htmlspecialchars($account['payment_method_name'] ?? 'Bilinmiyor') ?></td>
                                    <td>
                                        <pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 0.8em; margin: 0;"><?= htmlspecialchars(json_encode(json_decode($account['account_details'], true), JSON_PRETTY_PRINT)) ?></pre>
                                    </td>
                                    <td><?= htmlspecialchars($account['user_username'] ?? 'Sistem Hesabı') ?></td>
                                    <td>
                                        <?php
                                            $statusClass = $account['is_active'] ? 'badge bg-success' : 'badge bg-danger';
                                            $statusText = $account['is_active'] ? 'Aktif' : 'Pasif';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= htmlspecialchars($statusText) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($account['created_at']))) ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-info view-account-detail" data-bs-toggle="modal" data-bs-target="#account_detail_modal" data-id="<?= $account['id'] ?>" title="Detay Görüntüle"><i class="ti ti-eye"></i></a>
                                            <a href="#" class="me-2 btn btn-sm btn-primary edit-account-btn" data-bs-toggle="modal" data-bs-target="#edit_account_modal" data-id="<?= $account['id'] ?>" title="Düzenle"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-account-btn" data-id="<?= $account['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_account_modal" title="Sil"><i class="ti ti-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Kayıt bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end p-3">
                <ul class="pagination mb-0">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=accounts&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['is_active'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=accounts&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['is_active'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=accounts&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['is_active'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_account_modal" tabindex="-1" aria-labelledby="add_account_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_account_modal_label">Yeni Hesap Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_account_form" action="<?= Config::BASE_URL ?>/admin.php?page=accounts&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Banka <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="banka" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">IBAN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="iban" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şube Kodu</label>
                                <input type="text" class="form-control" name="sube_kodu">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ad_soyad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hesap No</label>
                                <input type="text" class="form-control" name="hesap_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Tutar</label>
                                <input type="number" step="0.01" class="form-control" name="min_tutar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Tutar</label>
                                <input type="number" step="0.01" class="form-control" name="max_tutar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum <span class="text-danger">*</span></label>
                                <select name="aktif" class="form-control" required>
                                    <option value="1">Aktif</option>
                                    <option value="3">Beklemede</option>
                                    <option value="4">Manuel</option>
                                    <option value="0">Pasif</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ödeme Yöntemi (Hesap Tipi) <span class="text-danger">*</span></label>
                                <select name="method_id" class="form-control select2" required>
                                    <option value="">Seçiniz</option>
                                    <?php
                                    $paymentMethodModel = new \App\Models\PaymentMethod();
                                    $allMethods = $paymentMethodModel->getAllPaymentMethods([], 100, 0);
                                    foreach ($allMethods as $method) {
                                        echo '<option value="' . $method['id'] . '">' . htmlspecialchars($method['method_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Bu hesabın hangi ödeme yöntemine ait olduğunu seçiniz.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı (Opsiyonel)</label>
                                <select name="user_id" class="form-control select2">
                                    <option value="">Sistem Hesabı (Yok)</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allUsers = $userModel->getAllUsers([], 9999, 0);
                                    foreach ($allUsers as $user) {
                                        echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Bu hesap belirli bir kullanıcıya aitse seçiniz.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button data-bs-dismiss="modal" type="button" class="btn btn-light me-2">Kapat</button>
                        <button id="add-btn" name="ekle" type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="account_detail_modal" tabindex="-1" aria-labelledby="account_detail_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="account_detail_modal_label">Hesap Detayı</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="account_detail_content">
                        <p class="text-center">Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit_account_modal" tabindex="-1" aria-labelledby="edit_account_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_account_modal_label">Hesabı Düzenle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="edit_account_form" action="<?= Config::BASE_URL ?>/admin.php?page=accounts&action=edit" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_account_id">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Banka <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="banka" id="edit_banka" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">IBAN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="iban" id="edit_iban" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şube Kodu</label>
                                <input type="text" class="form-control" name="sube_kodu" id="edit_sube_kodu">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ad_soyad" id="edit_ad_soyad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hesap No</label>
                                <input type="text" class="form-control" name="hesap_no" id="edit_hesap_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Tutar</label>
                                <input type="number" step="0.01" class="form-control" name="min_tutar" id="edit_min_tutar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Tutar</label>
                                <input type="number" step="0.01" class="form-control" name="max_tutar" id="edit_max_tutar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum <span class="text-danger">*</span></label>
                                <select name="is_active" id="edit_is_active" class="form-control" required>
                                    <option value="1">Aktif</option>
                                    <option value="3">Beklemede</option>
                                    <option value="4">Manuel</option>
                                    <option value="0">Pasif</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ödeme Yöntemi (Hesap Tipi) <span class="text-danger">*</span></label>
                                <select name="method_id" id="edit_method_id_select2" class="form-control select2" required>
                                    <option value="">Seçiniz</option>
                                    <?php
                                    $paymentMethodModel = new \App\Models\PaymentMethod();
                                    $allMethods = $paymentMethodModel->getAllPaymentMethods([], 100, 0);
                                    foreach ($allMethods as $method) {
                                        echo '<option value="' . $method['id'] . '">' . htmlspecialchars($method['method_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı (Opsiyonel)</label>
                                <select name="user_id" id="edit_user_id_select2" class="form-control select2">
                                    <option value="">Sistem Hesabı (Yok)</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allUsers = $userModel->getAllUsers([], 9999, 0);
                                    foreach ($allUsers as $user) {
                                        echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="delete_account_modal" tabindex="-1" aria-labelledby="delete_account_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_account_modal_label">Hesabı Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu hesabı sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-account-btn">Evet, Sil</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

<?php $content = ob_get_clean(); ?>
<?php require_once __DIR__ . '/../layout.php'; ?>

<script>
$(document).ready(function() {
    // Select2 kütüphanesini başlat
    if ($.fn.select2) {
        // Add ve Edit modal'ları için ayrı ayrı parent ayarı
        $('#add_account_modal .select2').select2({
            dropdownParent: $('#add_account_modal'),
            width: '100%'
        });
        $('#edit_account_modal .select2').select2({
            dropdownParent: $('#edit_account_modal'),
            width: '100%'
        });
    }

    // Add Account Form Submit
    $(document).on('submit', '#add_account_form', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const formData = form.serialize();

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#add_account_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'İşlem başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('İşlem sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // View Account Detail
    $(document).on('click', '.view-account-detail', function() {
        const accountId = $(this).data('id');
        $('#account_detail_content').html('<p class="text-center">Yükleniyor...</p>');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=accounts&action=view_detail',
            type: 'GET',
            data: { id: accountId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    // Account details'tan özel alanları ayrıştır
                    const banka = detail.account_details.banka || 'Yok';
                    const iban = detail.account_details.iban || 'Yok';
                    const subeKodu = detail.account_details.sube_kodu || 'Yok';
                    const adSoyad = detail.account_details.ad_soyad || 'Yok';
                    const hesapNo = detail.account_details.hesap_no || 'Yok';
                    const minTutar = detail.account_details.min_tutar || 'Yok';
                    const maxTutar = detail.account_details.max_tutar || 'Yok';
                    
                    const isActiveText = detail.is_active ? 'Aktif' : 'Pasif';
                    const statusClass = detail.is_active ? 'bg-success' : 'bg-danger';

                    let htmlContent = `
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center rounded bg-light p-3 mb-3">
                                <div>
                                    <p class="text-gray-9 fw-medium mb-0">Hesap Adı: ${detail.account_name}</p>
                                    <p class="mb-0">Yöntem: ${detail.payment_method_name}</p>
                                </div>
                                <span class="badge ${statusClass}"><i class="ti ti-point-filled"></i>${isActiveText}</span>
                            </div>
                            <p class="text-gray-9 fw-medium">Banka Hesap Detayları</p>
                            <div class="pb-1 border-bottom mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Banka</p>
                                        <p class="text-gray-9">${banka}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">IBAN</p>
                                        <p class="text-gray-9">${iban}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Şube Kodu</p>
                                        <p class="text-gray-9">${subeKodu}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Ad Soyad</p>
                                        <p class="text-gray-9">${adSoyad}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Hesap No</p>
                                        <p class="text-gray-9">${hesapNo}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Min Tutar</p>
                                        <p class="text-gray-9">${minTutar}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Max Tutar</p>
                                        <p class="text-gray-9">${maxTutar}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-gray-9 fw-medium">Genel Bilgiler</p>
                            <div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Bağlı Kullanıcı</p>
                                        <p class="text-gray-9">${detail.user_username || 'Sistem Hesabı'}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Oluşturulma Tarihi</p>
                                        <p class="text-gray-9">${detail.created_at_formatted}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#account_detail_content').html(htmlContent);
                } else {
                    $('#account_detail_content').html('<p class="text-danger text-center">' + (response.message || 'Detaylar yüklenirken bir hata oluştu.') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                $('#account_detail_content').html('<p class="text-danger text-center">Detaylar yüklenemedi. Sunucu hatası.</p>');
            }
        });
    });

    // Edit Account - Modalı doldur
    $(document).on('click', '.edit-account-btn', function() {
        const accountId = $(this).data('id');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=accounts&action=view_detail',
            type: 'GET',
            data: { id: accountId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    // Form alanlarını doldur
                    $('#edit_account_id').val(detail.id);
                    $('#edit_account_name').val(detail.account_name); // Genel Hesap Adı
                    $('#edit_method_id_select2').val(detail.method_id).trigger('change.select2');
                    $('#edit_user_id_select2').val(detail.user_id).trigger('change.select2');
                    $('#edit_is_active').val(detail.is_active ? '1' : '0');

                    // Özel JSON alanlarını doldur
                    $('#edit_banka').val(detail.account_details.banka || '');
                    $('#edit_iban').val(detail.account_details.iban || '');
                    $('#edit_sube_kodu').val(detail.account_details.sube_kodu || '');
                    $('#edit_ad_soyad').val(detail.account_details.ad_soyad || '');
                    $('#edit_hesap_no').val(detail.account_details.hesap_no || '');
                    $('#edit_min_tutar').val(detail.account_details.min_tutar || '');
                    $('#edit_max_tutar').val(detail.account_details.max_tutar || '');
                    
                    $('#edit_account_modal').modal('show');
                } else {
                    alert('Hata: ' + (response.message || 'Hesap detayları yüklenemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Hesap detayları yüklenirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Edit Account Form Submit
    $(document).on('submit', '#edit_account_form', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const formData = form.serialize(); // Form verilerini al

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#edit_account_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Değişiklikler kaydedilemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Değişiklikler kaydedilirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Delete Account - ID'yi modala aktar
    let accountToDeleteId = 0;
    $(document).on('click', '.delete-account-btn', function() {
        accountToDeleteId = $(this).data('id');
    });

    // Delete Account - Onay butonu AJAX
    $(document).on('click', '.confirm-delete-account-btn', function() {
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=accounts&action=delete',
            type: 'POST',
            data: { id: accountToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#delete_account_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Hesap silme işlemi başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Hesap silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Genel tablolarda "select all" checkbox'ı
    $(document).on('click', '#select-all-main-table', function() {
        var isChecked = this.checked;
        $('table.account-table tbody input[type="checkbox"]').each(function() {
            this.checked = isChecked;
        });
    });
});
</script>