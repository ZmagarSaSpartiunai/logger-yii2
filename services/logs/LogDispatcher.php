<?php

namespace app\services\logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\interfaces\logs\LogChannelFactoryInterface;
use app\interfaces\logs\LogChannelInterface;
use app\interfaces\logs\LogDispatcherInterface;
use app\support\logs\LogDeliveryResult;
use app\support\logs\LogMessage;
use Throwable;
use yii\log\Logger;

final class LogDispatcher implements LogDispatcherInterface
{
    /**
     * @param LogChannelFactoryInterface $factory
     * @param LogChannel $defaultChannel
     * @param Logger $fallbackLogger
     */
    public function __construct(
        private readonly LogChannelFactoryInterface $factory,
        private readonly LogChannel $defaultChannel,
        private readonly Logger $fallbackLogger,
    ) {
    }

    /**
     * @param LogMessage $message
     * @param array<int, LogChannel>|null $channels
     * @return array<int, LogDeliveryResult>
     * @throws UnknownLogChannelException
     */
    public function dispatch(LogMessage $message, ?array $channels = null): array
    {
        $deliveries = [];

        foreach ($channels ?? [$this->defaultChannel] as $channel) {
            $deliveries[] = $this->writeTo($this->factory->make($channel), $channel, $message);
        }

        return $deliveries;
    }

    /**
     * @return array<int, LogChannel>
     */
    public function channels(): array
    {
        return $this->factory->available();
    }

    /**
     * @param LogChannelInterface $target
     * @param LogChannel $channel
     * @param LogMessage $message
     * @return LogDeliveryResult
     */
    private function writeTo(
        LogChannelInterface $target,
        LogChannel $channel,
        LogMessage $message,
    ): LogDeliveryResult {
        try {
            $target->write($message);

            return LogDeliveryResult::delivered($channel);
        } catch (Throwable $failure) {
            $this->fallbackLogger->log(
                'Log channel failed: ' . $channel->value . ': ' . $failure->getMessage(),
                Logger::LEVEL_ERROR,
                'logger.dispatch',
            );

            return LogDeliveryResult::failed($channel, $failure);
        }
    }
}
