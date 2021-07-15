<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Ui\Component\Fieldset;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\ComponentVisibilityInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Fieldset;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class TreviPay extends Fieldset implements ComponentVisibilityInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        ConfigProvider $configProvider,
        RequestInterface $request,
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->request = $request;
        parent::__construct($context, $components, $data);
    }

    public function isComponentVisible(): bool
    {
        return $this->configProvider->isActive() && $this->request->getParam('id');
    }
}
