<?php

namespace TreviPay\TreviPayMagento\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPay\Model\MaskValue;
use TreviPay\TreviPay\Model\Webhook\WebhookApiCall;

class CreatedWebhooks extends Encrypted
{
    /**
     * @var MaskValue
     */
    private $maskValue;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param MaskValue $maskValue
     * @param Json $serializer
     * @param EncryptorInterface $encryptor
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        MaskValue $maskValue,
        Json $serializer,
        EncryptorInterface $encryptor,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );
        $this->maskValue = $maskValue;
        $this->serializer = $serializer;
    }

    /**
     * @param string $value
     * @return string
     */
    // phpcs:ignore
    public function processValue($value)
    {
        if (!$value) {
            return '[]';
        }

        return str_replace(
            '\/',
            '/',
            $this->serializer->serialize(
                $this->maskValue->maskValues(
                    $this->serializer->unserialize(
                        parent::processValue($value)
                    ),
                    WebhookApiCall::METHOD_NAME
                )
            )
        );
    }
}
