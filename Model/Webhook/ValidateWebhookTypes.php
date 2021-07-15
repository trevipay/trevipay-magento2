<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook;

use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;

class ValidateWebhookTypes
{
    /**
     * @param array $webhooks
     * @return bool
     */
    public function execute(array $webhooks): bool
    {
        if (!$webhooks) {
            return false;
        }

        $foundEventTypes = [];
        foreach ($webhooks as $webhook) {
            if (isset($webhook['event_types'])) {
                foreach ($webhook['event_types'] as $eventType) {
                    $foundEventTypes[] = $eventType;
                }
            }
        }

        return !array_diff($this->getRequiredEventTypes(), array_unique($foundEventTypes));
    }

    /**
     * @return array
     */
    private function getRequiredEventTypes(): array
    {
        return [
            EventTypeInterface::BUYER_UPDATED,
            EventTypeInterface::AUTHORIZATION_UPDATED,
        ];
    }
}
