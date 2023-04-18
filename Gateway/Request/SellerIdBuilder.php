<?php

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class SellerIdBuilder extends AbstractBuilder
{
    private const SELLER_ID = 'seller_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        SubjectReader $subjectReader,
        ConfigProvider $configProvider
    ) {
        $this->subjectReader = $subjectReader;
        $this->configProvider = $configProvider;
        parent::__construct($subjectReader);
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        return [
            self::SELLER_ID => $this->configProvider->getSellerId(
                ScopeInterface::SCOPE_STORE,
                (int)$order->getStoreId()
            ),
        ];
    }
}
