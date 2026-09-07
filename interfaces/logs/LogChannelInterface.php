<?php

namespace app\interfaces\logs;

use app\support\logs\LogMessage;

interface LogChannelInterface
{
    /**
     * @param LogMessage $message
     * @return void
     */
    public function write(LogMessage $message): void;
}
