<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogLevel;
use app\services\logs\channels\DatabaseChannel;
use app\services\logs\channels\EmailChannel;
use app\services\logs\channels\FileChannel;
use app\support\logs\LogMessage;
use Codeception\Test\Unit;
use yii\log\Logger;

final class ChannelTest extends Unit
{
    public function testFileChannelWritesThroughTheLogger(): void
    {
        $logger = $this->logger();

        (new FileChannel($logger))->write(LogMessage::create('Disk is full.', LogLevel::Error));

        verify($logger->messages)->arrayCount(1);
        verify($logger->messages[0][0])->stringContainsString('Log message by File:');
        verify($logger->messages[0][0])->stringContainsString('Disk is full.');
        verify($logger->messages[0][1])->equals(Logger::LEVEL_ERROR);
        verify($logger->messages[0][2])->equals('logger.file');
    }

    public function testDatabaseChannelWritesThroughTheLogger(): void
    {
        $logger = $this->logger();

        (new DatabaseChannel($logger))->write(LogMessage::create('Row added.'));

        verify($logger->messages[0][0])->stringContainsString('Log message by Db:');
        verify($logger->messages[0][1])->equals(Logger::LEVEL_INFO);
        verify($logger->messages[0][2])->equals('logger.database');
    }

    public function testEmailChannelReportsItsMailbox(): void
    {
        $logger = $this->logger();

        (new EmailChannel($logger, 'ops@example.com'))->write(LogMessage::create('Mail sent.'));

        verify($logger->messages[0][0])->stringContainsString('email=ops@example.com');
    }

    /**
     * @return void
     */
    public function testChannelsProduceNoOutput(): void
    {
        ob_start();
        (new FileChannel($this->logger()))->write(LogMessage::create('quiet'));
        (new DatabaseChannel($this->logger()))->write(LogMessage::create('quiet'));
        (new EmailChannel($this->logger(), 'ops@example.com'))->write(LogMessage::create('quiet'));

        verify(ob_get_clean())->equals('');
    }

    /**
     * @return Logger
     */
    private function logger(): Logger
    {
        $logger = new Logger();
        $logger->flushInterval = 1000;

        return $logger;
    }
}
