<?php
// app/Views/admin/payment_methods/list.php
namespace App\Views\Admin\PaymentMethods;

use App\Config;
use App\Core\Session;
use App\Models\PaymentMethod; // PaymentMethod modelini kullanıyoruz

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $methods, $pagination, $filters, $pageTitle, $currentSection, $activeMenu
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .payment-methods-list-section {
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
    .payment-methods-table-wrapper {
        overflow-x: auto; /* Yatay kaydırma çubuğu */
        margin-top: 20px;
        border: 1px solid #dee2e6; /* Bootstrap table border */
        border-radius: 0.25rem; /* Bootstrap border-radius */
        box-sizing: border-box; /* padding ve border genişlik içinde olsun */
    }
    .payment-methods-table {
        width: 100% !important; /* Temanın diğer stillerini ezmek için */
        min-width: 900px; /* Sütunların sığması için minimum genişlik */
        border-collapse: collapse;
        table-layout: fixed; /* Sütun genişliklerini sabitle */
    }
    .payment-methods-table th, .payment-methods-table td {
        white-space: nowrap; /* Metinlerin satır atlamasını engelle */
        padding: 12px 15px; /* Daha fazla padding */
        vertical-align: middle;
        border: 1px solid #dee2e6; /* Bootstrap table cell border */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .payment-methods-table th {
        background-color: #f8f9fa; /* Başlık arka planı */
        font-weight: 600; /* Kalın font */
        text-align: left;
        color: #495057; /* Koyu gri yazı */
    }
    .payment-methods-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc; /* Tek satırlar için hafif arka plan */
    }
    .payment-methods-table tbody tr:hover {
        background-color: #f0f0f0; /* Hover efekti */
    }

    /* Checkbox ve işlemler sütunu */
    .payment-methods-table th:first-child, .payment-methods-table td:first-child { width: 40px; text-align: center; }
    .payment-methods-table th:last-child, .payment-methods-table td:last-child { width: 120px; text-align: center; }
    /* Diğer sütun genişlikleri */
    .payment-methods-table th:nth-child(2) { width: 180px; } /* Yöntem Adı */
    .payment-methods-table th:nth-child(3) { width: 150px; } /* Slug */
    .payment-methods-table th:nth-child(4) { width: 100px; } /* Tip */
    .payment-methods-table th:nth-child(5) { width: 80px; } /* Aktif Mi? */
    .payment-methods-table th:nth-child(6) { width: 100px; } /* Sıra */
    .payment-methods-table th:nth-child(7) { width: 150px; } /* Oluşturulma Tarihi */

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

