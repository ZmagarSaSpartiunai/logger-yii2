<?php

namespace app\services\logs\channels;

use app\interfaces\logs\LogChannelInterface;
use app\support\logs\LogMessage;
use yii\log\Logger;

final class EmailChannel implements LogChannelInterface
{
    /**
     * @param Logger $logger
     * @param string $mailbox
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly string $mailbox,
    ) {
    }

    /**
     * @param LogMessage $message
     * @return void
     */
    public function write(LogMessage $message): void
    {
        $this->logger->log(
            "Log message by email={$this->mailbox}: " . $message->format(),
            $message->level->toYiiLevel(),
            'logger.email',
        );
    }
}
