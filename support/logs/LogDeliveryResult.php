<?php

namespace app\support\logs;

use app\enums\logs\LogChannel;
use Throwable;

final readonly class LogDeliveryResult
{
    /**
     * @param LogChannel $channel
     * @param bool $delivered
     * @param string|null $failureReason
     */
    public function __construct(
        public LogChannel $channel,
        public bool $delivered,
        public ?string $failureReason = null,
    ) {
    }

    /**
     * @param LogChannel $channel
     * @return self
     */
    public static function delivered(LogChannel $channel): self
    {
        return new self($channel, true);
    }

    /**
     * @param LogChannel $channel
     * @param Throwable $failure
     * @return self
     */
    public static function failed(LogChannel $channel, Throwable $failure): self
    {
        return new self($channel, false, $failure->getMessage());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'channel' => $this->channel->value,
            'delivered' => $this->delivered,
        ];

        if ($this->failureReason !== null) {
            $result['failure_reason'] = $this->failureReason;
        }

        return $result;
    }
}
