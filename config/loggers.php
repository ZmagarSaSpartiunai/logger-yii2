<?php

use app\services\logs\channels\DatabaseChannel;
use app\services\logs\channels\EmailChannel;
use app\services\logs\channels\FileChannel;

return [
    'default' => getenv('LOGGER_DEFAULT_CHANNEL') ?: 'email',

    'channels' => [
        'email' => [
            'driver' => EmailChannel::class,
            'box' => getenv('LOGGER_EMAIL') ?: 'logs@example.com',
        ],
        'file' => [
            'driver' => FileChannel::class,
        ],
        'database' => [
            'driver' => DatabaseChannel::class,
        ],
    ],
];
