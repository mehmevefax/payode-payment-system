<?php
// app/Core/Database.php
namespace App\Core;

use PDO;
use PDOException;
use App\Config;

class Database {
    private static $instance = null;
    private $pdo;
    private $stmt;
    private $error;

    private function __construct() {
        $dsn = "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=" . Config::DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Veritabanı bağlantı hatası: " . $this->error);
            if (Config::DEBUG_MODE) {
                die("Veritabanı bağlantı hatası: " . $this->error);
            } else {
                die("Sistem şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.");
            }
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function query($sql) {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("SQL Hata: " . $this->error . " | Sorgu: " . $this->stmt->queryString);
            return false;
        }
    }

    public function getRow() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function getRows() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function insert($table, $data) {
        $keys = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO `{$table}` ({$keys}) VALUES ({$values})";
        $this->query($sql);
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        return $this->execute();
    }

    public function update($table, $data, $where) {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "`{$key}` = :{$key}, ";
        }
        $set = rtrim($set, ', ');

        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
        $this->query($sql);
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        return $this->execute();
    }

    public function delete($table, $where) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $this->query($sql);
        return $this->execute();
    }

    public function getError() {
        return $this->error;
    }
}