<?php

namespace app\tests\Support\Logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\interfaces\logs\LogChannelFactoryInterface;
use app\interfaces\logs\LogChannelInterface;

final class FakeChannelFactory implements LogChannelFactoryInterface
{
    /**
     * @param array<string, LogChannelInterface> $channels
     */
    public function __construct(
        private readonly array $channels,
    ) {
    }

    /**
     * @param LogChannel $channel
     * @return LogChannelInterface
     * @throws UnknownLogChannelException
     */
    public function make(LogChannel $channel): LogChannelInterface
    {
        return $this->channels[$channel->value]
            ?? throw UnknownLogChannelException::forChannel($channel, array_keys($this->channels));
    }

    /**
     * @return array<int, LogChannel>
     */
    public function available(): array
    {
        return array_map(
            static fn (string $name): LogChannel => LogChannel::from($name),
            array_keys($this->channels),
        );
    }
}
