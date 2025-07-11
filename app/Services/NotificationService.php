<?php
// app/Services/NotificationService.php
namespace App\Services;

use App\Config;
use App\Services\Logger;

class NotificationService {
    private $logger;

    public function __construct() {
        $this->logger = new Logger('notifications.log');
    }

    public function sendEmail(string $to, string $subject, string $body, string $altBody = ''): bool {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . Config::APP_NAME . ' <no-reply@' . parse_url(Config::BASE_URL, PHP_URL_HOST) . '>' . "\r\n";

        $mailSent = mail($to, $subject, $body, $headers);

        if ($mailSent) {
            $this->logger->info("E-posta gönderildi.", ['to' => $to, 'subject' => $subject]);
            return true;
        } else {
            $this->logger->error("E-posta gönderme hatası.", ['to' => $to, 'subject' => $subject, 'error' => error_get_last()['message'] ?? 'Bilinmeyen hata']);
            return false;
        }
    }
}