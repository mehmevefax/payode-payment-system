<?php
// app/Controllers/PaymentController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Logger;

class PaymentController {
    private $depositModel;
    private $withdrawalModel;
    private $accountModel;
    private $paymentMethodModel;
    private $userModel;
    private $logger;

    public function __construct() {
        $this->depositModel = new Deposit();
        $this->withdrawalModel = new Withdrawal();
        $this->accountModel = new Account();
        $this->paymentMethodModel = new PaymentMethod();
        $this->userModel = new User();
        $this->logger = new Logger('payment_actions.log');
    }

    // Para Yatırma İşlemlerini Listeleme
    public function listDeposits(array $queryParams) {
        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 10);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'status' => Helper::sanitizeInput($queryParams['status'] ?? ''),
            'method_slug' => Helper::sanitizeInput($queryParams['method_slug'] ?? ''),
        ];

        $methodName = 'Tüm Ödeme Yöntemleri'; // Varsayılan genel başlık
        $methodId = null;
        if (!empty($filters['method_slug'])) {
            $method = $this->paymentMethodModel->getMethodBySlug($filters['method_slug']);
            if ($method) {
                $methodName = $method['method_name'];
                $methodId = $method['id'];
                $filters['method_id'] = $methodId;
            } else {
                $filters['method_id'] = -1; // Geçersiz slug gelirse hiç kayıt gösterme
            }
        }
        
        $currentUserId = Session::getUser('id');
        $currentUserType = Session::getUser('user_type');

        $deposits = $this->depositModel->getDeposits($filters, $limit, $offset, $currentUserId, $currentUserType);
        $totalDeposits = $this->depositModel->getTotalDeposits($filters, $currentUserId, $currentUserType);

        $pendingDepositsCount = $this->depositModel->getTotalDeposits(['status' => 'pending', 'method_id' => $methodId], $currentUserId, $currentUserType);
        $rejectedDepositsCount = $this->depositModel->getTotalDeposits(['status' => 'rejected', 'method_id' => $methodId], $currentUserId, $currentUserType);
        $totalApprovedDepositsAmount = $this->depositModel->getTotalApprovedAmount(['method_id' => $methodId], $currentUserId, $currentUserType);

        $data = [
            'pageTitle' => ($methodName === 'Tüm Ödeme Yöntemleri' ? '' : htmlspecialchars($methodName) . ' ') . 'Para Yatırma İşlemleri',
            'currentSection' => 'Yatırma',
            'activeMenu' => 'deposits',
            'activeMethodSlug' => $filters['method_slug'],
            'activeMenuSub' => 'deposits',
            'deposits' => $deposits,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalDeposits / $limit),
                'total_records' => $totalDeposits,
                'limit' => $limit,
            ],
            'filters' => $filters,
            'pending_deposits_count' => $pendingDepositsCount,
            'rejected_deposits_count' => $rejectedDepositsCount,
            'total_approved_deposits_amount' => $totalApprovedDepositsAmount,
        ];

        $this->render('admin/deposits/list', $data);
    }

    // Para Yatırma İşlemi Onay/Red
    public function processDepositAction(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            $this->logger->warning('Yetkisiz işlem denemesi: Deposit Onay/Red', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        // CSRF Token kontrolü
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Deposit Onay/Red', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $islemID = (int)($postData['islemID'] ?? 0);
        $action = Helper::sanitizeInput($postData['action'] ?? '');
        $reason = Helper::sanitizeInput($postData['reason'] ?? null);

        if ($islemID === 0 || !in_array($action, ['approve', 'reject'])) {
            echo json_encode(["status" => "error", "error_code" => "1002", "message" => "Geçersiz işlem veya ID."]);
            exit();
        }

        $deposit = $this->depositModel->getById($islemID);

        if (!$deposit) {
            echo json_encode(["status" => "error", "error_code" => "1006", "message" => "İşlem bulunamadı."]);
            exit();
        }

        if ($deposit['status'] !== 'pending') {
            echo json_encode(["status" => "error", "error_code" => "1007", "message" => "İşlem zaten " . $deposit['status'] . " durumunda."]);
            exit();
        }

        $approvedBy = Session::getUser('id');
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        
        $transactionStartedAt = strtotime($deposit['transaction_date']);
        $currentTime = time();
        $processingTimeSeconds = $currentTime - $transactionStartedAt;

        $updateSuccess = $this->depositModel->updateStatus(
            $islemID,
            $newStatus,
            $approvedBy,
            $reason
        );
        
        if ($updateSuccess) {
            $this->depositModel->update('deposits', ['processing_time_seconds' => $processingTimeSeconds], "id = {$islemID}");
        }

        if ($updateSuccess) {
            $message = "Para yatırma işlemi başarıyla " . (($action === 'approve') ? 'onaylandı.' : 'reddedildi.');
            $this->logger->info($message . " (Ref ID: " . $deposit['ref_id'] . ")", ['deposit_id' => $islemID, 'status' => $newStatus, 'approved_by' => $approvedBy, 'processing_time' => $processingTimeSeconds, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);

            echo json_encode(["status" => "success", "message" => $message]);
        } else {
            $message = "Para yatırma işlemi güncellenirken bir hata oluştu.";
            $this->logger->error($message . " (Ref ID: " . $deposit['ref_id'] . ")", ['deposit_id' => $islemID, 'status' => $newStatus, 'error' => $this->depositModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "1005", "message" => $message]);
        }
        exit();
    }

    // Para Yatırma İşlemi Detayını Görüntüleme (AJAX ile)
    public function viewDepositDetail(array $queryParams) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }

        $depositId = (int)($queryParams['id'] ?? 0);
        if ($depositId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        $deposit = $this->depositModel->getById($depositId);

        if (!$deposit) {
            echo json_encode(["status" => "error", "message" => "İşlem bulunamadı."]);
            exit();
        }

        if (Session::getUser('user_type') === 'sub_user' && $deposit['user_id'] !== Session::getUser('id')) {
            echo json_encode(["status" => "error", "message" => "Bu işlemi görüntüleme yetkiniz yok."]);
            exit();
        }

        // JSON alanlarını decode et
        $deposit['account_details'] = json_decode($deposit['account_details'] ?? '{}', true);
        $deposit['client_info'] = json_decode($deposit['client_info'] ?? '{}', true);

        // Frontend için ek formatlamalar
        $deposit['status_text'] = ucfirst($deposit['status']);
        $deposit['status_class'] = '';
        if ($deposit['status'] == 'pending') $deposit['status_class'] = 'badge-warning';
        elseif ($deposit['status'] == 'approved') $deposit['status_class'] = 'badge-success';
        elseif ($deposit['status'] == 'rejected') $deposit['status_class'] = 'badge-danger';
        elseif ($deposit['status'] == 'cancelled') $deposit['status_class'] = 'badge-info';
        
        $deposit['processing_time_text'] = ($deposit['processing_time_seconds'] !== null) ? 
                                            floor($deposit['processing_time_seconds'] / 60) . ' dk ' . ($deposit['processing_time_seconds'] % 60) . ' sn' : 'N/A';
        $deposit['transaction_date_formatted'] = date('d M Y H:i:s', strtotime($deposit['transaction_date']));
        $deposit['approved_at_formatted'] = $deposit['approved_at'] ? date('d M Y H:i:s', strtotime($deposit['approved_at'])) : 'Yok';

        echo json_encode(["status" => "success", "data" => $deposit]);
        exit();
    }

    // Yeni Para Yatırma İşlemi Ekleme (Manuel)
    public function addDeposit(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            $this->logger->warning('Yetkisiz manuel yatırım ekleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        // CSRF Token kontrolü
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Manuel yatırım ekleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $username = Helper::sanitizeInput($postData['username'] ?? '');
        $namesurname = Helper::sanitizeInput($postData['namesurname'] ?? '');
        $amount = (float)($postData['amount'] ?? 0);
        $methodId = (int)($postData['method_id'] ?? 0);
        $refId = Helper::sanitizeInput($postData['ref_id'] ?? '');
        
        // JSON alanlarını doğru formatta alma ve kaydetme
        $accountDetails = json_decode($postData['account_details'] ?? '{}', true);
        if (json_last_error() !== JSON_ERROR_NONE && !empty($postData['account_details'])) {
             // Eğer JSON değilse ve boş değilse, string'i bir JSON objesi içine saralım
             $accountDetails = ['raw_input' => $postData['account_details']];
        } else if (json_last_error() !== JSON_ERROR_NONE && empty($postData['account_details'])) {
             // Boş ama geçerli JSON değilse, boş array olarak kalsın
             $accountDetails = [];
        }
        // Eğer zaten geçerli JSON array'i ise json_decode ile array olarak kalacak


        $siteInfo = Helper::sanitizeInput($postData['site_info'] ?? '');
        $groupInfo = Helper::sanitizeInput($postData['group_info'] ?? '');
        $clientInfoRaw = $postData['client_info'] ?? '{}';
        $clientInfo = json_decode($clientInfoRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE && !empty($clientInfoRaw)) {
             $clientInfo = ['raw_input' => $clientInfoRaw];
        } else if (json_last_error() !== JSON_ERROR_NONE && empty($clientInfoRaw)) {
             $clientInfo = [];
        }
        $clientInfo = array_merge($clientInfo, ['site_info' => $siteInfo, 'group_info' => $groupInfo]);


        if (empty($username) || empty($namesurname) || $amount <= 0 || $methodId <= 0 || empty($refId)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun ve miktarın 0'dan büyük olduğundan emin olun."]);
            exit();
        }

        $existingDeposit = $this->depositModel->db->query("SELECT id FROM deposits WHERE ref_id = :ref_id")->bind(':ref_id', $refId)->getRow();
        if ($existingDeposit) {
            echo json_encode(["status" => "error", "message" => "Bu Referans ID zaten sistemde mevcut."]);
            exit();
        }

        $user = $this->userModel->findByUsername($username);
        $userId = $user['id'] ?? null;

        $dataToInsert = [
            'user_id' => $userId,
            'method_id' => $methodId,
            'ref_id' => $refId,
            'amount' => $amount,
            'currency' => 'TRY',
            'user_username' => $username,
            'user_namesurname' => $namesurname,
            'status' => 'approved',
            'transaction_date' => date('Y-m-d H:i:s'),
            'approved_by' => Session::getUser('id'),
            'approved_at' => date('Y-m-d H:i:s'),
            'source_ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'client_info' => json_encode($clientInfo), // Array'i tekrar JSON string'e çeviriyoruz
            'account_details' => json_encode($accountDetails), // Array'i tekrar JSON string'e çeviriyoruz
        ];
        
        $insertSuccess = $this->depositModel->create($dataToInsert);

        if ($insertSuccess) {
            $this->logger->info("Manuel yatırım eklendi: " . $refId, ['admin_id' => Session::getUser('id'), 'deposit_id' => $this->depositModel->db->lastInsertId(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Yatırım başarıyla eklendi."]);
        } else {
            $this->logger->error("Manuel yatırım ekleme hatası: " . $refId, ['admin_id' => Session::getUser('id'), 'error' => $this->depositModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yatırım eklenirken bir hata oluştu."]);
        }
        exit();
    }

    // Para Yatırma İşlemi Silme (Manuel)
    public function deleteDeposit(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz yatırım silme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        // CSRF Token kontrolü
        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Yatırım silme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $depositId = (int)($postData['id'] ?? 0);

        if ($depositId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        if ($this->depositModel->delete($depositId)) {
            $this->logger->info("Yatırım silindi: ID " . $depositId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Yatırım başarıyla silindi."]);
        } else {
            $this->logger->error("Yatırım silme hatası: ID " . $depositId, ['admin_id' => Session::getUser('id'), 'error' => $this->depositModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yatırım silinirken bir hata oluştu."]);
        }
        exit();
    }

    // Para Çekme İşlemlerini Listeleme
    public function listWithdrawals(array $queryParams) {
        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 10);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'status' => Helper::sanitizeInput($queryParams['status'] ?? ''),
            'method_slug' => Helper::sanitizeInput($queryParams['method_slug'] ?? ''),
        ];

        $methodName = 'Tüm Ödeme Yöntemleri';
        $methodId = null;
        if (!empty($filters['method_slug'])) {
            $method = $this->paymentMethodModel->getMethodBySlug($filters['method_slug']);
            if ($method) {
                $methodName = $method['method_name'];
                $methodId = $method['id'];
                $filters['method_id'] = $methodId;
            }
        }

        $currentUserId = Session::getUser('id');
        $currentUserType = Session::getUser('user_type');

        $withdrawals = $this->withdrawalModel->getWithdrawals($filters, $limit, $offset, $currentUserId, $currentUserType);
        $totalWithdrawals = $this->withdrawalModel->getTotalWithdrawals($filters, $currentUserId, $currentUserType);

        $pendingWithdrawalsCount = $this->withdrawalModel->getTotalWithdrawals(['status' => 'pending', 'method_id' => $methodId], $currentUserId, $currentUserType);
        $rejectedWithdrawalsCount = $this->withdrawalModel->getTotalWithdrawals(['status' => 'rejected', 'method_id' => $methodId], $currentUserId, $currentUserType);
        $totalApprovedWithdrawalsAmount = $this->withdrawalModel->getTotalApprovedAmount(['method_id' => $methodId], $currentUserId, $currentUserType);

        $data = [
            'pageTitle' => ($methodName === 'Tüm Ödeme Yöntemleri' ? '' : htmlspecialchars($methodName) . ' ') . 'Para Çekme İşlemleri',
            'currentSection' => 'Çekme',
            'activeMenu' => 'withdrawals',
            'activeMethodSlug' => $filters['method_slug'],
            'activeMenuSub' => 'withdrawals',
            'withdrawals' => $withdrawals,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalWithdrawals / $limit),
                'total_records' => $totalWithdrawals,
                'limit' => $limit,
            ],
            'filters' => $filters,
            'pending_withdrawals_count' => $pendingWithdrawalsCount,
            'rejected_withdrawals_count' => $rejectedWithdrawalsCount,
            'total_approved_withdrawals_amount' => $totalApprovedWithdrawalsAmount,
        ];

        $this->render('admin/withdrawals/list', $data);
    }

    // Para Çekme İşlemi Onay/Red
    public function processWithdrawalAction(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            $this->logger->warning('Yetkisiz işlem denemesi: Withdrawal Onay/Red', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        $islemID = (int)($postData['islemID'] ?? 0);
        $action = Helper::sanitizeInput($postData['action'] ?? '');
        $reason = Helper::sanitizeInput($postData['reason'] ?? null);

        if ($islemID === 0 || !in_array($action, ['approve', 'reject'])) {
            echo json_encode(["status" => "error", "error_code" => "1002", "message" => "Geçersiz işlem veya ID."]);
            exit();
        }

        $withdrawal = $this->withdrawalModel->getById($islemID);

        if (!$withdrawal) {
            echo json_encode(["status" => "error", "error_code" => "1006", "message" => "İşlem bulunamadı."]);
            exit();
        }

        if ($withdrawal['status'] !== 'pending') {
            echo json_encode(["status" => "error", "error_code" => "1007", "message" => "İşlem zaten " . $withdrawal['status'] . " durumunda."]);
            exit();
        }

        $approvedBy = Session::getUser('id');
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

        $transactionStartedAt = strtotime($withdrawal['transaction_date']);
        $currentTime = time();
        $processingTimeSeconds = $currentTime - $transactionStartedAt;

        $updateSuccess = $this->withdrawalModel->updateStatus(
            $islemID,
            $newStatus,
            $approvedBy,
            $reason
        );
        if ($updateSuccess) {
            $this->withdrawalModel->update('withdrawals', ['processing_time_seconds' => $processingTimeSeconds], "id = {$islemID}");
        }

        if ($updateSuccess) {
            $message = "Para çekme işlemi başarıyla " . (($action === 'approve') ? 'onaylandı.' : 'reddedildi.');
            $this->logger->info($message . " (Ref ID: " . $withdrawal['ref_id'] . ")", ['withdrawal_id' => $islemID, 'status' => $newStatus, 'approved_by' => $approvedBy, 'processing_time' => $processingTimeSeconds, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);

            echo json_encode(["status" => "success", "message" => $message]);
        } else {
            $message = "Para çekme işlemi güncellenirken bir hata oluştu.";
            $this->logger->error($message . " (Ref ID: " . $withdrawal['ref_id'] . ")", ['withdrawal_id' => $islemID, 'status' => $newStatus, 'error' => $this->withdrawalModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Yatırım silinirken bir hata oluştu."]);
        }
        exit();
    }

    // Hesapları Listeleme
    public function listAccounts(array $queryParams) {
        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }
        
        $page = (int)($queryParams['p'] ?? 1);
        $limit = (int)($queryParams['sayfada'] ?? 10);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => Helper::sanitizeInput($queryParams['search'] ?? ''),
            'method_slug' => Helper::sanitizeInput($queryParams['method_slug'] ?? ''),
            'user_id' => Helper::sanitizeInput($queryParams['user_id'] ?? ''),
            'is_active' => isset($queryParams['status']) ? (bool)$queryParams['status'] : null,
        ];

        $methodName = 'Tüm Ödeme Yöntemleri';
        $methodId = null;
        if (!empty($filters['method_slug'])) {
            $method = $this->paymentMethodModel->getMethodBySlug($filters['method_slug']);
            if ($method) {
                $methodName = $method['method_name'];
                $methodId = $method['id'];
                $filters['method_id'] = $methodId;
            }
        }

        $currentUserId = Session::getUser('id');
        $currentUserType = Session::getUser('user_type');

        $accounts = $this->accountModel->getAccounts($filters, $limit, $offset, $currentUserId, $currentUserType);
        $totalAccounts = $this->accountModel->getTotalAccounts($filters, $currentUserId, $currentUserType);

        $activeAccountsCount = $this->accountModel->getTotalAccounts(['is_active' => true, 'method_id' => $methodId], $currentUserId, $currentUserType);
        $inactiveAccountsCount = $this->accountModel->getTotalAccounts(['is_active' => false, 'method_id' => $methodId], $currentUserId, $currentUserType);
        $userLinkedAccountsCount = $this->accountModel->getTotalAccounts(['user_id_not_null' => true, 'method_id' => $methodId], $currentUserId, $currentUserType);

        $data = [
            'pageTitle' => ($methodName === 'Tüm Ödeme Yöntemleri' ? '' : htmlspecialchars($methodName) . ' ') . 'Hesaplar',
            'currentSection' => 'Hesaplar',
            'activeMenu' => 'accounts',
            'activeMethodSlug' => $filters['method_slug'],
            'activeMenuSub' => 'accounts',
            'accounts' => $accounts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalAccounts / $limit),
                'total_records' => $totalAccounts,
                'limit' => $limit,
            ],
            'filters' => $filters,
            'active_accounts_count' => $activeAccountsCount,
            'inactive_accounts_count' => $inactiveAccountsCount,
            'user_linked_accounts_count' => $userLinkedAccountsCount,
        ];
        $this->render('admin/accounts/list', $data);
    }

    // Hesap Ekleme
    public function addAccount(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            $this->logger->warning('Yetkisiz manuel hesap ekleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Manuel hesap ekleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        // Hesap detaylarını doğrudan alıyoruz
        $banka = Helper::sanitizeInput($postData['banka'] ?? '');
        $iban = Helper::sanitizeInput($postData['iban'] ?? '');
        $subeKodu = Helper::sanitizeInput($postData['sube_kodu'] ?? '');
        $adSoyad = Helper::sanitizeInput($postData['ad_soyad'] ?? '');
        $hesapNo = Helper::sanitizeInput($postData['hesap_no'] ?? '');
        $minTutar = (float)($postData['min_tutar'] ?? 0);
        $maxTutar = (float)($postData['max_tutar'] ?? 0);
        // Aktif alanı array olarak gelebilir
        $aktifStatus = isset($postData['aktif']) && is_array($postData['aktif']) ? (int)$postData['aktif'][0] : (int)($postData['aktif'] ?? 1);
        $isActive = ($aktifStatus === 1); // 1 ise aktif, diğerleri pasif veya beklemede gibi düşünülebilir

        $accountName = Helper::sanitizeInput($postData['account_name'] ?? ''); // Eğer bu alan ayrı bir inputtan geliyorsa
        $methodId = (int)($postData['method_id'] ?? 0);
        $userId = (int)($postData['user_id'] ?? null);

        // Hesap detaylarını JSON objesi olarak oluştur
        $accountDetails = [
            'banka' => $banka,
            'iban' => $iban,
            'sube_kodu' => $subeKodu,
            'ad_soyad' => $adSoyad,
            'hesap_no' => $hesapNo,
            'min_tutar' => $minTutar,
            'max_tutar' => $maxTutar,
        ];
        // Eğer accountName ayrı bir inputtan gelmiyorsa, banka ve ad_soyad ile oluşturabiliriz
        if (empty($accountName)) {
            $accountName = $banka . ' - ' . $adSoyad;
        }

        if (empty($accountName) || $methodId <= 0 || empty($iban)) { // IBAN kritik alan
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun (Hesap Adı, Yöntem, IBAN)."]);
            exit();
        }

        $dataToInsert = [
            'account_name' => $accountName,
            'method_id' => $methodId,
            'account_details' => json_encode($accountDetails), // Array'i JSON string'e çevir
            'user_id' => ($userId > 0) ? $userId : null,
            'is_active' => $isActive,
        ];
        
        $insertSuccess = $this->accountModel->create($dataToInsert);

        if ($insertSuccess) {
            $this->logger->info("Manuel hesap eklendi: " . $accountName, ['admin_id' => Session::getUser('id'), 'account_id' => $this->accountModel->db->lastInsertId(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Hesap başarıyla eklendi."]);
        } else {
            $this->logger->error("Manuel hesap ekleme hatası: " . $accountName, ['admin_id' => Session::getUser('id'), 'error' => $this->accountModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Hesap eklenirken bir hata oluştu."]);
        }
        exit();
    }

    // Hesap Detayını Görüntüleme
    public function viewAccountDetail(array $queryParams) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn()) {
            echo json_encode(["status" => "error", "message" => "Oturum sona erdi."]);
            exit();
        }

        $accountId = (int)($queryParams['id'] ?? 0);
        if ($accountId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        $account = $this->accountModel->getById($accountId);

        if (!$account) {
            echo json_encode(["status" => "error", "message" => "Hesap bulunamadı."]);
            exit();
        }

        // Yetkilendirme kontrolü (alt kullanıcı sadece kendi hesabını görebilir)
        if (Session::getUser('user_type') === 'sub_user' && $account['user_id'] !== Session::getUser('id')) {
            echo json_encode(["status" => "error", "message" => "Bu hesabı görüntüleme yetkiniz yok."]);
            exit();
        }

        // JSON alanını decode et
        $account['account_details'] = json_decode($account['account_details'] ?? '{}', true);

        // Frontend için ek formatlamalar
        $account['is_active_text'] = $account['is_active'] ? 'Aktif' : 'Pasif';
        $account['status_class'] = $account['is_active'] ? 'bg-success' : 'bg-danger';
        $account['created_at_formatted'] = date('d M Y H:i:s', strtotime($account['created_at']));
        $account['user_username'] = $account['user_username'] ?? 'Sistem Hesabı';

        echo json_encode(["status" => "success", "data" => $account]);
        exit();
    }

    // Hesap Düzenleme
    public function editAccount(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            $this->logger->warning('Yetkisiz hesap düzenleme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Hesap düzenleme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $accountId = (int)($postData['id'] ?? 0);
        $accountName = Helper::sanitizeInput($postData['account_name'] ?? '');
        $methodId = (int)($postData['method_id'] ?? 0);
        // Düzenleme formundan gelen ayrı ayrı alanlar
        $banka = Helper::sanitizeInput($postData['banka'] ?? '');
        $iban = Helper::sanitizeInput($postData['iban'] ?? '');
        $subeKodu = Helper::sanitizeInput($postData['sube_kodu'] ?? '');
        $adSoyad = Helper::sanitizeInput($postData['ad_soyad'] ?? '');
        $hesapNo = Helper::sanitizeInput($postData['hesap_no'] ?? '');
        $minTutar = (float)($postData['min_tutar'] ?? 0);
        $maxTutar = (float)($postData['max_tutar'] ?? 0);
        $isActive = filter_var($postData['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN); // Select'ten geliyor


        // Hesap detaylarını JSON objesi olarak oluştur
        $accountDetails = [
            'banka' => $banka,
            'iban' => $iban,
            'sube_kodu' => $subeKodu,
            'ad_soyad' => $adSoyad,
            'hesap_no' => $hesapNo,
            'min_tutar' => $minTutar,
            'max_tutar' => $maxTutar,
        ];
        
        $userId = (int)($postData['user_id'] ?? null);

        if ($accountId === 0 || empty($accountName) || $methodId <= 0 || empty($iban)) {
            echo json_encode(["status" => "error", "message" => "Tüm gerekli alanları doldurun (Hesap Adı, Yöntem, IBAN)."]);
            exit();
        }

        $dataToUpdate = [
            'account_name' => $accountName,
            'method_id' => $methodId,
            'account_details' => json_encode($accountDetails), // Array'i JSON string'e çevir
            'user_id' => ($userId > 0) ? $userId : null,
            'is_active' => $isActive,
        ];
        
        $updateSuccess = $this->accountModel->update($accountId, $dataToUpdate, "id = {$accountId}");

        if ($updateSuccess) {
            $this->logger->info("Hesap güncellendi: ID " . $accountId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Hesap başarıyla güncellendi."]);
        } else {
            $this->logger->error("Hesap güncelleme hatası: ID " . $accountId, ['admin_id' => Session::getUser('id'), 'error' => $this->accountModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Hesap güncellenirken bir hata oluştu."]);
        }
        exit();
    }

    // Hesap Silme (Aynı kalacak)
    public function deleteAccount(array $postData) {
        header('Content-Type: application/json');

        if (!Session::isLoggedIn() || Session::getUser('user_type') !== 'admin') {
            $this->logger->warning('Yetkisiz hesap silme denemesi', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "403", "message" => "Yetkisiz erişim."]);
            exit();
        }

        if (!isset($postData['csrf_token']) || !Session::verifyCsrfToken($postData['csrf_token'])) {
            $this->logger->warning('CSRF hatası: Hesap silme', ['user_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "error_code" => "401", "message" => "Geçersiz güvenlik jetonu."]);
            exit();
        }

        $accountId = (int)($postData['id'] ?? 0);

        if ($accountId === 0) {
            echo json_encode(["status" => "error", "message" => "Geçersiz ID."]);
            exit();
        }

        if ($this->accountModel->delete($accountId)) {
            $this->logger->info("Hesap silindi: ID " . $accountId, ['admin_id' => Session::getUser('id'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "success", "message" => "Hesap başarıyla silindi."]);
        } else {
            $this->logger->error("Hesap silme hatası: ID " . $accountId, ['admin_id' => Session::getUser('id'), 'error' => $this->accountModel->db->getError(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            echo json_encode(["status" => "error", "message" => "Hesap silinirken bir hata oluştu."]);
        }
        exit();
    }

    protected function render(string $viewPath, array $data = []) {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}