<?php

namespace TreviPay\TreviPayMagento\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ReCreateWebhooks extends Field
{
    /**
     * Remove scope label and use default checkbox
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->setData('value', __('(Re)Create Webhooks'));
        $element->setData('class', 'trevipay-magento-recreate-webhooks');

        return parent::_getElementHtml($element);
    }
}
