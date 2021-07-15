<?php

namespace TreviPay\TreviPayMagento\Plugin;

use Magento\Framework\Webapi\Rest\Request;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

/**
 * This class processes the links in the users `My Account` side menu, it is used to rename the TreviPay link
 * to the white labeled payment method name.
 */
class RenameCustomerMenuItem
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Request
     */
    private $request;

    public function __construct(
        ConfigProvider $configProvider,
        Request        $request
    ) {
        $this->configProvider = $configProvider;
        $this->request = $request;
    }

    public function afterGetLinks(\Magento\Customer\Block\Account\Navigation $subject, $links)
    {
        $scope = 'default';
        $scopeId = null;
        if ($this->request->getParam('website')) {
            $scope = 'website';
            $scopeId = $this->request->getParam('website');
        } elseif ($this->request->getParam('store')) {
            $scope = 'store';
            $scopeId = $this->request->getParam('store');
        }

        foreach ($links as $link) {
            $linkData = $link->getData();
            if ($linkData['type'] === \TreviPay\TreviPayMagento\Block\Customer\TreviPayLink::class) {
                if (array_key_exists('label', $linkData) && $linkData['label'] == 'DYNAMIC_CUSTOMER_MENU_ITEM_LABEL') {
                    $link->setLabel($this->configProvider->getPaymentMethodName($scope, (int)$scopeId));
                }
            }
        }

        return $links;
    }
}
