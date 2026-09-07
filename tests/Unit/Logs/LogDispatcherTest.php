<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogChannel;
use app\interfaces\logs\LogChannelInterface;
use app\services\logs\LogDispatcher;
use app\support\logs\LogDeliveryResult;
use app\support\logs\LogMessage;
use app\tests\Support\Logs\FailingChannel;
use app\tests\Support\Logs\FakeChannelFactory;
use app\tests\Support\Logs\RecordingChannel;
use Codeception\Test\Unit;
use Yii;

final class LogDispatcherTest extends Unit
{
    public function testItFallsBackToTheDefaultChannel(): void
    {
        $email = new RecordingChannel();
        $file = new RecordingChannel();

        $deliveries = $this->dispatcher([
            LogChannel::Email->value => $email,
            LogChannel::File->value => $file,
        ])->dispatch(LogMessage::create('hello'));

        verify($deliveries)->arrayCount(1);
        verify($deliveries[0]->channel)->equals(LogChannel::Email);
        verify($deliveries[0]->delivered)->true();
        verify($email->written)->arrayCount(1);
        verify($file->written)->arrayCount(0);
    }

    public function testItHonoursAnExplicitChannel(): void
    {
        $email = new RecordingChannel();
        $file = new RecordingChannel();

        $deliveries = $this->dispatcher([
            LogChannel::Email->value => $email,
            LogChannel::File->value => $file,
        ])->dispatch(LogMessage::create('hello'), [LogChannel::File]);

        verify($deliveries[0]->channel)->equals(LogChannel::File);
        verify($email->written)->arrayCount(0);
        verify($file->written)->arrayCount(1);
    }

    public function testItWritesToASubsetOfChannels(): void
    {
        $email = new RecordingChannel();
        $file = new RecordingChannel();
        $database = new RecordingChannel();

        $deliveries = $this->dispatcher([
            LogChannel::Email->value => $email,
            LogChannel::File->value => $file,
            LogChannel::Database->value => $database,
        ])->dispatch(LogMessage::create('hello'), [LogChannel::Email, LogChannel::Database]);

        verify($this->channelsOf($deliveries))->equals([LogChannel::Email, LogChannel::Database]);
        verify($file->written)->arrayCount(0);
    }

    public function testItReportsAFailingChannelInsteadOfThrowing(): void
    {
        $deliveries = $this->dispatcher([
            LogChannel::Email->value => new FailingChannel(),
        ])->dispatch(LogMessage::create('hello'));

        verify($deliveries[0]->delivered)->false();
        verify($deliveries[0]->failureReason)->equals('Transport is down.');
    }

    public function testAFailingChannelDoesNotStopTheRest(): void
    {
        $file = new RecordingChannel();
        $dispatcher = $this->dispatcher([
            LogChannel::Email->value => new FailingChannel(),
            LogChannel::File->value => $file,
        ]);

        $deliveries = $dispatcher->dispatch(LogMessage::create('hello'), $dispatcher->channels());

        verify($deliveries[0]->delivered)->false();
        verify($deliveries[1]->delivered)->true();
        verify($file->written)->arrayCount(1);
    }

    public function testItExposesEveryConfiguredChannel(): void
    {
        $dispatcher = $this->dispatcher([
            LogChannel::Email->value => new RecordingChannel(),
            LogChannel::File->value => new RecordingChannel(),
        ]);

        verify($dispatcher->channels())->equals([LogChannel::Email, LogChannel::File]);
    }

    /**
     * @param array<int, LogDeliveryResult> $deliveries
     * @return array<int, LogChannel>
     */
    private function channelsOf(array $deliveries): array
    {
        return array_map(
            static fn (LogDeliveryResult $delivery): LogChannel => $delivery->channel,
            $deliveries,
        );
    }

    /**
     * @param array<string, LogChannelInterface> $channels
     * @return LogDispatcher
     */
    private function dispatcher(array $channels): LogDispatcher
    {
        return new LogDispatcher(
            new FakeChannelFactory($channels),
            LogChannel::Email,
            Yii::getLogger(),
        );
    }
}
