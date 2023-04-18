<?php

namespace TreviPay\TreviPayMagento\Gateway\Request\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        TransferBuilder $transferBuilder,
        ConfigProvider $configProvider
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->configProvider = $configProvider;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     * @throws LocalizedException
     */
    public function create(array $request): TransferInterface
    {
        return $this->transferBuilder
            ->setBody($request)
            ->setMethod('POST')
            ->setHeaders($this->getHeaders())
            ->setUri($this->getUrl())
            ->build();
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    private function getUrl(): string
    {
        return $this->configProvider->getUri('authorizations');
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => $this->configProvider->getApiKey(),
            'Content-Type' => 'application/json',
        ];
    }
}
