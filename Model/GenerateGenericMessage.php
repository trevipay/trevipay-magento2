<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GenerateGenericMessage
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return Phrase
     * @throws NoSuchEntityException
     */
    public function execute(): Phrase
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();

        $storeName = $this->scopeConfig->getValue(
            Information::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $storePhone = $this->scopeConfig->getValue(
            Information::XML_PATH_STORE_INFO_PHONE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $storeGeneralEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($storeName) {
            if (!$storePhone || !$storeGeneralEmail) {
                return __(
                    'We are sorry! An error has occurred and we want to fix it. Please contact %1.',
                    $storeName
                );
            }

            return __(
                'We are sorry! An error has occurred and we want to fix it. Please contact %1 at %2 or %3',
                $storeName,
                $storePhone,
                $storeGeneralEmail
            );
        }
        if (!$storePhone || !$storeGeneralEmail) {
            return __(
                'We are sorry! An error has occurred and we want to fix it. Please contact us.'
            );
        }

        return __(
            'We are sorry! An error has occurred and we want to fix it. Please contact us at %1 or %2',
            $storePhone,
            $storeGeneralEmail
        );
    }
}
