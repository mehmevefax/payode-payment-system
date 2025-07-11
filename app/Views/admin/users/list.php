<?php
// app/Views/admin/users/list.php
namespace App\Views\Admin\Users;

use App\Config;
use App\Core\Session;
use App\Models\User; // Kullanıcıları çekmek için

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $users, $pagination, $filters, $pageTitle, $currentSection, $activeMenu
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Tablo ve form elemanları için genel stil ayarlamaları */
    .user-list-section .card {
        margin-bottom: 20px;
    }
    .user-table-wrapper {
        overflow-x: auto;
        margin-top: 20px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        box-sizing: border-box;
    }
    .user-table {
        width: 100% !important;
        min-width: 1000px; /* Sütunların sığması için minimum genişlik */
        border-collapse: collapse;
        table-layout: fixed;
    }
    .user-table th, .user-table td {
        white-space: nowrap;
        padding: 12px 15px;
        vertical-align: middle;
        border: 1px solid #dee2e6;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .user-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-align: left;
        color: #495057;
    }
    .user-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc;
    }
    .user-table tbody tr:hover {
        background-color: #f0f0f0;
    }

    /* Checkbox ve işlemler sütunu */
    .user-table th:first-child, .user-table td:first-child { width: 40px; text-align: center; }
    .user-table th:last-child, .user-table td:last-child { width: 120px; text-align: center; }
    /* Diğer sütun genişlikleri */
    .user-table th:nth-child(2) { width: 150px; } /* Kullanıcı Adı */
    .user-table th:nth-child(3) { width: 180px; } /* Ad Soyad */
    .user-table th:nth-child(4) { width: 200px; } /* Email */
    .user-table th:nth-child(5) { width: 100px; } /* Tip */
    .user-table th:nth-child(6) { width: 80px; } /* Aktif */
    .user-table th:nth-child(7) { width: 150px; } /* Kayıt Tarihi */

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
    .action-icon .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: white; }

    /* Badge stilleri */
    .badge {
        font-size: 0.8em;
        padding: .4em .7em;
        border-radius: .25rem;
    }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
</style>

