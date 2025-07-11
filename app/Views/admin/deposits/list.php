<?php
// app/Views/admin/deposits/list.php
namespace App\Views\Admin\Deposits; // Bu isim alanı, layout.php'den çağrıldığı için App\Views\Admin altında olacak

use App\Config;
use App\Core\Session;
use App\Models\PaymentMethod; // Ödeme yöntemlerini çekmek için

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $deposits, $pagination, $filters, $pageTitle, $currentSection, $activeMenu, $activeMethodSlug, $activeMenuSub
// $pending_deposits_count, $rejected_deposits_count, $total_approved_deposits_amount
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Tablo ve kapsayıcıları için doğrudan ve güçlü CSS kuralları */
    .deposit-list-section {
        padding: 0px 20px 20px 20px; /* İçeriğe genel boşluk */
        margin-top: 0px; /* Üstteki boşluğu ayarla */
    }
    .deposit-table-wrapper {
        overflow-x: auto; /* Yatay kaydırma çubuğu */
        margin-top: 20px;
        border: 1px solid #dee2e6; /* Bootstrap table border */
        border-radius: 0.25rem; /* Bootstrap border-radius */
        box-sizing: border-box; /* padding ve border genişlik içinde olsun */
    }
    .deposit-table {
        width: 100% !important; /* Temanın diğer stillerini ezmek için */
        min-width: 900px; /* Sütunların sığması için minimum genişlik */
        border-collapse: collapse;
        table-layout: fixed; /* Sütun genişliklerini sabitle */
    }
    .deposit-table th, .deposit-table td {
        white-space: nowrap; /* Metinlerin satır atlamasını engelle */
        padding: 12px 15px; /* Daha fazla padding */
        vertical-align: middle;
        border: 1px solid #dee2e6; /* Bootstrap table cell border */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .deposit-table th {
        background-color: #f8f9fa; /* Başlık arka planı */
        font-weight: 600; /* Kalın font */
        text-align: left;
        color: #495057; /* Koyu gri yazı */
    }
    .deposit-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc; /* Tek satırlar için hafif arka plan */
    }
    .deposit-table tbody tr:hover {
        background-color: #f0f0f0; /* Hover efekti */
    }

    /* Checkbox ve ikonlar için hizalama */
    .deposit-table th:first-child,
    .deposit-table td:first-child {
        width: 40px; /* Checkbox sütunu genişliği */
        text-align: center;
    }
    .deposit-table th:last-child,
    .deposit-table td:last-child {
        width: 150px; /* İşlemler sütunu genişliği */
        text-align: center;
    }
    /* Diğer sütunlara varsayılan eşit genişlik, veya ihtiyaç olursa ayarla */
    .deposit-table th:nth-child(2) { width: 120px; } /* Ref ID */
    .deposit-table th:nth-child(3) { width: 150px; } /* Kullanıcı */
    .deposit-table th:nth-child(4) { width: 100px; } /* Miktar */
    .deposit-table th:nth-child(5) { width: 120px; } /* Yöntem */
    .deposit-table th:nth-child(6) { width: 100px; } /* Durum */
    .deposit-table th:nth-child(7) { width: 150px; } /* İşlem Tarihi */
    .deposit-table th:nth-child(8) { width: 120px; } /* Onay Süresi */
    .deposit-table th:nth-child(9) { width: 120px; } /* Onaylayan */

    /* Action butonları */
    .action-icon .btn {
        font-size: 0.75rem; /* Daha küçük butonlar */
        padding: 0.3rem 0.6rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
        min-width: 30px; /* Minimum genişlik vererek sıkışmayı önle */
    }
    .action-icon .btn i {
        font-size: 1rem; /* İkon boyutu */
    }
    .action-icon .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: white; }
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
    .filter-form-container {
        background-color: #ffffff;
        padding: 15px;
        border-radius: 0.25rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }
    .filter-form-container .form-control,
    .filter-form-container .btn {
        height: 38px;
    }
</style>

