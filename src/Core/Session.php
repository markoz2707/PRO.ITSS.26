<?php

namespace ITSS\Core;

class Session
{
    private static bool $started = false;

    public static function start(array $config = []): void
    {
        if (self::$started) {
            return;
        }

        if (!empty($config)) {
            if (isset($config['lifetime'])) {
                ini_set('session.gc_maxlifetime', $config['lifetime']);
            }
            if (isset($config['name'])) {
                session_name($config['name']);
            }

            $cookieParams = [
                'lifetime' => $config['lifetime'] ?? 7200,
                'path' => '/',
                'domain' => '',
                'secure' => $config['secure'] ?? false,
                'httponly' => $config['httponly'] ?? true,
                'samesite' => $config['samesite'] ?? 'Lax'
            ];
            session_set_cookie_params($cookieParams);
        }

        session_start();
        self::$started = true;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        self::clear();
        if (self::$started) {
            session_destroy();
            self::$started = false;
        }
    }

    public static function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    public static function flash(string $key, $value = null)
    {
        if ($value !== null) {
            self::set('_flash_' . $key, $value);
            return null;
        }

        $flashKey = '_flash_' . $key;
        $value = self::get($flashKey);
        self::remove($flashKey);
        return $value;
    }
}
