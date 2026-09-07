<?php

namespace app\forms\logs;

use app\enums\logs\LogChannel;
use app\enums\logs\LogLevel;
use app\support\logs\LogMessage;
use yii\base\Model;

class StoreLogForm extends Model
{
    public const ALL_CHANNELS = '*';

    public mixed $message = null;

    public mixed $level = null;

    public mixed $channels = null;

    /**
     * @return array<int, array<int|string, mixed>>
     */
    public function rules(): array
    {
        return [
            [['message'], 'required'],
            [['message'], 'string', 'max' => 2000],
            [['level'], 'in', 'range' => LogLevel::values()],
            [
                ['channels'],
                'each',
                'rule' => ['in', 'range' => array_merge(LogChannel::values(), [self::ALL_CHANNELS])],
            ],
        ];
    }

    /**
     * @return LogMessage
     */
    public function logMessage(): LogMessage
    {
        return LogMessage::create(
            is_string($this->message) ? $this->message : '',
            is_string($this->level) ? LogLevel::from($this->level) : LogLevel::Info,
        );
    }

    /**
     * @param array<int, LogChannel> $available
     * @return array<int, LogChannel>|null
     */
    public function selectedChannels(array $available): ?array
    {
        if (!is_array($this->channels) || $this->channels === []) {
            return null;
        }

        if (in_array(self::ALL_CHANNELS, $this->channels, true)) {
            return $available;
        }

        return array_map(
            static fn (string $name): LogChannel => LogChannel::from($name),
            array_values(array_unique($this->channels)),
        );
    }
}
