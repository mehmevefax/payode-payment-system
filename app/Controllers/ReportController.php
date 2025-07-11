<?php
// app/Controllers/ReportController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Helper;
use App\Config;
use App\Models\Log;
use App\Models\User;
use App\Models\PaymentMethod;

class ReportController {
    protected $logModel;
    protected $userModel;
    protected $paymentMethodModel;

    public function __construct() {
        $this->logModel = new Log();
        $this->userModel = new User();
        $this->paymentMethodModel = new PaymentMethod();
    }

    public function showReports(array $queryParams) {
        $pageTitle = "Raporlar";
        $currentSection = "Raporlar";
        $activeMenu = "reports"; // Sidebar'da aktif menü
        $activeMenuSub = null;

        $reportType = $queryParams['report_type'] ?? 'user_activity'; // Varsayılan rapor türü
        $filters = [
            'start_date' => $queryParams['start_date'] ?? date('Y-m-01'), // Ayın ilk günü
            'end_date' => $queryParams['end_date'] ?? date('Y-m-d'),     // Bugün
            'user_id' => $queryParams['user_id'] ?? null,
            'status' => $queryParams['status'] ?? null,
            'method_id' => $queryParams['method_id'] ?? null,
            'ip_address' => $queryParams['ip_address'] ?? null,
            'search' => $queryParams['search'] ?? null,
        ];

        $reportData = [];
        $users = $this->userModel->getAllUsers(); // Kullanıcı filtresi için
        $paymentMethods = $this->paymentMethodModel->getAllPaymentMethods(); // Ödeme yöntemi filtresi için

        switch ($reportType) {
            case 'user_activity':
                $reportData = $this->getUserActivityReport($filters);
                $pageTitle = "Kullanıcı Hareketleri Raporu";
                break;
            case 'payment_logs':
                $reportData = $this->getPaymentLogsReport($filters);
                $pageTitle = "Ödeme İşlemleri Log Raporu";
                break;
            case 'system_logs':
                $reportData = $this->getSystemLogsReport($filters);
                $pageTitle = "Genel Sistem Log Raporu";
                break;
            default:
                // Varsayılan olarak kullanıcı hareketleri
                $reportData = $this->getUserActivityReport($filters);
                $pageTitle = "Kullanıcı Hareketleri Raporu";
                $reportType = 'user_activity';
                break;
        }

        $data = [
            'pageTitle' => $pageTitle,
            'currentSection' => $currentSection,
            'activeMenu' => $activeMenu,
            'activeMenuSub' => $activeMenuSub,
            'reportType' => $reportType,
            'filters' => $filters,
            'reportData' => $reportData,
            'users' => $users,
            'paymentMethods' => $paymentMethods
        ];

        extract($data);
        require_once __DIR__ . '/../Views/admin/reports/index.php';
    }

    protected function getUserActivityReport(array $filters) {
        $logs = $this->logModel->getLogs([
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'user_id' => $filters['user_id'],
            'action_types' => ['user_login', 'user_logout', 'deposit_approved', 'deposit_rejected', 'withdrawal_approved', 'withdrawal_rejected', 'user_added', 'payment_method_added', 'account_added', 'user_edited', 'payment_method_edited', 'account_edited', 'user_deleted', 'payment_method_deleted', 'account_deleted'],
            'order_by' => 'log_date DESC'
        ]);

        // Oturum sürelerini hesaplamak için logları gruplandırabiliriz
        $userActivity = [];
        foreach ($logs as $log) {
            $userId = $log['user_id'];
            if (!isset($userActivity[$userId])) {
                $userActivity[$userId] = [
                    'username' => $log['username'],
                    'full_name' => $log['user_namesurname_full'],
                    'sessions' => [],
                    'actions' => []
                ];
            }

            // Oturum logları
            if ($log['action_type'] === 'user_login') {
                $userActivity[$userId]['sessions'][] = [
                    'type' => 'login',
                    'time' => $log['log_date'],
                    'ip_address' => $log['ip_address']
                ];
            } elseif ($log['action_type'] === 'user_logout') {
                $userActivity[$userId]['sessions'][] = [
                    'type' => 'logout',
                    'time' => $log['log_date'],
                    'ip_address' => $log['ip_address']
                ];
            } else {
                // Diğer aksiyonlar
                $userActivity[$userId]['actions'][] = [
                    'type' => $log['action_type'],
                    'description' => $log['description'],
                    'time' => $log['log_date'],
                    'ip_address' => $log['ip_address']
                ];
            }
        }

        // Oturum sürelerini ve giriş/çıkışları birleştir
        foreach ($userActivity as $userId => &$data) {
            usort($data['sessions'], function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });

            $processedSessions = [];
            $currentSession = null;

            foreach ($data['sessions'] as $sessionLog) {
                if ($sessionLog['type'] === 'login') {
                    if ($currentSession) { // Önceki oturum kapanmadan yeni giriş varsa, önceki oturumu kapat
                        $currentSession['logout_time'] = $sessionLog['time']; // Yeni giriş zamanını çıkış olarak kabul et
                        $currentSession['duration'] = strtotime($currentSession['logout_time']) - strtotime($currentSession['login_time']);
                        $processedSessions[] = $currentSession;
                    }
                    $currentSession = [
                        'login_time' => $sessionLog['time'],
                        'login_ip' => $sessionLog['ip_address'],
                        'logout_time' => null,
                        'logout_ip' => null,
                        'duration' => 0
                    ];
                } elseif ($sessionLog['type'] === 'logout') {
                    if ($currentSession) {
                        $currentSession['logout_time'] = $sessionLog['time'];
                        $currentSession['logout_ip'] = $sessionLog['ip_address'];
                        $currentSession['duration'] = strtotime($currentSession['logout_time']) - strtotime($currentSession['login_time']);
                        $processedSessions[] = $currentSession;
                        $currentSession = null;
                    } else {
                        // Logout without a preceding login (edge case)
                        $processedSessions[] = [
                            'login_time' => null,
                            'login_ip' => null,
                            'logout_time' => $sessionLog['time'],
                            'logout_ip' => $sessionLog['ip_address'],
                            'duration' => 0 // Cannot calculate duration without login
                        ];
                    }
                }
            }
            if ($currentSession && !$currentSession['logout_time']) { // Açık kalan oturumlar
                $currentSession['logout_time'] = 'Hala Aktif'; // Veya şimdiki zaman
                $currentSession['duration'] = time() - strtotime($currentSession['login_time']);
                $processedSessions[] = $currentSession;
            }
            $data['sessions'] = $processedSessions;
        }

