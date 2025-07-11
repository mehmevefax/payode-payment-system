<?php
// app/Core/Session.php
namespace App\Core;

use App\Config;

class Session {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_name(Config::SESSION_NAME);
            session_set_cookie_params(Config::SESSION_LIFETIME, '/', '', false, true);
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    public static function delete($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_unset();
        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    public static function isLoggedIn(): bool {
        return self::has('user_id');
    }

    public static function getUser($key = null) {
        $user = self::get('user_data');
        if ($key && is_array($user)) {
            return $user[$key] ?? null;
        }
        return $user;
    }

    public static function generateCsrfToken() {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    public static function verifyCsrfToken($token) {
        if (!self::has('csrf_token') || !hash_equals(self::get('csrf_token'), $token)) {
            return false;
        }
        return true;
    }

    public static function setFlash($name, $message) {
        self::set('flash_' . $name, $message);
    }

    public static function getFlash($name) {
        $message = self::get('flash_' . $name);
        self::delete('flash_' . $name);
        return $message;
    }
}