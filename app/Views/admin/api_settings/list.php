<?php
// app/Views/admin/api_settings/list.php
namespace App\Views\Admin\ApiSettings;

use App\Config;
use App\Core\Session;
use App\Models\PaymentMethod; // Ödeme yöntemlerini çekmek için (API hangi yönteme ait)

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $credentials, $pagination, $filters, $pageTitle, $currentSection, $activeMenu
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .api-settings-list-section {
        padding: 0px 20px 20px 20px;
        margin-top: 0px;
    }
    .page-breadcrumb {
        margin-bottom: 20px !important;
    }
    .main-card {
        margin-bottom: 20px;
    }

    /* Tablo ve kapsayıcıları için doğrudan ve güçlü CSS kuralları */
    .api-settings-table-wrapper {
        overflow-x: auto;
        margin-top: 20px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        box-sizing: border-box;
    }
    .api-settings-table {
        width: 100% !important;
        min-width: 1000px; /* Minimum genişlik */
        border-collapse: collapse;
        table-layout: fixed;
    }
    .api-settings-table th, .api-settings-table td {
        white-space: nowrap;
        padding: 12px 15px;
        vertical-align: middle;
        border: 1px solid #dee2e6;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .api-settings-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-align: left;
        color: #495057;
    }
    .api-settings-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc;
    }
    .api-settings-table tbody tr:hover {
        background-color: #f0f0f0;
    }

    /* Checkbox ve işlemler sütunu */
    .api-settings-table th:first-child, .api-settings-table td:first-child { width: 40px; text-align: center; }
    .api-settings-table th:last-child, .api-settings-table td:last-child { width: 120px; text-align: center; }
    /* Diğer sütun genişlikleri */
    .api-settings-table th:nth-child(2) { width: 180px; } /* Ödeme Yöntemi */
    .api-settings-table th:nth-child(3) { width: 250px; } /* API Key (kısaltılmış) */
    .api-settings-table th:nth-child(4) { width: 250px; } /* API Secret (kısaltılmış) */
    .api-settings-table th:nth-child(5) { width: 150px; } /* API Endpoint */
    .api-settings-table th:nth-child(6) { width: 80px; } /* Aktif Mi? */

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
    .action-icon .btn-primary { background-color: #007bff; border-color: #007bff; color: white; }
    .action-icon .btn-danger { background-color: #dc3545; border-color: #dc3545; color: white; }

    /* Badge stilleri */
    .badge {
        font-size: 0.8em;
        padding: .4em .7em;
        border-radius: .25rem;
    }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }

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
        height: 45px;
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    .modal-body .form-label {
        font-weight: 500;
        margin-bottom: 8px;
    }
    .modal-footer .btn {
        min-width: 100px;
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
</style>

<div class="api-settings-list-section">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1"><?= htmlspecialchars($pageTitle) ?></h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard"><i class="ti ti-smart-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">
                        Sistem Ayarları
                    </li>
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
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_credential_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni API Ayarı Ekle</a>
            </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                    <i class="ti ti-chevrons-up"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="card main-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
            <h5>API Ayarları Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center filter-form-container">
                    <input type="hidden" name="page" value="api_settings">

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
                        <select class="form-control" name="method_id">
                            <option value="">Tüm Yöntemler</option>
                            <?php
                            $paymentMethodModel = new \App\Models\PaymentMethod();
                            $allMethods = $paymentMethodModel->getAllPaymentMethods([], 100, 0);
                            foreach ($allMethods as $method) {
                                echo '<option value="' . $method['id'] . '" ' . ((isset($filters['method_id']) && $filters['method_id'] == $method['id']) ? 'selected' : '') . '>' . htmlspecialchars($method['method_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="me-3">
                        <select class="form-control" name="is_active">
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
            <div class="api-settings-table-wrapper">
                <table class="table table-hover table-striped custom-styled-table api-settings-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-credentials">
                                </div>
                            </th>
                            <th>Ödeme Yöntemi</th>
                            <th>API Anahtarı (Kısaltılmış)</th>
                            <th>API Sırrı (Kısaltılmış)</th>
                            <th>API Uç Noktası</th>
                            <th>Aktif Mi?</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($credentials)): ?>
                            <?php foreach ($credentials as $credential): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $credential['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($credential['payment_method_name'] ?? 'Bilinmiyor') ?></td>
                                    <td><?= htmlspecialchars(substr($credential['api_key'], 0, 10) . '...') ?></td> <td><?= htmlspecialchars(substr($credential['api_secret'], 0, 10) . '...') ?></td> <td><?= htmlspecialchars($credential['api_endpoint']) ?></td>
                                    <td>
                                        <?php
                                            $statusClass = $credential['is_active'] ? 'badge bg-success' : 'badge bg-danger';
                                            $statusText = $credential['is_active'] ? 'Evet' : 'Hayır';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= htmlspecialchars($statusText) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($credential['created_at']))) ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-primary edit-credential-btn" data-bs-toggle="modal" data-bs-target="#edit_credential_modal" data-id="<?= $credential['id'] ?>" title="Düzenle"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-credential-btn" data-id="<?= $credential['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_credential_modal" title="Sil"><i class="ti ti-trash"></i></a>
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
                        <li class="page-item"><a class="page-link" href="?page=api_settings&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_id=<?= htmlspecialchars($filters['method_id'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=api_settings&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_id=<?= htmlspecialchars($filters['method_id'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=api_settings&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_id=<?= htmlspecialchars($filters['method_id'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_credential_modal" tabindex="-1" aria-labelledby="add_credential_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_credential_modal_label">Yeni API Ayarı Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_credential_form" action="<?= Config::BASE_URL ?>/admin.php?page=api_settings&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Ödeme Yöntemi <span class="text-danger">*</span></label>
                                <select name="method_id" class="form-control select2" required>
                                    <option value="">Seçiniz</option>
                                    <?php
                                    $paymentMethodModel = new \App\Models\PaymentMethod();
                                    $allMethods = $paymentMethodModel->getAllPaymentMethods([], 100, 0); // Tüm yöntemleri çek
                                    foreach ($allMethods as $method) {
                                        echo '<option value="' . $method['id'] . '">' . htmlspecialchars($method['method_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Anahtarı <span class="text-danger">*</span></label>
                                <input type="text" name="api_key" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Sırrı <span class="text-danger">*</span></label>
                                <input type="text" name="api_secret" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Uç Noktası (Endpoint) <span class="text-danger">*</span></label>
                                <input type="url" name="api_endpoint" class="form-control" placeholder="örn: https://api.payfix.com/v1" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Diğer Ayarlar (JSON)</label>
                                <textarea name="other_config" class="form-control" rows="3" placeholder='örn: {"merchant_id": "123", "webhook_secret": "abc"}'></textarea>
                                <small class="form-text text-muted">API'ye özel diğer ayarları JSON formatında giriniz.</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" class="form-control" required>
                                    <option value="1">Evet</option>
                                    <option value="0">Hayır</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">API Ayarı Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit_credential_modal" tabindex="-1" aria-labelledby="edit_credential_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_credential_modal_label">API Ayarını Düzenle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="edit_credential_form" action="<?= Config::BASE_URL ?>/admin.php?page=api_settings&action=edit" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_credential_id">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Ödeme Yöntemi <span class="text-danger">*</span></label>
                                <select name="method_id" id="edit_method_id" class="form-control select2" required>
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
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Anahtarı <span class="text-danger">*</span></label>
                                <input type="text" name="api_key" id="edit_api_key" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Sırrı <span class="text-danger">*</span></label>
                                <input type="text" name="api_secret" id="edit_api_secret" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">API Uç Noktası (Endpoint) <span class="text-danger">*</span></label>
                                <input type="url" name="api_endpoint" id="edit_api_endpoint" class="form-control" placeholder="örn: https://api.payfix.com/v1" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Diğer Ayarlar (JSON)</label>
                                <textarea name="other_config" id="edit_other_config" class="form-control" rows="3" placeholder='örn: {"merchant_id": "123", "webhook_secret": "abc"}'></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" id="edit_is_active" class="form-control" required>
                                    <option value="1">Evet</option>
                                    <option value="0">Hayır</option>
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
    <div class="modal fade" id="delete_credential_modal" tabindex="-1" aria-labelledby="delete_credential_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_credential_modal_label">API Ayarını Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu API ayarını sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-credential-btn">Evet, Sil</button>
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
        $('#add_credential_modal .select2').select2({
            dropdownParent: $('#add_credential_modal'),
            width: '100%'
        });
        $('#edit_credential_modal .select2').select2({
            dropdownParent: $('#edit_credential_modal'),
            width: '100%'
        });
    }

    // Add Credential Form Submit
    $(document).on('submit', '#add_credential_form', function(e) {
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
                    $('#add_credential_modal').modal('hide');
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

    // Edit Credential - Modalı doldur
    $(document).on('click', '.edit-credential-btn', function() {
        const credentialId = $(this).data('id');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=api_settings&action=view_detail', // Detay çekme servisi
            type: 'GET',
            data: { id: credentialId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    $('#edit_credential_id').val(detail.id);
                    $('#edit_method_id').val(detail.method_id).trigger('change.select2');
                    $('#edit_api_key').val(detail.api_key);
                    $('#edit_api_secret').val(detail.api_secret);
                    $('#edit_api_endpoint').val(detail.api_endpoint);
                    // Other config JSON'unu formatlı olarak göster
                    $('#edit_other_config').val(JSON.stringify(detail.other_config, null, 2));
                    $('#edit_is_active').val(detail.is_active ? '1' : '0');
                    
                    $('#edit_credential_modal').modal('show');
                } else {
                    alert('Hata: ' + (response.message || 'API ayar detayları yüklenemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('API ayar detayları yüklenirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Edit Credential Form Submit
    $(document).on('submit', '#edit_credential_form', function(e) {
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
                    $('#edit_credential_modal').modal('hide');
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

    // Delete Credential - ID'yi modala aktar
    let credentialToDeleteId = 0;
    $(document).on('click', '.delete-credential-btn', function() {
        credentialToDeleteId = $(this).data('id');
    });

    // Delete Credential - Onay butonu AJAX
    $(document).on('click', '.confirm-delete-credential-btn', function() {
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=api_settings&action=delete',
            type: 'POST',
            data: { id: credentialToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#delete_credential_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'API ayarı silme işlemi başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('API ayarı silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Genel tablolarda "select all" checkbox'ı
    $(document).on('click', '#select-all-credentials', function() {
        var isChecked = this.checked;
        $('table.api-settings-table tbody input[type="checkbox"]').each(function() {
            this.checked = isChecked;
        });
    });
});
</script>