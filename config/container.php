<?php

use app\enums\logs\LogChannel;
use app\exceptions\logs\UnknownLogChannelException;
use app\interfaces\logs\LogChannelFactoryInterface;
use app\interfaces\logs\LogChannelInterface;
use app\interfaces\logs\LogDispatcherInterface;
use app\services\logs\channels\DatabaseChannel;
use app\services\logs\channels\EmailChannel;
use app\services\logs\channels\FileChannel;
use app\services\logs\LogChannelFactory;
use app\services\logs\LogDispatcher;
use yii\di\Container;

$loggers = require __DIR__ . '/loggers.php';

/**
 * @return array<string, class-string<LogChannelInterface>>
 * @throws UnknownLogChannelException
 */
$drivers = function () use ($loggers): array {
    $drivers = [];

    foreach ($loggers['channels'] as $name => $options) {
        $channel = LogChannel::tryFrom($name);

        if ($channel === null) {
            throw UnknownLogChannelException::forName($name, LogChannel::values());
        }

        $drivers[$channel->value] = $options['driver'];
    }

    return $drivers;
};

/**
 * @return LogChannel
 * @throws UnknownLogChannelException
 */
$defaultChannel = function () use ($loggers, $drivers): LogChannel {
    $name = $loggers['default'];
    $channel = LogChannel::tryFrom($name);
    $configured = $drivers();

    if ($channel === null || !array_key_exists($name, $configured)) {
        throw UnknownLogChannelException::forName($name, array_keys($configured));
    }

    return $channel;
};

$factory = fn (Container $container): LogChannelFactory => new LogChannelFactory($container, $drivers());

$dispatcher = fn (Container $container): LogDispatcher => new LogDispatcher(
    $container->get(LogChannelFactoryInterface::class),
    $defaultChannel(),
    Yii::getLogger(),
);

return [
    'singletons' => [
        LogChannelFactoryInterface::class => $factory,
        LogDispatcherInterface::class => $dispatcher,
    ],

    'definitions' => [
        FileChannel::class => fn (): FileChannel => new FileChannel(Yii::getLogger()),
        DatabaseChannel::class => fn (): DatabaseChannel => new DatabaseChannel(Yii::getLogger()),
        EmailChannel::class => fn (): EmailChannel => new EmailChannel(
            Yii::getLogger(),
            $loggers['channels'][LogChannel::Email->value]['box'],
        ),
    ],
];
