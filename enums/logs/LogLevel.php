<?php

namespace app\enums\logs;

use yii\log\Logger;

enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    /**
     * @return int
     */
    public function toYiiLevel(): int
    {
        return match ($this) {
            self::Debug => Logger::LEVEL_TRACE,
            self::Info => Logger::LEVEL_INFO,
            self::Warning => Logger::LEVEL_WARNING,
            self::Error => Logger::LEVEL_ERROR,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
