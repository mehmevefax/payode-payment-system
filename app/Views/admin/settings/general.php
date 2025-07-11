<?php
// app/Views/admin/settings/general.php
namespace App\Views\Admin\Settings;

use App\Config;
use App\Core\Session;
use App\Models\Setting; // Ayarları çekmek için

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $settings (örneğin: ['site_name' => 'Payode', 'admin_email' => 'admin@example.com']), $pageTitle, $currentSection, $activeMenu
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .general-settings-section {
        padding: 0px 20px 20px 20px;
        margin-top: 0px;
    }
    .page-breadcrumb {
        margin-bottom: 20px !important;
    }
    .main-card {
        margin-bottom: 20px;
    }

    /* Form elemanları için genel stil */
    .settings-form .form-group {
        margin-bottom: 15px;
    }
    .settings-form .form-label {
        font-weight: 500;
        margin-bottom: 8px;
        display: block; /* Her label kendi satırında olsun */
    }
    .settings-form .form-control,
    .settings-form .select2-container .select2-selection--single {
        height: 45px; /* Input ve select yüksekliklerini ayarla */
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    .settings-form textarea.form-control {
        height: auto; /* Textarea için otomatik yükseklik */
        min-height: 80px;
        padding-top: 10px;
    }
    .settings-form .form-check-input {
        width: 1.25em;
        height: 1.25em;
        margin-top: 0.25em;
    }
    .settings-form .form-check-label {
        margin-left: 0.5rem;
    }
    .settings-form .btn {
        min-width: 120px;
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
    .settings-form .card-header {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 1px solid #dee2e6;
        padding: 1rem 1.25rem;
    }
    .settings-form .card-body {
        padding: 1.25rem;
    }
</style>

<div class="general-settings-section">
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
            <div class="head-icons ms-2">
                <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                    <i class="ti ti-chevrons-up"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="card main-card">
        <div class="card-header">
            <h4>Genel Ayarlar</h4>
        </div>
        <div class="card-body">
            <form id="general_settings_form" action="<?= Config::BASE_URL ?>/admin.php?page=settings" method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Site Adı <span class="text-danger">*</span></label>
                            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? Config::APP_NAME) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Yönetici Email <span class="text-danger">*</span></label>
                            <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@example.com') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Varsayılan Para Birimi <span class="text-danger">*</span></label>
                            <select name="default_currency" class="form-control" required>
                                <option value="TRY" <?= (($settings['default_currency'] ?? 'TRY') == 'TRY' ? 'selected' : '') ?>>TRY - Türk Lirası</option>
                                <option value="USD" <?= (($settings['default_currency'] ?? 'TRY') == 'USD' ? 'selected' : '') ?>>USD - Amerikan Doları</option>
                                <option value="EUR" <?= (($settings['default_currency'] ?? 'TRY') == 'EUR' ? 'selected' : '') ?>>EUR - Euro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Geliştirme Modu (Debug) <span class="text-danger">*</span></label>
                            <select name="debug_mode" class="form-control" required>
                                <option value="1" <?= (($settings['debug_mode'] ?? Config::DEBUG_MODE) == '1' ? 'selected' : '') ?>>Açık</option>
                                <option value="0" <?= (($settings['debug_mode'] ?? Config::DEBUG_MODE) == '0' ? 'selected' : '') ?>>Kapalı</option>
                            </select>
                            <small class="form-text text-muted">Açıkken hatalar ekranda gösterilir, kapalıyken sadece loglanır.</small>
                        </div>
                    </div>
                     <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label class="form-label">Site Açıklaması</label>
                            <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description'] ?? 'Ödeme yönetim sisteminiz.') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require_once __DIR__ . '/../layout.php'; ?>

<script>
$(document).ready(function() {
    // Form submit işlemi
    $(document).on('submit', '#general_settings_form', function(e) {
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
                    location.reload();
                } else {
                    alert('Hata: ' + (response.message || 'Ayarlar kaydedilemedi.'));
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                alert('Ayarlar kaydedilirken bir hata oluştu: ' + xhr.responseText);
            }
        });
    });
});
</script>