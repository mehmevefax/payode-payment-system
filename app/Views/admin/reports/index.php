<?php
// app/Views/admin/reports/index.php
namespace App\Views\Admin\Reports;

use App\Config;
use App\Core\Session;
use App\Models\User; // Kullanıcıları çekmek için

// Controller'dan gelen veriler (extract($data) ile erişilebilir)
// $reportData, $filters, $pageTitle, $currentSection, $activeMenu
// $filters içinde baslangic, bitis, user_id, ozel_tarih olacak
?>
<?php ob_start(); // Sayfa çıktısını tamponlamaya başla ?>

<style>
    /* Genel kapsayıcılar için padding ve margin sıfırlamaları */
    .report-section {
        padding: 0px 20px 20px 20px;
        margin-top: 0px;
    }
    .page-breadcrumb {
        margin-bottom: 20px !important;
    }
    .main-card {
        margin-bottom: 20px;
    }

    /* Rapor Filtre Formu Stili */
    .report-filter-form-container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 0.25rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }
    .report-filter-form-container .form-label {
        font-weight: 500;
        margin-bottom: 8px;
    }
    .report-filter-form-container .form-control,
    .report-filter-form-container .select2-container .select2-selection--single {
        height: 45px; /* Input ve select yüksekliklerini ayarla */
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    .report-filter-form-container .btn {
        height: 45px;
        min-width: 120px;
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }

    /* Rapor Tablosu Stili */
    .report-table-wrapper {
        overflow-x: auto;
        margin-top: 20px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        box-sizing: border-box;
    }
    .report-table {
        width: 100% !important;
        min-width: 1200px; /* Çok sayıda sütun olduğu için genişletildi */
        border-collapse: collapse;
        table-layout: fixed; /* Sütun genişliklerini sabitle */
    }
    .report-table th, .report-table td {
        white-space: nowrap;
        padding: 12px 10px; /* Daha az padding, çok sütunlu olduğu için */
        vertical-align: middle;
        border: 1px solid #dee2e6;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.9em; /* Yazı boyutunu küçült */
    }
    .report-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-align: center; /* Başlıkları ortala */
        color: #495057;
    }
    .report-table tbody tr:nth-of-type(odd) {
        background-color: #fcfcfc;
    }
    .report-table tbody tr:hover {
        background-color: #f0f0f0;
    }
    .report-table td {
        text-align: center; /* Verileri ortala */
    }
    .report-table td:first-child {
        text-align: left; /* İlk sütun (Rapor adı) sola hizalı olsun */
        font-weight: 500;
    }

    /* Dışa Aktar Buton Grubu */
    .export-buttons .btn-group .btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }
</style>

