<?php

namespace app\controllers;

use app\exceptions\logs\UnknownLogChannelException;
use app\forms\logs\StoreLogForm;
use app\interfaces\logs\LogDispatcherInterface;
use app\support\logs\LogDeliveryResult;
use app\support\logs\LogMessage;
use Yii;
use yii\base\Module;
use yii\rest\Controller;

final class LogController extends Controller
{
    /**
     * @param string $id
     * @param Module $module
     * @param LogDispatcherInterface $dispatcher
     * @param array<string, mixed> $config
     */
    public function __construct(
        $id,
        $module,
        private readonly LogDispatcherInterface $dispatcher,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function verbs(): array
    {
        return [
            'store' => ['POST'],
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws UnknownLogChannelException
     */
    public function actionStore(): array
    {
        $form = new StoreLogForm();
        $form->load(Yii::$app->request->getBodyParams(), '');

        if (!$form->validate()) {
            return $this->validationFailed($form);
        }

        $message = $form->logMessage();

        return $this->respond($message, $this->dispatcher->dispatch(
            $message,
            $form->selectedChannels($this->dispatcher->channels()),
        ));
    }

    /**
     * @param StoreLogForm $form
     * @return array<string, mixed>
     */
    private function validationFailed(StoreLogForm $form): array
    {
        Yii::$app->response->setStatusCode(422);

        return [
            'message' => 'Validation failed.',
            'errors' => $form->getErrors(),
        ];
    }

    /**
     * @param LogMessage $message
     * @param array<int, LogDeliveryResult> $deliveries
     * @return array<string, mixed>
     */
    private function respond(LogMessage $message, array $deliveries): array
    {
        Yii::$app->response->setStatusCode($this->statusFor($deliveries));

        return [
            'data' => [
                'deliveries' => array_map(
                    static fn (LogDeliveryResult $delivery): array => $delivery->toArray(),
                    $deliveries,
                ),
                'level' => $message->level->value,
                'logged_at' => $message->occurredAt->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @param array<int, LogDeliveryResult> $deliveries
     * @return int
     */
    private function statusFor(array $deliveries): int
    {
        $delivered = array_filter(
            $deliveries,
            static fn (LogDeliveryResult $delivery): bool => $delivery->delivered,
        );

        if ($delivered === []) {
            return 502;
        }

        return count($delivered) === count($deliveries) ? 202 : 207;
    }
}
