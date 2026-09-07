<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\services\logs\channels\FileChannel;
use app\services\logs\LogChannelFactory;
use Codeception\Test\Unit;
use Yii;
use yii\di\Container;

final class LogChannelFactoryTest extends Unit
{
    public function testItResolvesTheConfiguredDriver(): void
    {
        verify($this->factory()->make(LogChannel::File))->instanceOf(FileChannel::class);
    }

    public function testItReusesAnAlreadyResolvedChannel(): void
    {
        $factory = $this->factory();

        verify($factory->make(LogChannel::File))->same($factory->make(LogChannel::File));
    }

    public function testItRejectsAChannelWithoutADriver(): void
    {
        $this->expectException(UnknownLogChannelException::class);

        $this->factory()->make(LogChannel::Database);
    }

    public function testItListsOnlyTheConfiguredChannels(): void
    {
        verify($this->factory()->available())->equals([LogChannel::File]);
    }

    /**
     * @return LogChannelFactory
     */
    private function factory(): LogChannelFactory
    {
        $container = new Container();
        $container->set(FileChannel::class, static fn (): FileChannel => new FileChannel(Yii::getLogger()));

        return new LogChannelFactory($container, [
            LogChannel::File->value => FileChannel::class,
        ]);
    }
}
