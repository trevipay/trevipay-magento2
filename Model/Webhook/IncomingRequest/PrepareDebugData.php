<?php

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPay\Model\MaskValue;

class PrepareDebugData
{
    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var MaskValue
     */
    private $maskValue;

    public function __construct(
        Json $jsonSerializer,
        MaskValue $maskValue
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->maskValue = $maskValue;
    }

    /**
     * @param RequestInterface $request
     * @param string $webhookType
     * @param bool $shouldMaskUuid
     * @return string[]
     */
    public function execute(
        RequestInterface $request,
        string $webhookType,
        bool $shouldMaskUuid = false
    ): array {
        $rawBody = $request->getContent();
        $jsonInputData = $this->jsonSerializer->unserialize($rawBody);
        if ($shouldMaskUuid) {
            $jsonInputData = $this->maskUuid($jsonInputData);
        }

        return [
            'type'                  => 'webhook from the TreviPay API',
            'request_webhook_type'  => $webhookType,
            'request_auth_header'   => 'Authorization: Bearer '
                . $this->maskValue->mask(
                    str_replace('Bearer ', '', $request->getHeader('Authorization'))
                ),
            'request_raw'           => $this->jsonSerializer->serialize($jsonInputData),
            'request'               => $jsonInputData,
        ];
    }

    private function maskUuid(array $data): array
    {
        if (isset($data['data']) && isset($data['data']['id'])) {
            $data['data']['id'] = $this->maskValue->mask($data['data']['id']);
        }

        return $data;
    }
}
