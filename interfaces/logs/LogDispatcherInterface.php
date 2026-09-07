<?php

namespace app\interfaces\logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\support\logs\LogDeliveryResult;
use app\support\logs\LogMessage;

interface LogDispatcherInterface
{
    /**
     * @param LogMessage $message
     * @param array<int, LogChannel>|null $channels
     * @return array<int, LogDeliveryResult>
     * @throws UnknownLogChannelException
     */
    public function dispatch(LogMessage $message, ?array $channels = null): array;

    /**
     * @return array<int, LogChannel>
     */
    public function channels(): array;
}
