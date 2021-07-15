<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DeleteWebhooks extends Field
{
    /**
     * @var string
     */
    protected $_template = 'TreviPay_TreviPayMagento::system/config/form/field/delete_webhooks.phtml';

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

    /**
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore
    protected function _renderValue(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
