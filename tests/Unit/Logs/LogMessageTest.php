<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogLevel;
use app\support\logs\LogMessage;
use Codeception\Test\Unit;
use DateTimeImmutable;

final class LogMessageTest extends Unit
{
    public function testItDefaultsToTheInfoLevel(): void
    {
        verify(LogMessage::create('hello')->level)->equals(LogLevel::Info);
    }

    public function testItFormatsTimestampLevelAndText(): void
    {
        $message = new LogMessage(
            'Disk is full.',
            LogLevel::Error,
            new DateTimeImmutable('2026-09-07 14:30:00'),
        );

        verify($message->format())->equals('[07-09-2026 14:30:00] ERROR: Disk is full.');
    }
}
