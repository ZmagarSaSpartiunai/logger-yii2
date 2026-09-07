<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogLevel;
use Codeception\Test\Unit;
use yii\log\Logger;

final class LogLevelTest extends Unit
{
    public function testItMapsEveryLevelOntoAYiiLevel(): void
    {
        verify(LogLevel::Debug->toYiiLevel())->equals(Logger::LEVEL_TRACE);
        verify(LogLevel::Info->toYiiLevel())->equals(Logger::LEVEL_INFO);
        verify(LogLevel::Warning->toYiiLevel())->equals(Logger::LEVEL_WARNING);
        verify(LogLevel::Error->toYiiLevel())->equals(Logger::LEVEL_ERROR);
    }

    public function testItExposesEveryValue(): void
    {
        verify(LogLevel::values())->equals(['debug', 'info', 'warning', 'error']);
    }
}
