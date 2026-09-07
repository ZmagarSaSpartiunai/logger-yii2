<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogChannel;
use app\support\logs\LogDeliveryResult;
use Codeception\Test\Unit;
use RuntimeException;

final class LogDeliveryResultTest extends Unit
{
    public function testADeliveredResultOmitsTheFailureReason(): void
    {
        $result = LogDeliveryResult::delivered(LogChannel::File);

        verify($result->toArray())->equals(['channel' => 'file', 'delivered' => true]);
    }

    public function testAFailedResultCarriesTheReason(): void
    {
        $result = LogDeliveryResult::failed(LogChannel::Email, new RuntimeException('Transport is down.'));

        verify($result->toArray())->equals([
            'channel' => 'email',
            'delivered' => false,
            'failure_reason' => 'Transport is down.',
        ]);
    }
}
