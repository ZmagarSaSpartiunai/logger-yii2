<?php

namespace app\services\logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\interfaces\logs\LogChannelFactoryInterface;
use app\interfaces\logs\LogChannelInterface;
use yii\di\Container;

final class LogChannelFactory implements LogChannelFactoryInterface
{
    /**
     * @var array<string, LogChannelInterface>
     */
    private array $resolved = [];

    /**
     * @param Container $container
     * @param array<string, class-string<LogChannelInterface>> $drivers
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $drivers,
    ) {
    }

    /**
     * @param LogChannel $channel
     * @return LogChannelInterface
     * @throws UnknownLogChannelException
     */
    public function make(LogChannel $channel): LogChannelInterface
    {
        return $this->resolved[$channel->value] ??= $this->resolve($channel);
    }

    /**
     * @return array<int, LogChannel>
     */
    public function available(): array
    {
        return array_map(
            static fn (string $name): LogChannel => LogChannel::from($name),
            array_keys($this->drivers),
        );
    }

    /**
     * @param LogChannel $channel
     * @return LogChannelInterface
     * @throws UnknownLogChannelException
     */
    private function resolve(LogChannel $channel): LogChannelInterface
    {
        $driver = $this->drivers[$channel->value] ?? null;

        if ($driver === null) {
            throw UnknownLogChannelException::forChannel($channel, array_keys($this->drivers));
        }

        return $this->container->get($driver);
    }
}