<div class="report-section">
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
        <div class="head-icons ms-2">
            <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                <i class="ti ti-chevrons-up"></i>
            </a>
        </div>
    </div>
    <div class="report-filter-form-container">
        <form method="GET" action="<?= Config::BASE_URL ?>/admin.php" class="d-flex flex-wrap align-items-end row-gap-3">
            <input type="hidden" name="page" value="reports">
            
            <div class="col-md-2 me-3">
                <label class="form-label">Rapor Başlangıç Tarihi</label>
                <input autocomplete="off" name="start_date" type="text"
                    class="form-control datetimepicker" placeholder="Başlangıç Tarihi" 
                    value="<?= htmlspecialchars($filters['start_date'] ?? date('Y-m-d 00:00:00')) ?>">
            </div>
            <div class="col-md-2 me-3">
                <label class="form-label">Rapor Bitiş Tarihi</label>
                <input autocomplete="off" name="end_date" type="text"
                    class="form-control datetimepicker" placeholder="Bitiş Tarihi" 
                    value="<?= htmlspecialchars($filters['end_date'] ?? date('Y-m-d 23:59:59')) ?>">
            </div>
            <div class="col-md-3 me-3">
                <label class="form-label">Kullanıcı</label>
                <select name="user_id" class="form-control select2">
                    <option value="">Tüm Kullanıcılar</option>
                    <?php
                    $userModel = new \App\Models\User();
                    $allUsers = $userModel->getAllUsers([], 9999, 0); // Tüm kullanıcıları çek
                    foreach ($allUsers as $u) {
                        echo '<option value="' . $u['id'] . '" ' . ((isset($filters['user_id']) && $filters['user_id'] == $u['id']) ? 'selected' : '') . '>' . htmlspecialchars($u['username']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 me-3">
                <label class="form-label">Özel Tarih Seçimi</label>
                <select name="special_date_range" class="form-control" onchange="this.form.submit()">
                    <option value="">Tarih Seçiniz</option>
                    <option value="today" <?= (($filters['special_date_range'] ?? '') == 'today' ? 'selected' : '') ?>>Bugün</option>
                    <option value="yesterday" <?= (($filters['special_date_range'] ?? '') == 'yesterday' ? 'selected' : '') ?>>Dün</option>
                    <option value="this_week" <?= (($filters['special_date_range'] ?? '') == 'this_week' ? 'selected' : '') ?>>Bu Hafta</option>
                    <option value="this_month" <?= (($filters['special_date_range'] ?? '') == 'this_month' ? 'selected' : '') ?>>Bu Ay</option>
                    <option value="this_year" <?= (($filters['special_date_range'] ?? '') == 'this_year' ? 'selected' : '') ?>>Bu Sene</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" name="generate_report" value="1" class="btn btn-primary">Raporu Oluştur</button>
            </div>
        </form>
    </div>

    <div class="card main-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
            <h5 class="card-title mb-0 flex-grow-1">Yatırma ve Çekme Raporları Özeti</h5>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <div class="dropdown me-3">
                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-secondary d-inline-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="ti ti-download me-1"></i>İndir
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-3">
                        <li><a href="<?= Config::BASE_URL ?>/admin.php?page=reports&action=export_pdf&<?= http_build_query($filters) ?>" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>PDF Olarak İndir</a></li>
                        <li><a href="<?= Config::BASE_URL ?>/admin.php?page=reports&action=export_excel&<?= http_build_query($filters) ?>" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Excel Olarak İndir</a></li>
                        <li><a href="<?= Config::BASE_URL ?>/admin.php?page=reports&action=export_csv&<?= http_build_query($filters) ?>" class="dropdown-item rounded-1"><i class="ti ti-file-type-csv me-1"></i>CSV Olarak İndir</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="report-table-wrapper">
                <table class="table table-hover table-striped report-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Rapor Kategorisi</th>
                            <th>Yatırım Toplam</th>
                            <th>Yatırım Adedi</th>
                            <th>Onaylanan Yatırım</th>
                            <th>Onaylanan Yatırım Adedi</th>
                            <th>Bekleyen Yatırım</th>
                            <th>Reddedilen Yatırım</th>
                            <th>Manuel Yatırım</th>
                            <th>Manuel Yatırım Adedi</th>
                            <th>Çekim Toplam</th>
                            <th>Çekim Adedi</th>
                            <th>Onaylanan Çekim</th>
                            <th>Bekleyen Çekim</th>
                            <th>Reddedilen Çekim</th>
                            <th>Manuel Çekim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Genel Özet</td>
                            <td><?= number_format($reportData['total_deposits_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= $reportData['total_deposits_count'] ?? 0 ?></td>
                            <td><?= number_format($reportData['approved_deposits_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= $reportData['approved_deposits_count'] ?? 0 ?></td>
                            <td><?= number_format($reportData['pending_deposits_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= number_format($reportData['rejected_deposits_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= number_format($reportData['manual_deposits_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= $reportData['manual_deposits_count'] ?? 0 ?></td>
                            <td><?= number_format($reportData['total_withdrawals_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= $reportData['total_withdrawals_count'] ?? 0 ?></td>
                            <td><?= number_format($reportData['approved_withdrawals_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= number_format($reportData['pending_withdrawals_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= number_format($reportData['rejected_withdrawals_amount'] ?? 0, 2) ?>₺</td>
                            <td><?= number_format($reportData['manual_withdrawals_amount'] ?? 0, 2) ?>₺</td>
                        </tr>
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require_once __DIR__ . '/../layout.php'; ?>

<script>
$(document).ready(function() {
    // Datetimepicker'ı başlat
    if ($.fn.datetimepicker) {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss', // SQL formatına uygun
            icons: {
                up: "fas fa-angle-up",
                down: "fas fa-angle-down",
                next: 'fas fa-angle-right',
                previous: 'fas fa-angle-left'
            }
        });
    }

    // Select2 kütüphanesini başlat
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%'
        });
    }

    // Export butonları için JavaScript
    // Bunlar controller'da handle edilecek.
    // Export butonlarına tıklandığında, formu action'ına göre submit et.
    $(document).on('click', '.export-pdf-btn, .export-excel-btn, .export-csv-btn', function(e) {
        e.preventDefault();
        const exportType = $(this).attr('class').split(' ').find(cls => cls.startsWith('export-')).replace('export-', '').replace('-btn', '');
        
        const form = $(this).closest('.dropdown').find('form'); // En yakın formu bulmaya çalış
        const currentUrlParams = new URLSearchParams(window.location.search);
        
        currentUrlParams.set('page', 'reports');
        currentUrlParams.set('action', 'export_' + exportType); // export_pdf, export_excel, export_csv
        
        // Filtre formundaki değerleri al
        const filterForm = $('.report-filter-form-container form');
        filterForm.serializeArray().forEach(item => {
            if (item.name !== 'page' && item.name !== 'generate_report') {
                currentUrlParams.set(item.name, item.value);
            }
        });

        // Yeni bir pencerede indirmeyi başlat
        window.open('<?= Config::BASE_URL ?>/admin.php?' + currentUrlParams.toString(), '_blank');
    });

    // Özel tarih seçimi değiştiğinde formu otomatik gönder
    $(document).on('change', 'select[name="special_date_range"]', function() {
        const selectedValue = $(this).val();
        const form = $(this).closest('form');
        if (selectedValue) {
            // Eğer özel tarih aralığı seçildiyse, başlangıç/bitiş tarihlerini otomatik ayarla
            let startDate = moment();
            let endDate = moment();

            switch (selectedValue) {
                case 'today':
                    startDate = moment().startOf('day');
                    endDate = moment().endOf('day');
                    break;
                case 'yesterday':
                    startDate = moment().subtract(1, 'day').startOf('day');
                    endDate = moment().subtract(1, 'day').endOf('day');
                    break;
                case 'this_week':
                    startDate = moment().startOf('week');
                    endDate = moment().endOf('week');
                    break;
                case 'this_month':
                    startDate = moment().startOf('month');
                    endDate = moment().endOf('month');
                    break;
                case 'this_year':
                    startDate = moment().startOf('year');
                    endDate = moment().endOf('year');
                    break;
            }
            form.find('input[name="start_date"]').val(startDate.format('YYYY-MM-DD HH:mm:ss'));
            form.find('input[name="end_date"]').val(endDate.format('YYYY-MM-DD HH:mm:ss'));
        }
        form.submit(); // Formu otomatik gönder
    });
});
</script>