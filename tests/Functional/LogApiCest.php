<?php

namespace app\tests\Functional;

use app\enums\logs\LogChannel;
use app\forms\logs\StoreLogForm;
use app\interfaces\logs\LogChannelFactoryInterface;
use app\interfaces\logs\LogChannelInterface;
use app\tests\Support\FunctionalTester;
use app\tests\Support\Logs\FailingChannel;
use app\tests\Support\Logs\FakeChannelFactory;
use app\tests\Support\Logs\RecordingChannel;
use Yii;

final class LogApiCest
{
    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function _before(FunctionalTester $I): void
    {
        $this->restoreRealChannels();
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itLogsToTheDefaultChannel(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', ['message' => 'hello']);

        $I->seeResponseCodeIs(202);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [['channel' => 'email', 'delivered' => true]],
                'level' => 'info',
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itLogsToAnExplicitChannel(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', [
            'message' => 'hello',
            'channels' => [LogChannel::File->value],
            'level' => 'warning',
        ]);

        $I->seeResponseCodeIs(202);
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [['channel' => 'file', 'delivered' => true]],
                'level' => 'warning',
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itLogsToASubsetOfChannels(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', [
            'message' => 'hello',
            'channels' => [LogChannel::Email->value, LogChannel::Database->value],
        ]);

        $I->seeResponseCodeIs(202);
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [
                    ['channel' => 'email', 'delivered' => true],
                    ['channel' => 'database', 'delivered' => true],
                ],
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function theWildcardReachesEveryChannel(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', [
            'message' => 'hello',
            'channels' => [StoreLogForm::ALL_CHANNELS],
        ]);

        $I->seeResponseCodeIs(202);
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [
                    ['channel' => 'email', 'delivered' => true],
                    ['channel' => 'file', 'delivered' => true],
                    ['channel' => 'database', 'delivered' => true],
                ],
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function aPartialFailureReportsMultiStatus(FunctionalTester $I): void
    {
        $this->useChannels([
            LogChannel::Email->value => new FailingChannel(),
            LogChannel::File->value => new RecordingChannel(),
        ]);

        $I->sendPost('/api/logs', [
            'message' => 'hello',
            'channels' => [StoreLogForm::ALL_CHANNELS],
        ]);

        $I->seeResponseCodeIs(207);
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [
                    [
                        'channel' => 'email',
                        'delivered' => false,
                        'failure_reason' => 'Transport is down.',
                    ],
                    ['channel' => 'file', 'delivered' => true],
                ],
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function aFullyFailingDeliveryReportsBadGateway(FunctionalTester $I): void
    {
        $this->useChannels([LogChannel::Email->value => new FailingChannel()]);

        $I->sendPost('/api/logs', ['message' => 'hello']);

        $I->seeResponseCodeIs(502);
        $I->seeResponseContainsJson([
            'data' => [
                'deliveries' => [['channel' => 'email', 'delivered' => false]],
            ],
        ]);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itRequiresAMessage(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', []);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.errors.message');
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itRejectsAnUnknownChannel(FunctionalTester $I): void
    {
        $I->sendPost('/api/logs', ['message' => 'hello', 'channels' => ['carrier-pigeon']]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.errors.channels');
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function itRejectsAWrongHttpMethod(FunctionalTester $I): void
    {
        $I->sendGet('/api/logs');

        $I->seeResponseCodeIs(405);
    }

    /**
     * @param array<string, LogChannelInterface> $channels
     * @return void
     */
    private function useChannels(array $channels): void
    {
        $this->restoreRealChannels();

        Yii::$container->setSingleton(
            LogChannelFactoryInterface::class,
            new FakeChannelFactory($channels),
        );
    }

    /**
     * @return void
     */
    private function restoreRealChannels(): void
    {
        Yii::configure(Yii::$container, require Yii::getAlias('@app/config/container.php'));
    }
}
