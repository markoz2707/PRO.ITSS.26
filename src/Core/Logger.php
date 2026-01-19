<?php

namespace ITSS\Core;

class Logger
{
    private static ?string $logPath = null;
    private static bool $enabled = true;
    private static string $level = 'debug';

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3
    ];

    public static function init(string $logPath, bool $enabled = true, string $level = 'debug'): void
    {
        self::$logPath = $logPath;
        self::$enabled = $enabled;
        self::$level = $level;

        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$enabled || !self::$logPath) {
            return;
        }

        if (self::LEVELS[$level] < self::LEVELS[self::$level]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        $filename = self::$logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }
}