<div class="deposit-list-section">
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
                        <li><a href="javascript:void(0);" class="dropdown-item rounded-1 export-pdf-btn"><i class="ti ti-file-type-pdf me-1"></i>PDF Olarak Aktar</a></li>
                        <li><a href="javascript:void(0);" class="dropdown-item rounded-1 export-excel-btn"><i class="ti ti-file-type-xls me-1"></i>Excel Olarak Aktar</a></li>
                    </ul>
                </div>
            </div>
            <div class="mb-2">
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_deposit_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni Yatırım Ekle</a>
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
            <div class="card flex-fill">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-primary flex-shrink-0">
                            <i class="ti ti-cash-banknote fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Toplam Yatırma İşlemi</p>
                            <h4><?= $pagination['total_records'] ?? 0 ?></h4>
                        </div>
                    </div>
                    <div id="total-chart"></div> </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-warning flex-shrink-0">
                            <i class="ti ti-clock fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Bekleyen İşlemler</p>
                            <h4><?= $pending_deposits_count ?? 0 ?></h4>
                        </div>
                    </div>
                    <div id="pending-chart"></div> </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-success flex-shrink-0">
                            <i class="ti ti-check fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Onaylanan Tutar</p>
                            <h4>$<?= number_format($total_approved_deposits_amount ?? 0, 2) ?></h4>
                        </div>
                    </div>
                    <div id="approved-chart"></div> </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center overflow-hidden">
                        <span class="avatar avatar-lg bg-danger flex-shrink-0">
                            <i class="ti ti-x fs-16"></i>
                        </span>
                        <div class="ms-2 overflow-hidden">
                            <p class="fs-12 fw-medium mb-1 text-truncate">Reddedilen İşlemler</p>
                            <h4><?= $rejected_deposits_count ?? 0 ?></h4>
                        </div>
                    </div>
                    <div id="rejected-chart"></div> </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
            <h5>Para Yatırma İşlemleri Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center">
                    <input type="hidden" name="page" value="deposits">
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
                            <option value="pending" <?= (($filters['status'] ?? '') == 'pending' ? 'selected' : '') ?>>Beklemede</option>
                            <option value="approved" <?= (($filters['status'] ?? '') == 'approved' ? 'selected' : '') ?>>Onaylandı</option>
                            <option value="rejected" <?= (($filters['status'] ?? '') == 'rejected' ? 'selected' : '') ?>>Reddedildi</option>
                            <option value="cancelled" <?= (($filters['status'] ?? '') == 'cancelled' ? 'selected' : '') ?>>İptal Edildi</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="deposit-table-wrapper">
                <table class="table table-hover table-striped deposit-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-main-table">
                                </div>
                            </th>
                            <th>Ref ID</th>
                            <th>Kullanıcı</th>
                            <th>Miktar</th>
                            <th>Yöntem</th>
                            <th>Durum</th>
                            <th>İşlem Tarihi</th>
                            <th>Onay Süresi</th>
                            <th>Onaylayan</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($deposits)): ?>
                            <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $deposit['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($deposit['ref_id']) ?></td>
                                    <td><?= htmlspecialchars($deposit['user_username']) ?> (<?= htmlspecialchars($deposit['user_namesurname_full']) ?>)</td>
                                    <td><?= number_format($deposit['amount'], 2) ?> <?= htmlspecialchars($deposit['currency']) ?></td>
                                    <td><?= htmlspecialchars($deposit['payment_method_name'] ?? 'Bilinmiyor') ?></td>
                                    <td>
                                        <?php
                                            $statusClass = '';
                                            if ($deposit['status'] == 'pending') $statusClass = 'badge bg-warning';
                                            elseif ($deposit['status'] == 'approved') $statusClass = 'badge bg-success';
                                            elseif ($deposit['status'] == 'rejected') $statusClass = 'badge bg-danger';
                                            elseif ($deposit['status'] == 'cancelled') $statusClass = 'badge bg-info';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= ucfirst($deposit['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($deposit['transaction_date']))) ?></td>
                                    <td>
                                        <?php if ($deposit['processing_time_seconds'] !== null): ?>
                                            <?= htmlspecialchars(floor($deposit['processing_time_seconds'] / 60)) ?> dk <?= htmlspecialchars($deposit['processing_time_seconds'] % 60) ?> sn
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($deposit['approved_by_username'] ?? 'Beklemede') ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-info view-deposit-detail" data-bs-toggle="modal" data-bs-target="#deposit_detail_modal" data-id="<?= $deposit['id'] ?>" title="Detay Görüntüle"><i class="ti ti-eye"></i></a>
                                            <?php if ($deposit['status'] === 'pending'): ?>
                                                <a href="javascript:void(0);" class="me-2 btn btn-sm btn-success btn-approve-deposit" data-id="<?= $deposit['id'] ?>" title="Onayla"><i class="ti ti-check"></i></a>
                                                <a href="javascript:void(0);" class="me-2 btn btn-sm btn-danger btn-reject-deposit" data-id="<?= $deposit['id'] ?>" title="Reddet"><i class="ti ti-x"></i></a>
                                            <?php endif; ?>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-secondary btn-delete-deposit" data-id="<?= $deposit['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_deposit_modal" title="Sil"><i class="ti ti-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">Kayıt bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end p-3">
                <ul class="pagination mb-0">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=deposits&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=deposits&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=deposits&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&method_slug=<?= htmlspecialchars($filters['method_slug'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_deposit_modal" tabindex="-1" aria-labelledby="add_deposit_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_deposit_modal_label">Yeni Para Yatırma İşlemi Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_deposit_form" action="<?= Config::BASE_URL ?>/admin.php?page=deposits&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adı Soyadı <span class="text-danger">*</span></label>
                                <input type="text" name="namesurname" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Miktar <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ödeme Yöntemi <span class="text-danger">*</span></label>
                                <select name="method_id" class="form-control select2" required>
                                    <option value="">Seçiniz</option>
                                    <?php
                                    $paymentMethodModel = new \App\Models\PaymentMethod();
                                    $depositMethods = $paymentMethodModel->getAllPaymentMethods(['method_type' => 'deposit']);
                                    foreach ($depositMethods as $method) {
                                        echo '<option value="' . $method['id'] . '">' . htmlspecialchars($method['method_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Referans ID <span class="text-danger">*</span></label>
                                <input type="text" name="ref_id" class="form-control" required>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label class="form-label">Hesap Numarası / Detayları (JSON) </label>
                                <textarea name="account_details" class="form-control" rows="1"></textarea>
                                <small class="form-text text-muted">Örnek: {"iban": "TRXX...", "bank_name": "Garanti"}</small>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label class="form-label">Site Bilgisi</label>
                                <input type="text" name="site_info" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grup Bilgisi</label>
                                <input type="text" name="group_info" class="form-control">
                            </div>
                             <div class="col-md-12 mb-3">
                                <label class="form-label">Müşteri Bilgileri (JSON)</label>
                                <textarea name="client_info" class="form-control" rows="2"></textarea>
                                <small class="form-text text-muted">Örnek: {"user_agent": "...", "locale": "tr"}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Yatırım Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deposit_detail_modal" tabindex="-1" aria-labelledby="deposit_detail_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deposit_detail_modal_label">Para Yatırma İşlem Detayı</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="deposit_detail_content">
                        <p class="text-center">Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="delete_deposit_modal" tabindex="-1" aria-labelledby="delete_deposit_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_deposit_modal_label">İşlemi Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu işlemi sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-deposit-btn">Evet, Sil</button>
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
        $('.select2').select2({
            dropdownParent: $('#add_deposit_modal'),
            width: '100%'
        });
    }

    // Bu sayfada DataTables kullanmıyoruz. Sadece kendi manuel JS'imiz olacak.
    // Detay Görüntüleme (View) Butonu AJAX ile
    $(document).on('click', '.view-deposit-detail', function() {
        const depositId = $(this).data('id');
        $('#deposit_detail_content').html('<p class="text-center">Yükleniyor...</p>');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=view_detail',
            type: 'GET',
            data: { id: depositId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    const accountDetailsPretty = JSON.stringify(detail.account_details, null, 2);
                    const clientInfoSite = detail.client_info && detail.client_info.site_info ? detail.client_info.site_info : 'Yok';
                    const clientInfoGroup = detail.client_info && detail.client_info.group_info ? detail.client_info.group_info : 'Yok';
                    const rejectionReasonHtml = detail.rejection_reason ? `
                        <div class="row align-items-center">
                            <div class="col-md-12 mb-3">
                                <p class="fs-12 mb-0">Reddetme Nedeni</p>
                                <p class="text-gray-9">${detail.rejection_reason}</p>
                            </div>
                        </div>
                    ` : '';

                    let htmlContent = `
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center rounded bg-light p-3 mb-3">
                                <div>
                                    <p class="text-gray-9 fw-medium mb-0">Referans ID: ${detail.ref_id}</p>
                                    <p class="mb-0">Kullanıcı: ${detail.user_username} (${detail.user_namesurname_full})</p>
                                </div>
                                <span class="badge ${detail.status_class}"><i class="ti ti-point-filled"></i>${detail.status_text}</span>
                            </div>
                            <p class="text-gray-9 fw-medium">Temel Bilgiler</p>
                            <div class="pb-1 border-bottom mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Miktar</p>
                                        <p class="text-gray-9">${detail.amount} ${detail.currency}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Ödeme Yöntemi</p>
                                        <p class="text-gray-9">${detail.payment_method_name}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">İşlem Tarihi</p>
                                        <p class="text-gray-9">${detail.transaction_date_formatted}</p>
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Onaylayan</p>
                                        <p class="text-gray-9">${detail.approved_by_username || 'Yok'}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Onay Tarihi</p>
                                        <p class="text-gray-9">${detail.approved_at_formatted || 'Yok'}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Onay Süresi</p>
                                        <p class="text-gray-9">${detail.processing_time_text || 'N/A'}</p>
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                     <div class="col-md-12 mb-3">
                                        <p class="fs-12 mb-0">Hesap Detayları</p>
                                        <p class="text-gray-9"><pre>${accountDetailsPretty}</pre></p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-gray-9 fw-medium">Ek Bilgiler</p>
                            <div>
                                <div class="row align-items-center">
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Kaynak IP</p>
                                        <p class="text-gray-9">${detail.source_ip || 'Yok'}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Site Bilgisi</p>
                                        <p class="text-gray-9">${clientInfoSite}</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="fs-12 mb-0">Grup Bilgisi</p>
                                        <p class="text-gray-9">${clientInfoGroup}</p>
                                    </div>
                                </div>
                                ${rejectionReasonHtml}
                            </div>
                        </div>
                    `;
                    $('#deposit_detail_content').html(htmlContent);
                } else {
                    $('#deposit_detail_content').html('<p class="text-danger text-center">' + (response.message || 'Detaylar yüklenirken bir hata oluştu.') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                $('#deposit_detail_content').html('<p class="text-danger text-center">Detaylar yüklenemedi. Sunucu hatası.</p>');
            }
        });
    });

    // Manuel Yatırım Ekleme Formu Submit
    $(document).on('submit', '#add_deposit_form', function(e) {
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
                    $('#add_deposit_modal').modal('hide');
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

        // Delete butonu için ID'yi modal'a aktar
        let depositToDeleteId = 0;
        $(document).on('click', '.btn-delete-deposit', function() {
            depositToDeleteId = $(this).data('id');
        });

        // Delete onayı butonu AJAX ile
        $(document).on('click', '.confirm-delete-deposit-btn', function() {
            $.ajax({
                url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=delete',
                type: 'POST',
                data: { id: depositToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $('#delete_deposit_modal').modal('hide');
                        location.reload();
                    } else {
                        alert('Hata: ' + (response.message || 'Silme işlemi başarısız.'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error, xhr.responseText);
                    alert('Silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
                }
            });
        });

        // Onay/Red AJAX metotları
        $(document).on('click', '.btn-approve-deposit', function() {
            const islemID = $(this).data('id');
            if (confirm('Bu işlemi onaylamak istediğinize emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=approve',
                    data: { islemID: islemID, csrf_token: '<?= Session::generateCsrfToken() ?>', action: 'approve' },
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

        $(document).on('click', '.btn-reject-deposit', function() {
            const islemID = $(this).data('id');
            const reason = prompt('Reddetme sebebini giriniz:');
            if (reason !== null && reason.trim() !== '') {
                if (confirm('Bu işlemi reddetmek istediğinize emin misiniz?')) {
                    $.ajax({
                        type: 'POST',
                        url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=reject',
                        data: { islemID: islemID, reason: reason, csrf_token: '<?= Session::generateCsrfToken() ?>', action: 'reject' },
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

        // Genel tablolarda "select all" checkbox'ı
        $(document).on('click', '#select-all-main-table', function() {
            var isChecked = this.checked;
            $('table.table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });

    }); // document.ready kapanışı
</script>