<?php
// app/Views/admin/users/permissions.php
namespace App\Views\Admin\Users;

use App\Config;
use App\Core\Session;
use App\Models\Permission; // İzin modelini kullanıyoruz

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $permissions, $pagination, $filters, $pageTitle, $currentSection, $activeMenu
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .permission-list-section {
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
    .permission-table-wrapper {
        overflow-x: auto; /* Yatay kaydırma çubuğu */
        margin-top: 20px;
        border: 1px solid #dee2e6; /* Bootstrap table border */
        border-radius: 0.25rem; /* Bootstrap border-radius */
        box-sizing: border-box; /* padding ve border genişlik içinde olsun */
    }
    .permission-table {
        width: 100% !important; /* Temanın diğer stillerini ezmek için */
        min-width: 900px; /* Sütunların sığması için minimum genişlik */
        border-collapse: collapse;
        table-layout: fixed; /* Sütun genişliklerini sabitle */
    }
    .permission-table th, .permission-table td {
        white-space: nowrap; /* Metinlerin satır atlamasını engelle */
        padding: 12px 15px; /* Daha fazla padding */
        vertical-align: middle;
        border: 1px solid #dee2e6; /* Bootstrap table cell border */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .permission-table th {
        background-color: #f8f9fa; /* Başlık arka planı */
        font-weight: 600; /* Kalın font */
        text-align: left;
        color: #495057; /* Koyu gri yazı */
    }
    .permission-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc; /* Tek satırlar için hafif arka plan */
    }
    .permission-table tbody tr:hover {
        background-color: #f0f0f0; /* Hover efekti */
    }

    /* Checkbox ve işlemler sütunu */
    .permission-table th:first-child, .permission-table td:first-child { width: 40px; text-align: center; }
    .permission-table th:last-child, .permission-table td:last-child { width: 120px; text-align: center; }
    /* Diğer sütun genişlikleri */
    .permission-table th:nth-child(2) { width: 180px; } /* İzin Anahtarı */
    .permission-table th:nth-child(3) { width: 150px; } /* Kullanıcı Adı */
    .permission-table th:nth-child(4) { width: 100px; } /* Etkin Mi? */
    .permission-table th:nth-child(5) { width: 150px; } /* Oluşturulma Tarihi */

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

