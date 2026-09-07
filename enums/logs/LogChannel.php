<?php

namespace app\enums\logs;

enum LogChannel: string
{
    case Email = 'email';
    case File = 'file';
    case Database = 'database';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
