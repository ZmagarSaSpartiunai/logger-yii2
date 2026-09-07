<?php

namespace app\tests\Support\Logs;

use app\interfaces\logs\LogChannelInterface;
use app\support\logs\LogMessage;
use RuntimeException;

final class FailingChannel implements LogChannelInterface
{
    /**
     * @param LogMessage $message
     * @return void
     */
    public function write(LogMessage $message): void
    {
        throw new RuntimeException('Transport is down.');
    }
}
