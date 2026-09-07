<?php

namespace app\interfaces\logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;

interface LogChannelFactoryInterface
{
    /**
     * @param LogChannel $channel
     * @return LogChannelInterface
     * @throws UnknownLogChannelException
     */
    public function make(LogChannel $channel): LogChannelInterface;

    /**
     * @return array<int, LogChannel>
     */
    public function available(): array;
}
