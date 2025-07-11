<?php
// app/Services/Logger.php
namespace App\Services;

class Logger {
    private $logFile;

    public function __construct(string $logFileName = 'app.log') {
        // logs klasörü projenin kök dizininde olmalı (payode/logs/)
        $this->logFile = __DIR__ . '/../../logs/' . $logFileName; 
        
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0775, true);
        }
    }

    public function log(string $message, string $level = 'info', array $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}";

        if (!empty($context)) {
            $logEntry .= " " . json_encode($context, JSON_UNESCAPED_UNICODE); // Türkçe karakterler için UNESCAPED_UNICODE
        }

        $logEntry .= PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []) {
        $this->log($message, 'info', $context);
    }

    public function warning(string $message, array $context = []) {
        $this->log($message, 'warning', $context);
    }

    public function error(string $message, array $context = []) {
        $this->log($message, 'error', $context);
    }

    public function critical(string $message, array $context = []) {
        $this->log($message, 'critical', $context);
    }
}