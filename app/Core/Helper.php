<?php
// app/Core/Helper.php
namespace App\Core;

use App\Config;

class Helper {
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)));
    }

    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    // CSV Dışa Aktarma Yardımcı Metodu
    public static function exportToCsv(array $data, string $filename, array $headers = []) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM ekle
        
        if (!empty($headers)) {
            fputcsv($output, $headers);
        } else {
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
            }
        }
        
        foreach ($data as $row) {
            $processedRow = [];
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $processedRow[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                } else {
                    $processedRow[$key] = $value;
                }
            }
            fputcsv($output, $processedRow);
        }
        
        fclose($output);
        exit();
    }

    // PDF Dışa Aktarma (Şimdilik placeholder)
    public static function exportToPdf(array $data, string $filename, array $headers = []) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        die("PDF dışa aktarma henüz desteklenmiyor. Lütfen CSV olarak dışa aktarın.");
    }
}