<div class="user-list-section">
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
                <a href="#" data-bs-toggle="modal" data-bs-target="#add_user_modal" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Yeni Kullanıcı Ekle</a>
            </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                    <i class="ti ti-chevrons-up"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
            <h5>Kullanıcılar Listesi</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex align-items-center">
                    <input type="hidden" name="page" value="users">

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
                        <select class="form-control" name="user_type">
                            <option value="">Tüm Tipler</option>
                            <option value="admin" <?= (($filters['user_type'] ?? '') == 'admin' ? 'selected' : '') ?>>Admin</option>
                            <option value="sub_user" <?= (($filters['user_type'] ?? '') == 'sub_user' ? 'selected' : '') ?>>Alt Kullanıcı</option>
                            <option value="staff" <?= (($filters['user_type'] ?? '') == 'staff' ? 'selected' : '') ?>>Personel</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="user-table-wrapper">
                <table class="table table-hover table-striped custom-styled-table user-table">
                    <thead class="thead-light">
                        <tr>
                            <th>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all-users">
                                </div>
                            </th>
                            <th>Kullanıcı Adı</th>
                            <th>Ad Soyad</th>
                            <th>Email</th>
                            <th>Tip</th>
                            <th>Aktif</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" value="<?= $user['id'] ?>">
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['namesurname']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($user['user_type'])) ?></td>
                                    <td>
                                        <?php
                                            $statusClass = $user['is_active'] ? 'badge bg-success' : 'badge bg-danger';
                                            $statusText = $user['is_active'] ? 'Aktif' : 'Pasif';
                                        ?>
                                        <span class="<?= $statusClass ?> d-inline-flex align-items-center badge-xs">
                                            <i class="ti ti-point-filled me-1"></i><?= htmlspecialchars($statusText) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y H:i:s', strtotime($user['created_at']))) ?></td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 btn btn-sm btn-info view-user-detail" data-bs-toggle="modal" data-bs-target="#user_detail_modal" data-id="<?= $user['id'] ?>" title="Detay Görüntüle"><i class="ti ti-eye"></i></a>
                                            <a href="#" class="me-2 btn btn-sm btn-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#edit_user_modal" data-id="<?= $user['id'] ?>" title="Düzenle"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-user-btn" data-id="<?= $user['id'] ?>" data-bs-toggle="modal" data-bs-target="#delete_user_modal" title="Sil"><i class="ti ti-trash"></i></a>
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
                        <li class="page-item"><a class="page-link" href="?page=users&p=<?= ($pagination['current_page'] - 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_type=<?= htmlspecialchars($filters['user_type'] ?? '') ?>">Önceki</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= ($pagination['total_pages'] ?? 1); $i++): ?>
                        <li class="page-item <?= (($pagination['current_page'] ?? 1) == $i ? 'active' : '') ?>">
                            <a class="page-link" href="?page=users&p=<?= $i ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_type=<?= htmlspecialchars($filters['user_type'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1)): ?>
                        <li class="page-item"><a class="page-link" href="?page=users&p=<?= ($pagination['current_page'] + 1) ?>&sayfada=<?= htmlspecialchars($pagination['limit']) ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&user_type=<?= htmlspecialchars($filters['user_type'] ?? '') ?>">Sonraki</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_user_modal" tabindex="-1" aria-labelledby="add_user_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_user_modal_label">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="add_user_form" action="<?= Config::BASE_URL ?>/admin.php?page=users&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="namesurname" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şifre <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Tipi <span class="text-danger">*</span></label>
                                <select name="user_type" class="form-control" required>
                                    <option value="sub_user">Alt Kullanıcı</option>
                                    <option value="staff">Personel</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Üst Kullanıcı ID (Opsiyonel)</label>
                                <select name="parent_user_id" class="form-control select2">
                                    <option value="">Yok</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allAdminsAndStaff = $userModel->getAllUsers(['user_type_in' => ['admin', 'staff']], 9999, 0); // Üst kullanıcılar
                                    foreach ($allAdminsAndStaff as $u) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Bu kullanıcı birine bağlıysa seçiniz.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" class="form-control" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Pasif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kullanıcı Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="user_detail_modal" tabindex="-1" aria-labelledby="user_detail_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="user_detail_modal_label">Kullanıcı Detayı</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="user_detail_content">
                        <p class="text-center">Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit_user_modal" tabindex="-1" aria-labelledby="edit_user_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_user_modal_label">Kullanıcıyı Düzenle</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="edit_user_form" action="<?= Config::BASE_URL ?>/admin.php?page=users&action=edit" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="id" id="edit_user_id">
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="namesurname" id="edit_namesurname" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yeni Şifre (Boş bırakırsanız değişmez)</label>
                                <input type="password" name="password" id="edit_password" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Tipi <span class="text-danger">*</span></label>
                                <select name="user_type" id="edit_user_type" class="form-control" required>
                                    <option value="sub_user">Alt Kullanıcı</option>
                                    <option value="staff">Personel</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Üst Kullanıcı ID (Opsiyonel)</label>
                                <select name="parent_user_id" id="edit_parent_user_id" class="form-control select2">
                                    <option value="">Yok</option>
                                    <?php
                                    $userModel = new \App\Models\User();
                                    $allAdminsAndStaff = $userModel->getAllUsers(['user_type_in' => ['admin', 'staff']], 9999, 0);
                                    foreach ($allAdminsAndStaff as $u) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['namesurname']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aktif Mi? <span class="text-danger">*</span></label>
                                <select name="is_active" id="edit_is_active" class="form-control" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Pasif</option>
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
    <div class="modal fade" id="delete_user_modal" tabindex="-1" aria-labelledby="delete_user_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete_user_modal_label">Kullanıcıyı Silmek İstiyor Musunuz?</h5>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <span class="avatar avatar-xl bg-transparent-danger text-danger mb-3">
                        <i class="ti ti-trash-x fs-36"></i>
                    </span>
                    <h4 class="mb-1">Emin Misiniz?</h4>
                    <p class="mb-3">Bu kullanıcıyı sildiğinizde geri alınamaz.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</a>
                        <button type="button" class="btn btn-danger confirm-delete-user-btn">Evet, Sil</button>
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
        $('#add_user_modal .select2').select2({
            dropdownParent: $('#add_user_modal'),
            width: '100%'
        });
        $('#edit_user_modal .select2').select2({
            dropdownParent: $('#edit_user_modal'),
            width: '100%'
        });
    }

    // Add User Form Submit
    $(document).on('submit', '#add_user_form', function(e) {
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
                    $('#add_user_modal').modal('hide');
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

    // View User Detail
    $(document).on('click', '.view-user-detail', function() {
        const userId = $(this).data('id');
        $('#user_detail_content').html('<p class="text-center">Yükleniyor...</p>');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=users&action=view_detail',
            type: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    const isActiveText = detail.is_active ? 'Aktif' : 'Pasif';
                    const statusClass = detail.is_active ? 'bg-success' : 'bg-danger';

                    let htmlContent = `
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center rounded bg-light p-3 mb-3">
                                <div>
                                    <p class="text-gray-9 fw-medium mb-0">Kullanıcı Adı: ${detail.username}</p>
                                    <p class="mb-0">Ad Soyad: ${detail.namesurname}</p>
                                </div>
                                <span class="badge ${statusClass}"><i class="ti ti-point-filled"></i>${isActiveText}</span>
                            </div>
                            <p class="text-gray-9 fw-medium">Temel Bilgiler</p>
                            <div class="pb-1 border-bottom mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Email</p>
                                        <p class="text-gray-9">${detail.email}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Kullanıcı Tipi</p>
                                        <p class="text-gray-9">${detail.user_type}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Üst Kullanıcı</p>
                                        <p class="text-gray-9">${detail.parent_username || 'Yok'}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="fs-12 mb-0">Kayıt Tarihi</p>
                                        <p class="text-gray-9">${detail.created_at_formatted}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#user_detail_content').html(htmlContent);
                } else {
                    $('#user_detail_content').html('<p class="text-danger text-center">' + (response.message || 'Detaylar yüklenirken bir hata oluştu.') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                $('#user_detail_content').html('<p class="text-danger text-center">Detaylar yüklenemedi. Sunucu hatası.</p>');
            }
        });
    });

    // Edit User - Modalı doldur
    $(document).on('click', '.edit-user-btn', function() {
        const userId = $(this).data('id');
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=users&action=view_detail',
            type: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const detail = response.data;
                    $('#edit_user_id').val(detail.id);
                    $('#edit_username').val(detail.username);
                    $('#edit_namesurname').val(detail.namesurname);
                    $('#edit_email').val(detail.email);
                    // Şifre alanı boş bırakılır, sadece değiştirilmek istenirse girilir
                    $('#edit_password').val('');
                    $('#edit_user_type').val(detail.user_type);
                    $('#edit_parent_user_id').val(detail.parent_user_id).trigger('change.select2');
                    $('#edit_is_active').val(detail.is_active ? '1' : '0');
                    
                    $('#edit_user_modal').modal('show');
                } else {
                    alert('Hata: ' + (response.message || 'Kullanıcı detayları yüklenemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Kullanıcı detayları yüklenirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Edit User Form Submit
    $(document).on('submit', '#edit_user_form', function(e) {
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
                    $('#edit_user_modal').modal('hide');
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

    // Delete User - ID'yi modala aktar
    let userToDeleteId = 0;
    $(document).on('click', '.delete-user-btn', function() {
        userToDeleteId = $(this).data('id');
    });

    // Delete User - Onay butonu AJAX
    $(document).on('click', '.confirm-delete-user-btn', function() {
        $.ajax({
            url: '<?= Config::BASE_URL ?>/admin.php?page=users&action=delete',
            type: 'POST',
            data: { id: userToDeleteId, csrf_token: '<?= Session::generateCsrfToken() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#delete_user_modal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Kullanıcı silme işlemi başarısız.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Kullanıcı silme işlemi sırasında bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    // Genel tablolarda "