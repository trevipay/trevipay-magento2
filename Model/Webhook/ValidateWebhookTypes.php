<?php

namespace TreviPay\TreviPayMagento\Model\Webhook;

use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;

class ValidateWebhookTypes
{
    /**
     * @param array $webhooks
     * @return bool
     */
    public function execute(array $webhooks, string $baseUrl): bool
    {
        if (!$webhooks) {
            return false;
        }

        $foundEventTypes = [];
        foreach ($webhooks as $webhook) {
            if (isset($webhook['event_types']) && strpos($webhook['webhook_url'], $baseUrl) === 0) {
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
            EventTypeInterface::BUYER_CREATED,
            EventTypeInterface::BUYER_UPDATED,
            EventTypeInterface::CUSTOMER_CREATED,
            EventTypeInterface::CUSTOMER_UPDATED,
            EventTypeInterface::AUTHORIZATION_UPDATED
        ];
    }
}
