<?php
// app/Config.php
namespace App;

class Config {
    // Veritabanı Ayarları
    const DB_HOST = 'localhost';
    const DB_NAME = 'peyto';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    // Uygulama Ayarları
    const APP_NAME = 'Payode Payment System';
    const BASE_URL = 'http://localhost/payode/public'; // Projenizin public klasörüne erişim URL'si
    const DEFAULT_TIMEZONE = 'Europe/Istanbul';

    // Güvenlik Ayarları
    const API_KEY = 'ab5a307926541738d56d406fbe90cdce92cb610baeaed46a96ed4acc24ad223d';
    const API_SECRET = '4654e94b976a059b73f08d073ad41650ffb97dbf30fdb96a638f7215f46364';
    const CSRF_TOKEN_SECRET = 'your_strong_random_secret_for_csrf_please_change_me_in_production';
    const SESSION_NAME = 'payodesess';
    const SESSION_LIFETIME = 3600;

    // Hata Ayıklama Modu (Geliştirme için TRUE, Canlı için FALSE)
    const DEBUG_MODE = TRUE;

    public static function load() {
        date_default_timezone_set(self::DEFAULT_TIMEZONE);
        if (self::DEBUG_MODE) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE);
        }
    }
}