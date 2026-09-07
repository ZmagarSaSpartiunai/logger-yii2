<?php

namespace app\tests\Support\Logs;

use app\interfaces\logs\LogChannelInterface;
use app\support\logs\LogMessage;

final class RecordingChannel implements LogChannelInterface
{
    /**
     * @var array<int, LogMessage>
     */
    public array $written = [];

    /**
     * @param LogMessage $message
     * @return void
     */
    public function write(LogMessage $message): void
    {
        $this->written[] = $message;
    }
}
