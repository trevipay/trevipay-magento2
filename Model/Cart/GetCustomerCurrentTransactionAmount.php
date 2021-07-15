<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Cart;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;

class GetCustomerCurrentTransactionAmount
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(
        Cart $cart,
        CurrencyConverter $currencyConverter,
        StoreManagerInterface $storeManager
    ) {
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function execute(): ?int
    {
        $quoteBaseGrandTotal = $this->cart->getQuote()->getBaseGrandTotal();
        if ($quoteBaseGrandTotal > 0) {
            $currencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();

            return (int)round($quoteBaseGrandTotal * $this->currencyConverter->getMultiplier($currencyCode));
        }

        return null;
    }
}
