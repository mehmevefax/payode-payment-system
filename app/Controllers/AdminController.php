<?php
// app/Controllers/AdminController.php
namespace App\Controllers;

use App\Core\Session;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\Log; // Log modelini dahil et

class AdminController {
    private $depositModel;
    private $withdrawalModel;
    private $userModel;
    private $paymentMethodModel;
    private $logModel;

    public function __construct() {
        $this->depositModel = new Deposit();
        $this->withdrawalModel = new Withdrawal();
        $this->userModel = new User();
        $this->paymentMethodModel = new PaymentMethod();
        $this->logModel = new Log();
    }

    public function showDashboard() {
        // Dashboard verilerini çek
        // Bugünden sonuna kadar olan işlemler için filtreler
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $commonFilters = ['start_date' => $todayStart, 'end_date' => $todayEnd];
        
        $currentUserId = Session::getUser('id');
        $currentUserType = Session::getUser('user_type');

        $pendingDepositsCount = $this->depositModel->getTotalDeposits(array_merge($commonFilters, ['status' => 'pending']), $currentUserId, $currentUserType);
        $pendingWithdrawalsCount = $this->withdrawalModel->getTotalWithdrawals(array_merge($commonFilters, ['status' => 'pending']), $currentUserId, $currentUserType);
        
        $totalDepositsCount = $this->depositModel->getTotalDeposits($commonFilters, $currentUserId, $currentUserType);
        $totalWithdrawalsCount = $this->withdrawalModel->getTotalWithdrawals($commonFilters, $currentUserId, $currentUserType);

        $totalApprovedDepositsAmount = $this->depositModel->getTotalApprovedAmount(array_merge($commonFilters, ['status' => 'approved']), $currentUserType);
        $totalApprovedWithdrawalsAmount = $this->withdrawalModel->getTotalApprovedAmount(array_merge($commonFilters, ['status' => 'approved']), $currentUserType);
        
        $rejectedDepositsCount = $this->depositModel->getTotalDeposits(array_merge($commonFilters, ['status' => 'rejected']), $currentUserId, $currentUserType);
        $rejectedWithdrawalsCount = $this->withdrawalModel->getTotalWithdrawals(array_merge($commonFilters, ['status' => 'rejected']), $currentUserId, $currentUserType);

        $totalUsersCount = $this->userModel->getTotalUsers();
        $avgProcessingTime = $this->depositModel->getAverageProcessingTime(); // Bu metot tüm zamanlar için. Filtrelenebilir de.
        $activePaymentMethodsCount = $this->paymentMethodModel->getActiveCount();
        $totalPaymentMethodsCount = $this->paymentMethodModel->getTotalCount();
        $lastLogTime = $this->logModel->getLastLogTime();

        $data = [
            'pageTitle' => 'Yönetici Panosu',
            'currentSection' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'pending_deposits_count' => $pendingDepositsCount,
            'pending_withdrawals_count' => $pendingWithdrawalsCount,
            'total_users_count' => $totalUsersCount,
            'total_deposits_count' => $totalDepositsCount,
            'total_withdrawals_count' => $totalWithdrawalsCount,
            'total_approved_deposits_amount' => $totalApprovedDepositsAmount,
            'total_approved_withdrawals_amount' => $totalApprovedWithdrawalsAmount,
            'avg_processing_time' => $avgProcessingTime,
            'active_payment_methods_count' => $activePaymentMethodsCount,
            'total_payment_methods_count' => $totalPaymentMethodsCount,
            'last_log_time' => $lastLogTime,
        ];
        $this->render('admin/dashboard/index', $data);
    }

    public function showLogs(array $queryParams) {
        if (!Session::isLoggedIn() || (Session::getUser('user_type') !== 'admin' && Session::getUser('user_type') !== 'staff')) {
            Helper::redirect(Config::BASE_URL . '/admin');
            exit();
        }

        $page = $queryParams['p'] ?? 1;
        $limit = $queryParams['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => $queryParams['search'] ?? null,
            'log_type' => $queryParams['log_type'] ?? null,
            'level' => $queryParams['level'] ?? null,
        ];

        $logs = $this->logModel->getLogs($filters, $limit, $offset);
        $totalLogs = $this->logModel->getTotalLogs($filters);

        $data = [
            'pageTitle' => 'Log Kayıtları',
            'currentSection' => 'Loglar',
            'activeMenu' => 'logs',
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'total_records' => $totalLogs,
                'limit' => $limit,
            ],
            'filters' => $filters
        ];
        $this->render('admin/logs/index', $data);
    }

    protected function render(string $viewPath, array $data = []) {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}