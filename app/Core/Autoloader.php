<?php
// app/Core/Autoloader.php
namespace App\Core;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            // Namespace'i dosya yoluna dönüştür
            // App\Config -> App/Config
            // App\Core\Database -> App/Core/Database
            $namespacePath = str_replace('\\', DIRECTORY_SEPARATOR, $class);

            // app/ klasörünü baz alarak dosya yolu oluştur
            // Örneğin, App\Config için: __DIR__ . '/../../' + App/Config . '.php'
            // Bu da C:\xampp\htdocs\payode\app\Config.php yapar
            $filepath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $namespacePath . '.php';

            if (file_exists($filepath)) {
                require_once $filepath;
                return true;
            }
            return false;
        });
    }
}