<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Block\Customer;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;

class TreviPayLink extends Current implements SortLinkInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        Session $customerSession,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->customerSession = $customerSession;
        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    protected function _toHtml(): string
    {
        return $this->customerSession->isLoggedIn() && $this->isModuleFullyConfigured->execute()
            ? parent::_toHtml() : '';
    }
}
