<?php

namespace TreviPay\TreviPayMagento\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use TreviPay\TreviPayMagento\Model\Webhook\CheckWebhooksStatus;

class WebhookStatus extends Field
{
    /**
     * @var string
     */
    protected $_template = 'TreviPay_TreviPayMagento::system/config/form/field/webhook_status.phtml';

    /**
     * @var CheckWebhooksStatus
     */
    private $checkWebhooksStatus;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        CheckWebhooksStatus $checkWebhooksStatus,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkWebhooksStatus = $checkWebhooksStatus;
        $this->request = $request;
    }

    public function getCheckCreatedWebhooksUrl(): string
    {
        return $this->getUrl(
            'trevipay_magento/Config/CheckCreatedWebhooks/',
            $this->getUrlParams()
        );
    }

    public function getRecreateCreatedWebhooksUrl(): string
    {
        return $this->getUrl(
            'trevipay_magento/Config/ReCreateWebhooks/',
            $this->getUrlParams()
        );
    }

    public function getDeleteCreatedWebhooksUrl(): string
    {
        return $this->getUrl(
            'trevipay_magento/Config/DeleteWebhooks/',
            $this->getUrlParams()
        );
    }

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
        $urlParams = $this->getUrlParams();
        $scope = $urlParams['scope'];
        $scopeId = $urlParams['scopeId'];

        $this->setData(
            'check_result',
            $this->checkWebhooksStatus->execute($scope, $scopeId)
        );

        return $this->_toHtml();
    }

    private function getUrlParams(): array
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

        return [
            'scope' => $scope,
            'scopeId' => (int)$scopeId,
        ];
    }
}
