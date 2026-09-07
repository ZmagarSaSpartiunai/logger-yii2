<?php

namespace app\services\logs\channels;

use app\interfaces\logs\LogChannelInterface;
use app\support\logs\LogMessage;
use yii\log\Logger;

final class FileChannel implements LogChannelInterface
{
    /**
     * @param Logger $logger
     */
    public function __construct(
        private readonly Logger $logger,
    ) {
    }

    /**
     * @param LogMessage $message
     * @return void
     */
    public function write(LogMessage $message): void
    {
        $this->logger->log(
            'Log message by File: ' . $message->format(),
            $message->level->toYiiLevel(),
            'logger.file',
        );
    }
}