<div class="payment-methods-list-section">
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
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_method_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni Yöntem Ekle</a>
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
            <h5>Ödeme Yöntemleri Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center filter-form-container">
                    <input type="hidden" name="page" value="payment_methods">

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
                        <select class="form-control" name="method_type">
                            <option value="">Tüm Tipler</option>
                            <option value="deposit" <?= (($filters['method_type'] ?? '') == 'deposit' ? 'selected' : '') ?>>Yatırma</option>
                            <option value="withdrawal" <?= (($filters['method_type'] ?? '') == 'withdrawal' ? 'selected' : '') ?>>Çekme</option>
                            <option value="both" <?= (($filters['method_type'] ?? '') == 'both' ? 'selected' : '') ?>>İkisi De</option>
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
            <div class="payment-methods-table-wrapper">
                <table class="table table-hover table-striped custom-styled-table payment-methods-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-methods">
                                </div>
                            </th>
                            <th>Yöntem Adı</th>
                            <th>Slug</th>
                            <th>Tip</th>
                            <th>Aktif Mi?</th>
                            <th>Sıra</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($methods)): ?>
                            <?php foreach ($methods as $method): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $method['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($method['method_name']) ?></td>
                                    <td><?= htmlspecialchars($method['method_slug']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($method['method_type'])) ?></td>
                                    <td>
                                        <?php
                                            $statusClass = $method['is_active'] ? 'badge bg-success' : 'badge bg-danger';
                                            $statusText = $method['is_active'] ? 'Evet' : 'Hayır';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= htmlspecialchars($statusText) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($method['display_order']) ?></td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($method['created_at']))) ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-primary edit-method-btn" data-bs-toggle="modal" data-bs-target="#edit_method_modal" data-id="<?= $method['id'] ?>" title="Düzenle"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-method-btn" data-id="<?= $method['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_method_modal" title="Sil"><i class="ti ti-trash"></i></a>
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
                        <li class="page-item"><a class="page-link" href="?page=payment_methods&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_type=<?= htmlspecialchars($filters['method_type'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=payment_methods&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_type=<?= htmlspecialchars($filters['method_type'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=payment_methods&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&method_type=<?= htmlspecialchars($filters['method_type'] ?? '') ?>&is_active=<?= htmlspecialchars($filters['is_active'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_method_modal" tabindex="-1" aria-labelledby="add_method_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_method_modal_label">Yeni Ödeme Yöntemi Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_method_form" action="<?= Config::BASE_URL ?>/admin.php?page=payment_methods&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yöntem Adı <span class="text-danger">*</span></label>
                                <input type="text" name="method_name" class="form-control" placeholder="örn: Banka Havalesi" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="method_slug" class="form-control" placeholder="örn: bank_transfer (benzersiz)" required>
                                <small class="form-text text-muted">Kodda kullanılacak benzersiz ve küçük harfli isim.</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yöntem Tipi <span class="text-danger">*</span></label>
                                <select name="method_type" class="form-control" required>
                                    <option value="">Seçiniz</option>
                                    <option value="deposit">Yatırma</option>
                                    <option value="withdrawal">Çekme</option>
                                    <option value="both">İkisi De</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" class="form-control" required>
                                    <option value="1">Evet</option>
                                    <option value="0">Hayır</option>
                                </select>
                            </div>
                             <div class="col-md-12 mb-3">
                                <label class="form-label">Görüntüleme Sırası</label>
                                <input type="number" name="display_order" class="form-control" value="0">
                                <small class="form-text text-muted">Menüde veya listede görünme sırası.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Yöntem Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit_method_modal" tabindex="-1" aria-labelledby="edit_method_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_method_modal_label">Ödeme Yöntemini Düzenle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="edit_method_form" action="<?= Config::BASE_URL ?>/admin.php?page=payment_methods&action=edit" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_method_id">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yöntem Adı <span class="text-danger">*</span></label>
                                <input type="text" name="method_name" id="edit_method_name" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="method_slug" id="edit_method_slug" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yöntem Tipi <span class="text-danger">*</span></label>
                                <select name="method_type" id="edit_method_type" class="form-control" required>
                                    <option value="deposit">Yatırma</option>
                                    <option value="withdrawal">Çekme</option>
                                    <option value="both">İkisi De</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" id="edit_is_active" class="form-control" required>
                                    <option value="1">Evet</option>
                                    <option value="0">Hayır</option>
                                </select>
                            </div>
                             <div class="col-md-12 mb-3">
                                <label class="form-label">Görüntüleme Sırası</label>
                                <input type="number" name="display_order" id="edit_display_order" class="form-control" value="0">
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
    <div class="modal fade" id="delete_method_modal" tabindex="-1" aria-labelledby="delete_method_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_method_modal_label">Yöntemi Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu yöntemi sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-method-btn">Evet, Sil</button>
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
        $('#add_method_modal .select2').select2({
            dropdownParent: $('#add_method_modal'),
            width: '100%'
        });
        $('#edit_method_modal .select2').select2({
            dropdownParent: $('#edit_method_modal'),
            width: '100%'
        });
    }

    // Add Method Form Submit
    $(document).on('submit', '#add_method_form', function(e) {
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
                    $('#add_method_modal').modal('hide');
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

    // Edit Method - Modalı doldur
    $(document).on('click', '.edit-method-btn', function() {
        const methodId = $(this).data('id');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=payment_methods&action=view_detail', // Detay çekme servisi
            type: 'GET',
            data: { id: methodId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    $('#edit_method_id').val(detail.id);
                    $('#edit_method_name').val(detail.method_name);
                    $('#edit_method_slug').val(detail.method_slug);
                    $('#edit_method_type').val(detail.method_type);
                    $('#edit_is_active').val(detail.is_active ? '1' : '0');
                    $('#edit_display_order').val(detail.display_order);
                    
                    $('#edit_method_modal').modal('show');
                } else {
                    alert('Hata: ' + (response.message || 'Yöntem detayları yüklenemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Yöntem detayları yüklenirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Edit Method Form Submit
    $(document).on('submit', '#edit_method_form', function(e) {
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
                    $('#edit_method_modal').modal('hide');
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

    // Delete Method - ID'yi modala aktar
    let methodToDeleteId = 0;
    $(document).on('click', '.delete-method-btn', function() {
        methodToDeleteId = $(this).data('id');
    });

    // Delete Method - Onay butonu AJAX
    $(document).on('click', '.confirm-delete-method-btn', function() {
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=payment_methods&action=delete',
            type: 'POST',
            data: { id: methodToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#delete_method_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Yöntem silme işlemi başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Yöntem silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Genel tablolarda "select all" checkbox'ı
    $(document).on('click', '#select-all-methods', function() { // Yöntem tablosuna özel ID
        var isChecked = this.checked;
        $('table.payment-methods-table tbody input[type="checkbox"]').each(function() {
            this.checked = isChecked;
        });
    });
});
</script>