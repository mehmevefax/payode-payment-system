<?php
// public/api.php
// Tüm API isteklerinin ana giriş noktası

ini_set('display_errors', 0); // API'de hata gösterme, sadece logla
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Config::load(); // Konfigürasyonu yükle

use App\Config;
use App\Controllers\ApiController;
use App\Services\Logger;

header('Content-Type: application/json'); // API yanıtları her zaman JSON formatında olur

$logger = new Logger('api.log'); // API isteklerini ayrı bir log dosyasına kaydet

// Temel API anahtar doğrulamasını burada yap
$apiKey = $_POST['apiKey'] ?? $_GET['apiKey'] ?? null;
$apiSecret = $_POST['apiSecret'] ?? $_GET['apiSecret'] ?? null;

// API kimlik bilgilerini veritabanından doğrulamak için (ileride geliştirilecek)
// $apiCredentialModel = new \App\Models\ApiCredential();
// $validCredential = $apiCredentialModel->findByApiKeyAndSecret($apiKey, $apiSecret);

// Şimdilik Config'deki sabitleri kullanalım
if (empty($apiKey) || empty($apiSecret) || $apiKey !== Config::API_KEY || $apiSecret !== Config::API_SECRET) {
    $logger->warning('API kimlik doğrulama hatası', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', 'api_key' => $apiKey]);
    echo json_encode(["status" => "error", "error_code" => "1001", "message" => "API key or API secret is wrong."]);
    exit();
}

$controller = new ApiController();

// API action'ı al (örneğin ?action=deposit_callback veya POST içinde action)
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'deposit_callback':
        $controller->handleDepositCallback($_POST);
        break;
    case 'withdrawal_callback':
        $controller->handleWithdrawalCallback($_POST);
        break;
    case 'query_transaction_status':
        $controller->queryTransactionStatus($_REQUEST); // GET veya POST olabilir
        break;
    default:
        $logger->error('Geçersiz API uç noktası', ['action' => $action, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
        echo json_encode(["status" => "error", "error_code" => "1000", "message" => "Invalid API endpoint."]);
        break;
}
exit();