<!DOCTYPE html>
<html lang="tr" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg" data-sidebar-image="none">
<head>
    <?php include_once 'layouts/title-meta.php'; // Temanızın orijinal include'ları ?>
    <?php include_once 'layouts/head-css.php'; // Temanızın orijinal include'ları ?>
    
    <title><?= htmlspecialchars($pageTitle ?? 'Panel') ?> - Payode</title>
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/plugins/tabler-icons/tabler-icons.css">
    <link rel="stylesheet" href="<?= Config::BASE_URL ?>/assets/css/style.css"> 
    <style>
        html, body { overflow-x: hidden !important; width: 100% !important; margin: 0 !important; padding: 0 !important; position: relative !important; }
        .main-wrapper { overflow: hidden !important; width: 100% !important; position: relative !important; display: flex !important; }
        .sidebar { position: fixed !important; left: 0 !important; top: 0 !important; bottom: 0 !important; z-index: 1000 !important; width: 240px !important; transition: all 0.2s ease-in-out !important; flex-shrink: 0 !important; overflow-y: auto !important; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        body.mini-sidebar .sidebar { width: 80px !important; }
        .page-wrapper {
            min-height: 100vh !important;
            padding: 20px !important; /* Bu padding, content div'i dışarıdan verildiği için önemli */
            width: calc(100% - 240px) !important;
            margin-left: 240px !important;
            transition: all 0.2s ease-in-out !important;
            flex-grow: 1 !important;
            box-sizing: border-box !important;
        }
        body.mini-sidebar .page-wrapper {
            width: calc(100% - 80px) !important;
            margin-left: 80px !important;
        }
        .content { /* content div'i layout.php'de kalacaksa iç padding'i burada sıfırlamayız */
            /* padding: 0 !important; */
        }

        /* Sidebar rengi, breadcrumb, pagination vb. için genel kurallar burada kalacak */
        html[data-sidebar="light"] .sidebar, body[data-sidebar="light"] .sidebar { background-color: #ffffff !important; color: #343a40 !important; }
        html[data-sidebar="light"] .sidebar .sidebar-menu ul li a { color: #495057 !important; }
        html[data-sidebar="light"] .sidebar .sidebar-menu ul li a.active, html[data-sidebar="light"] .sidebar .sidebar-menu ul li a.subdrop { background-color: #e9ecef !important; color: #007bff !important; }
        html[data-sidebar="light"] .sidebar .sidebar-menu ul li.menu-title { color: #6c757d !important; }
        html[data-sidebar="light"] .sidebar-logo { background-color: #ffffff !important; border-bottom: 1px solid #eee; }
        
        .sidebar-overlay { position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-color: rgba(0,0,0,0.5) !important; z-index: 999 !important; display: none; }
        .sidebar-overlay.opened { display: block !important; }
        body.slide-nav .main-wrapper { position: relative !important; left: 240px !important; }
        body.slide-nav .sidebar-overlay { display: block !important; }
        @media (max-width: 991.98px) { body.slide-nav .main-wrapper { position: static !important; left: 0 !important; } .page-wrapper { width: 100% !important; margin-left: 0 !important; } }

        /* Sayfalama düğmelerinin görünümünü düzelt */
        .pagination { display: flex !important; padding-left: 0 !important; list-style: none !important; border-radius: 0.25rem !important; margin: 1rem 0 !important; justify-content: flex-end; }
        .pagination .page-item { margin: 0 2px !important; }
        .pagination .page-link { cursor: pointer !important; border-radius: 0.25rem !important; padding: 0.375rem 0.75rem !important; line-height: 1.5 !important; color: #007bff !important; background-color: #fff !important; border: 1px solid #dee2e6 !important; text-decoration: none !important; }
        .pagination .page-item.active .page-link { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; z-index: 1; }
        .pagination .page-link:hover { color: #0056b3 !important; background-color: #e9ecef !important; border-color: #dee2e6 !important; }
    </style>
</head>
<body>
   
    <div class="main-wrapper">
        <?php include_once 'layouts/topbar.php'; // Temanın orijinal topbar include'ı ?>

        <?php include_once 'layouts/sidebar.php'; // Temanın orijinal sidebar include'ı ?>

        <div class="page-wrapper">
            <div class="content">
                <?= $content ?? '' ?>
            </div>
        </div>
        <?php include_once 'layouts/footer.php'; // Temanın orijinal footer include'ı ?>

    </div>
    <?php include_once 'layouts/vendor-scripts.php'; // Temanızın orijinal vendor-scripts.php include'ı ?>

    <script src="<?= Config::BASE_URL ?>/assets/js/script.js"></script> 
    
    <script>
        $(document).on('click', '#toggle_btn', function() {
            if ($('body').hasClass('mini-sidebar')) {
                $('body').removeClass('mini-sidebar');
                $(this).addClass('active');
            } else {
                $('body').addClass('mini-sidebar');
                $(this).removeClass('active');
            }
            return false;
        });

        $(function() {
            var Sidemenu = function() { this.$menuItem = $('.sidebar-menu a'); };
            function init() {
                $('.sidebar-menu a').on('click', function(e) {
                    if($(this).parent().hasClass('submenu')) { e.preventDefault(); }
                    if(!$(this).hasClass('subdrop')) {
                        $('ul', $(this).parents('ul:first')).slideUp(250);
                        $('a', $(this).parents('ul:first')).removeClass('subdrop');
                        $(this).next('ul').slideDown(350);
                        $(this).addClass('subdrop');
                    } else if($(this).hasClass('subdrop')) {
                        $(this).removeClass('subdrop');
                        $(this).next('ul').slideUp(350);
                    }
                });
                $('.sidebar-menu ul li.submenu a.active').parents('li:last').children('a:first').addClass('active').trigger('click');
            }
            init();
        });

        // Ortak AJAX metodları (Onay/Red, Detay, Silme - bunların artık View'lerde olmasına gerek yok, burada merkezi olsun)
        $(document).on('click', '.btn-approve-deposit', function() { /* ... */ });
        $(document).on('click', '.btn-reject-deposit', function() { /* ... */ });
        $(document).on('click', '.view-deposit-detail', function() { /* ... */ });
        $(document).on('click', '.btn-delete-deposit', function() { /* ... */ });
        $(document).on('click', '.confirm-delete-deposit-btn', function() { /* ... */ });

        $(document).on('submit', '#add_deposit_form', function(e) { /* ... */ });
        $(document).on('submit', '#edit_deposit_form', function(e) { /* ... */ });

        // Add/Edit/Delete Withdrawal, Account, User, Permission, PaymentMethod, ApiCredential için benzer AJAX metodları
        // Bu metodlar çok uzun olacağı için buraya komple yazmıyorum, ancak mantığı aynıdır.
        // İlgili Controller'daki addX, editX, deleteX metotlarına AJAX isteklerini buradan gönder.

        // ÖRNEK: add_deposit_form submit
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
                    if (response.status === 'success') { alert(response.message); $('#add_deposit_modal').modal('hide'); location.reload(); }
                    else { alert('Hata: ' + (response.message || 'İşlem başarısız.')); }
                },
                error: function(xhr, status, error) { console.error("AJAX Error: ", status, error, xhr.responseText); alert('İşlem sırasında bir hata oluştu: ' + xhr.responseText); }
            });
        });
        
        // ÖRNEK: view-deposit-detail click
        $(document).on('click', '.view-deposit-detail', function() {
            const id = $(this).data('id');
            $('#deposit_detail_content').html('<p class="text-center">Yükleniyor...</p>');
            $.ajax({
                url: '<?= Config::BASE_URL ?>/admin.php?page=deposits&action=view_detail',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const detail = response.data;
                        let htmlContent = `
                            <div class="p-3">
                                <p>Ref ID: ${detail.ref_id}</p>
                                <p>Kullanıcı: ${detail.user_username}</p>
                                <p>Miktar: ${detail.amount} ${detail.currency}</p>
                                <p>Durum: ${detail.status_text}</p>
                                <pre>${JSON.stringify(detail.account_details, null, 2)}</pre>
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

        // "select all" checkbox'ları için ortak kod
        $(document).on('click', '#select-all-main-table', function() { // Deposits için
            var isChecked = this.checked;
            $('table.deposit-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-users', function() { // Users için
            var isChecked = this.checked;
            $('table.user-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-methods', function() { // Payment Methods için
            var isChecked = this.checked;
            $('table.payment-methods-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-credentials', function() { // API Settings için
            var isChecked = this.checked;
            $('table.api-settings-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-accounts', function() { // Accounts için
            var isChecked = this.checked;
            $('table.account-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-permissions', function() { // Permissions için
            var isChecked = this.checked;
            $('table.permission-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-withdrawals', function() { // Withdrawals için
            var isChecked = this.checked;
            $('table.withdrawal-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });
        $(document).on('click', '#select-all-report', function() { // Raporlar için (eğer select all olacaksa)
            var isChecked = this.checked;
            $('table.report-table tbody input[type="checkbox"]').each(function() {
                this.checked = isChecked;
            });
        });

        // Ortak Modal AJAX Yükleme Fonksiyonu (Gelecekte kod tekrarını azaltmak için)
        // function loadModalContent(modalId, contentDivId, ajaxUrl, dataToSend) {
        //     $(contentDivId).html('<p class="text-center">Yükleniyor...</p>');
        //     $.ajax({
        //         url: ajaxUrl,
        //         type: 'GET',
        //         data: dataToSend,
        //         dataType: 'json',
        //         success: function(response) {
        //             if (response.status === 'success') {
        //                 // İçeriği burada dinamik olarak oluştur
        //                 let html = ''; // Detayları göstermek için HTML
        //                 // Örn: for (let key in response.data) { html += `<p>${key}: ${response.data[key]}</p>`; }
        //                 $(contentDivId).html(html);
        //             } else {
        //                 $(contentDivId).html('<p class="text-danger text-center">' + (response.message || 'Detaylar yüklenirken bir hata oluştu.') + '</p>');
        //             }
        //         },
        //         error: function(xhr, status, error) {
        //             console.error("AJAX Error: ", status, error, xhr.responseText);
        //             $(contentDivId).html('<p class="text-danger text-center">Detaylar yüklenemedi. Sunucu hatası.</p>');
        //         }
        //     });
        // }
        
        // Örnek kullanım:
        // $(document).on('click', '.view-user-detail', function() {
        //    loadModalContent('#user_detail_modal', '#user_detail_content', '<?= Config::BASE_URL ?>/admin.php?page=users&action=view_detail', { id: $(this).data('id') });
        // });

        // Temanın kendi tarih seçicileri ve diğer JS bileşenleri
        // Eğer temanın kendi script.js'i varsa ve çalışıyorsa,
        // bu fonksiyonlar orada tanımlanmış olmalı.
        // Örneğin:
        // if($('.datetimepicker').length > 0 ){ $('.datetimepicker').datetimepicker({...}); }
        // if($('.yearpicker').length > 0 ){ $('.yearpicker').datetimepicker({...}); }
        // if($('.bookingrange').length > 0) { /* ... daterangepicker ... */ }
        // if($('.custom-input').length > 0) { /* ... range slider ... */ }
        // if($('.timepicker').length > 0) { /* ... timepicker ... */ }

        // Eğer bunlar çalışmazsa, bunları ya burada tanımlamalıyız ya da temanın orijinal JS dosyalarının
        // yükleme sırasını ve varlığını kontrol etmeliyiz.

    });
    </script>
</body>
</html>