        return array_values($userActivity);
    }

    protected function getPaymentLogsReport(array $filters) {
        $logs = $this->logModel->getLogs([
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'user_id' => $filters['user_id'],
            'action_types' => ['deposit_approved', 'deposit_rejected', 'withdrawal_approved', 'withdrawal_rejected', 'deposit_added', 'withdrawal_added'],
            'ip_address' => $filters['ip_address'],
            'order_by' => 'log_date DESC'
        ]);

        // Ödeme yöntemi filtresini manuel olarak uygula
        if (!empty($filters['method_id'])) {
            $filteredLogs = [];
            $methodName = $this->paymentMethodModel->getPaymentMethodById($filters['method_id'])['method_name'] ?? null;
            if ($methodName) {
                foreach ($logs as $log) {
                    // Log açıklamasında ödeme yöntemi adını arayabiliriz
                    if (strpos(strtolower($log['description']), strtolower($methodName)) !== false) {
                        $filteredLogs[] = $log;
                    }
                }
                $logs = $filteredLogs;
            }
        }

        return $logs;
    }

    protected function getSystemLogsReport(array $filters) {
        $logs = $this->logModel->getLogs([
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'search' => $filters['search'],
            'order_by' => 'log_date DESC'
        ]);
        return $logs;
    }

    public function exportReports(array $queryParams, string $format) {
        $reportType = $queryParams['report_type'] ?? 'user_activity';
        $filters = [
            'start_date' => $queryParams['start_date'] ?? date('Y-m-01'),
            'end_date' => $queryParams['end_date'] ?? date('Y-m-d'),
            'user_id' => $queryParams['user_id'] ?? null,
            'status' => $queryParams['status'] ?? null, // Bu log raporlarında kullanılmayabilir
            'method_id' => $queryParams['method_id'] ?? null,
            'ip_address' => $queryParams['ip_address'] ?? null,
            'search' => $queryParams['search'] ?? null,
        ];

        $reportData = [];
        $fileName = "report_" . $reportType . "_" . date('Ymd_His');
        $headers = [];

        switch ($reportType) {
            case 'user_activity':
                $reportData = $this->getUserActivityReport($filters);
                $fileName = "user_activity_report_" . date('Ymd_His');
                $headers = ['Kullanıcı Adı', 'Adı Soyadı', 'Giriş Zamanı', 'Giriş IP', 'Çıkış Zamanı', 'Çıkış IP', 'Süre (sn)', 'Aksiyon Tipi', 'Aksiyon Açıklaması', 'Aksiyon Zamanı', 'Aksiyon IP'];
                
                $exportData = [];
                foreach ($reportData as $user) {
                    // Oturumları ekle
                    foreach ($user['sessions'] as $session) {
                        $exportData[] = [
                            $user['username'],
                            $user['full_name'],
                            $session['login_time'] ?? 'N/A',
                            $session['login_ip'] ?? 'N/A',
                            $session['logout_time'] ?? 'N/A',
                            $session['logout_ip'] ?? 'N/A',
                            $session['duration'],
                            'Oturum',
                            'Giriş/Çıkış',
                            $session['login_time'] ?? $session['logout_time'], // Ana zaman
                            $session['login_ip'] ?? $session['logout_ip'] // Ana IP
                        ];
                    }
                    // Aksiyonları ekle
                    foreach ($user['actions'] as $action) {
                        $exportData[] = [
                            $user['username'],
                            $user['full_name'],
                            '', '', '', '', '', // Oturum bilgileri boş
                            $action['type'],
                            $action['description'],
                            $action['time'],
                            $action['ip_address']
                        ];
                    }
                }
                $reportData = $exportData; // Export için düzleştirilmiş veri
                break;
            case 'payment_logs':
                $reportData = $this->getPaymentLogsReport($filters);
                $fileName = "payment_logs_report_" . date('Ymd_His');
                $headers = ['Log ID', 'Kullanıcı Adı', 'Adı Soyadı', 'Aksiyon Tipi', 'Açıklama', 'IP Adresi', 'Log Tarihi'];
                break;
            case 'system_logs':
                $reportData = $this->getSystemLogsReport($filters);
                $fileName = "system_logs_report_" . date('Ymd_His');
                $headers = ['Log ID', 'Kullanıcı Adı', 'Aksiyon Tipi', 'Açıklama', 'IP Adresi', 'Log Tarihi', 'Detaylar'];
                break;
        }

        if ($format === 'pdf') {
            Helper::exportToPdf($reportData, $fileName, $headers);
        } elseif ($format === 'csv' || $format === 'excel') { // Excel için CSV kullanıyoruz
            Helper::exportToCsv($reportData, $fileName, $headers);
        } else {
            // Desteklenmeyen format
            Session::set('error_message', 'Desteklenmeyen dışa aktarma formatı.');
            Helper::redirect(Config::BASE_URL . '/admin.php?page=reports&report_type=' . $reportType);
        }
        exit();
    }
}