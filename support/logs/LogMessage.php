<?php

namespace app\support\logs;

use app\enums\logs\LogLevel;
use DateTimeImmutable;

final readonly class LogMessage
{
    /**
     * @param string $text
     * @param LogLevel $level
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $text,
        public LogLevel $level,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @param string $text
     * @param LogLevel $level
     * @return self
     */
    public static function create(string $text, LogLevel $level = LogLevel::Info): self
    {
        return new self($text, $level, new DateTimeImmutable());
    }

    /**
     * @return string
     */
    public function format(): string
    {
        return sprintf(
            '[%s] %s: %s',
            $this->occurredAt->format('d-m-Y H:i:s'),
            strtoupper($this->level->value),
            $this->text,
        );
    }
}