<div class="permission-list-section">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1"><?= htmlspecialchars($pageTitle) ?></h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?= Config::BASE_URL ?>/admin.php?page=dashboard"><i class="ti ti-smart-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">
                        Yönetim
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
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_permission_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni Yetki Ekle</a>
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
            <h5>Roller ve Yetkiler Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center filter-form-container">
                    <input type="hidden" name="page" value="permissions">

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
                        <select class="form-control" name="user_id">
                            <option value="">Tüm Kullanıcılar</option>
                            <?php
                            $userModel = new \App\Models\User();
                            $allUsers = $userModel->getAllUsers([], 9999, 0);
                            foreach ($allUsers as $u) {
                                echo '<option value="' . $u['id'] . '" ' . ((isset($filters['user_id']) && $filters['user_id'] == $u['id']) ? 'selected' : '') . '>' . htmlspecialchars($u['username']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="permission-table-wrapper">
                <table class="table table-hover table-striped custom-styled-table permission-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-permissions">
                                </div>
                            </th>
                            <th>İzin Anahtarı</th>
                            <th>Kullanıcı Adı</th>
                            <th>Etkin Mi?</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($permissions)): ?>
                            <?php foreach ($permissions as $permission): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $permission['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($permission['permission_key']) ?></td>
                                    <td><?= htmlspecialchars($permission['username'] ?? 'Genel/Tanımsız') ?></td>
                                    <td>
                                        <?php
                                            $statusClass = $permission['is_enabled'] ? 'badge bg-success' : 'badge bg-danger';
                                            $statusText = $permission['is_enabled'] ? 'Evet' : 'Hayır';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= htmlspecialchars($statusText) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($permission['created_at']))) ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-primary edit-permission-btn" data-bs-toggle="modal" data-bs-target="#edit_permission_modal" data-id="<?= $permission['id'] ?>" title="Düzenle"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-permission-btn" data-id="<?= $permission['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_permission_modal" title="Sil"><i class="ti ti-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Kayıt bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end p-3">
                <ul class="pagination mb-0">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=permissions&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_id=<?= htmlspecialchars($filters['user_id'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=permissions&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_id=<?= htmlspecialchars($filters['user_id'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=permissions&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_id=<?= htmlspecialchars($filters['user_id'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_permission_modal" tabindex="-1" aria-labelledby="add_permission_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_permission_modal_label">Yeni Yetki Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_permission_form" action="<?= Config::BASE_URL ?>/admin.php?page=permissions&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yetki Anahtarı <span class="text-danger">*</span></label>
                                <input type="text" name="permission_key" class="form-control" placeholder="örn: can_manage_users" required>
                                <small class="form-text text-muted">Yetkiye özel benzersiz bir anahtar girin.</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Kullanıcı (Opsiyonel)</label>
                                <select name="user_id" class="form-control select2">
                                    <option value="">Tüm Kullanıcılar (Genel Yetki)</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allUsers = $userModel->getAllUsers([], 9999, 0);
                                    foreach ($allUsers as $u) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Bu yetki belirli bir kullanıcıya atanacaksa seçiniz. Seçilmezse genel yetki olur.</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Etkin Mi? <span class="text-danger">*</span></label>
                                <select name="is_enabled" class="form-control" required>
                                    <option value="1">Evet</option>
                                    <option value="0">Hayır</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Yetki Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit_permission_modal" tabindex="-1" aria-labelledby="edit_permission_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_permission_modal_label">Yetkiyi Düzenle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="edit_permission_form" action="<?= Config::BASE_URL ?>/admin.php?page=permissions&action=edit" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_permission_id">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Yetki Anahtarı <span class="text-danger">*</span></label>
                                <input type="text" name="permission_key" id="edit_permission_key" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Kullanıcı (Opsiyonel)</label>
                                <select name="user_id" id="edit_permission_user_id" class="form-control select2">
                                    <option value="">Tüm Kullanıcılar (Genel Yetki)</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allUsers = $userModel->getAllUsers([], 9999, 0);
                                    foreach ($allUsers as $u) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Etkin Mi? <span class="text-danger">*</span></label>
                                <select name="is_enabled" id="edit_is_enabled" class="form-control" required>
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
    <div class="modal fade" id="delete_permission_modal" tabindex="-1" aria-labelledby="delete_permission_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_permission_modal_label">Yetkiyi Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu yetkiyi sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-permission-btn">Evet, Sil</button>
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
        $('#add_permission_modal .select2').select2({
            dropdownParent: $('#add_permission_modal'),
            width: '100%'
        });
        $('#edit_permission_modal .select2').select2({
            dropdownParent: $('#edit_permission_modal'),
            width: '100%'
        });
    }

    // Add Permission Form Submit
    $(document).on('submit', '#add_permission_form', function(e) {
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
                    $('#add_permission_modal').modal('hide');
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

    // Edit Permission - Modalı doldur
    $(document).on('click', '.edit-permission-btn', function() {
        const permissionId = $(this).data('id');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=permissions&action=view_detail', // Detay çekme servisi
            type: 'GET',
            data: { id: permissionId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    $('#edit_permission_id').val(detail.id);
                    $('#edit_permission_key').val(detail.permission_key);
                    $('#edit_permission_user_id').val(detail.user_id).trigger('change.select2'); // null ise boş kalır
                    $('#edit_is_enabled').val(detail.is_enabled ? '1' : '0');
                    
                    $('#edit_permission_modal').modal('show');
                } else {
                    alert('Hata: ' + (response.message || 'Yetki detayları yüklenemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Yetki detayları yüklenirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Edit Permission Form Submit
    $(document).on('submit', '#edit_permission_form', function(e) {
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
                    $('#edit_permission_modal').modal('hide');
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

    // Delete Permission - ID'yi modala aktar
    let permissionToDeleteId = 0;
    $(document).on('click', '.delete-permission-btn', function() {
        permissionToDeleteId = $(this).data('id');
    });

    // Delete Permission - Onay butonu AJAX
    $(document).on('click', '.confirm-delete-permission-btn', function() {
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=permissions&action=delete',
            type: 'POST',
            data: { id: permissionToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#delete_permission_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Yetki silme işlemi başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Yetki silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Genel tablolarda "select all" checkbox'ı
    $(document).on('click', '#select-all-permissions', function() {
        var isChecked = this.checked;
        $('table.permission-table tbody input[type="checkbox"]').each(function() {
            this.checked = isChecked;
        });
    });
});
</script>