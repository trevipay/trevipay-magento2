<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

class MultishippingUnsupportedMessage extends Field
{
    private const MULTISHIPPING = 'multishipping/options/checkout_multiple';

    /**
     * @var string
     */
    protected $_template = 'TreviPay_TreviPayMagento::system/config/form/field/multishipping_unsupported_message.phtml';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Remove scope label and use default checkbox
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        if (!$this->isMultishippingEnabled()) {
            return '';
        }

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

    private function isMultishippingEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::MULTISHIPPING,
            ScopeInterface::SCOPE_STORE
        );
    }
}
