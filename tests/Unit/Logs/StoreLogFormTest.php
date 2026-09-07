<?php

namespace app\tests\Unit\Logs;

use app\enums\logs\LogChannel;
use app\enums\logs\LogLevel;
use app\forms\logs\StoreLogForm;
use Codeception\Test\Unit;

final class StoreLogFormTest extends Unit
{
    public function testItAcceptsAMessageAlone(): void
    {
        $form = $this->form(['message' => 'hello']);

        verify($form->validate())->true();
        verify($form->selectedChannels(LogChannel::cases()))->null();
        verify($form->logMessage()->level)->equals(LogLevel::Info);
    }

    public function testItResolvesTheSelectedChannelsAndLevel(): void
    {
        $form = $this->form([
            'message' => 'hello',
            'channels' => [LogChannel::File->value, LogChannel::Database->value],
            'level' => LogLevel::Warning->value,
        ]);

        verify($form->validate())->true();
        verify($form->selectedChannels(LogChannel::cases()))
            ->equals([LogChannel::File, LogChannel::Database]);
        verify($form->logMessage()->level)->equals(LogLevel::Warning);
    }

    public function testTheWildcardExpandsToEveryAvailableChannel(): void
    {
        $form = $this->form(['message' => 'hello', 'channels' => [StoreLogForm::ALL_CHANNELS]]);

        verify($form->validate())->true();
        verify($form->selectedChannels([LogChannel::Email, LogChannel::File]))
            ->equals([LogChannel::Email, LogChannel::File]);
    }

    public function testItDeduplicatesRepeatedChannels(): void
    {
        $form = $this->form([
            'message' => 'hello',
            'channels' => [LogChannel::File->value, LogChannel::File->value],
        ]);

        verify($form->validate())->true();
        verify($form->selectedChannels(LogChannel::cases()))->equals([LogChannel::File]);
    }

    public function testItRequiresAMessage(): void
    {
        $form = $this->form([]);

        verify($form->validate())->false();
        verify($form->getErrors())->arrayHasKey('message');
    }

    public function testItRejectsAnUnknownChannel(): void
    {
        $form = $this->form(['message' => 'hello', 'channels' => ['carrier-pigeon']]);

        verify($form->validate())->false();
        verify($form->getErrors())->arrayHasKey('channels');
    }

    public function testItRejectsChannelsThatAreNotAList(): void
    {
        $form = $this->form(['message' => 'hello', 'channels' => 'file']);

        verify($form->validate())->false();
        verify($form->getErrors())->arrayHasKey('channels');
    }

    public function testItRejectsAnUnknownLevel(): void
    {
        $form = $this->form(['message' => 'hello', 'level' => 'catastrophic']);

        verify($form->validate())->false();
        verify($form->getErrors())->arrayHasKey('level');
    }

    /**
     * @param array<string, mixed> $data
     * @return StoreLogForm
     */
    private function form(array $data): StoreLogForm
    {
        $form = new StoreLogForm();
        $form->load($data, '');

        return $form;
    }
}
