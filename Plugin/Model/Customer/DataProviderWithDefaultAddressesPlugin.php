<?php


namespace TreviPay\TreviPayMagento\Plugin\Model\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\M2Customer;
use TreviPay\TreviPayMagento\Model\PriceFormatter;

use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Api\Data\Customer\CustomerResponseInterface;
use TreviPay\TreviPay\Model\Buyer\BuyerApiCall;
use TreviPay\TreviPay\Model\Customer\CustomerApiCall;

class DataProviderWithDefaultAddressesPlugin
{
    private PriceFormatter $priceFormatter;
    private BuyerApiCall $trevipayBuyerAPI;
    private BuyerResponseInterface $trevipayBuyer;
    private CustomerApiCall $trevipayCustomerAPI;
    private CustomerResponseInterface $trevipayCustomer;

    public function __construct(
        PriceFormatter $priceFormatter,
        BuyerApiCall $buyerApiCall,
        CustomerApiCall $customerApiCall,
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->trevipayBuyerAPI = $buyerApiCall;
        $this->trevipayCustomerAPI = $customerApiCall;
    }

    /**
     * Prepare values of the TreviPay TreviPayMagento related customer attributes for the TreviPayMagento section
     * at the Customer Edit Page in the Magento Admin Panel
     *
     * @see \Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses::getData()
     *
     * @param DataProviderWithDefaultAddresses $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(DataProviderWithDefaultAddresses $subject, array $result): array
    {
        $items = $subject->getCollection()->getItems();
        if (empty($items)) {
            return $result;
        }

        $customers = [];
        /** @var Customer $customer */
        foreach ($items as $customer) {
            $customers[$customer->getId()] = $customer;
        }

        foreach ($result as $customerId => $customerData) {
            if (!isset($customerData['customer']) || !is_array($customerData['customer'])) {
                continue;
            }

            $data = $customerData['customer'];
            $currentCustomer = $customers[$customerId];

            $result[$customerId]['customer'] = $this->prepareCustomerData($data, $currentCustomer);
        }

        return $result;
    }

    private function prepareCustomerData(array $data, Customer $customer): array
    {
        $data[M2Customer::EMPTY_TREVIPAY_FIELDS_MESSAGE] = __(
            'Any empty TreviPay fields will be populated when the Magento Customer signs in to the ' .
            'TreviPay Checkout App, or the TreviPay Customer and TreviPay Buyer status is no longer pending'
        );
        $data[M2Customer::HAS_EMPTY_TREVIPAY_FIELDS] = $this->hasEmptyTreviPayFields($data);

        if ($this->hasNeverLinkedToTreviPay($data)) {
            return $data;
        }

        // to prevent the display of null state fields (i.e., 0.0)
        if (!$this->hasBuyerWebhookProcessed($data)) {
            unset($data[Buyer::CREDIT_BALANCE]);
            unset($data[Buyer::CREDIT_AUTHORIZED]);
            unset($data[Buyer::CREDIT_AVAILABLE]);
            unset($data[Buyer::CREDIT_LIMIT]);
            unset($data[Buyer::STATUS]);
        }

        if (!$this->hasCustomerWebhookProcessed($data)) {
            unset($data[TreviPayCustomer::STATUS]);
        }

        if (!empty($data[TreviPayCustomer::ID])) {
            $this->trevipayCustomer = $this->trevipayCustomerAPI->retrieve($data[TreviPayCustomer::ID]);
            $data[TreviPayCustomer::ID] = substr($this->trevipayCustomer->getId(), 0, 8);
        }

        if (!empty($data[Buyer::ID])) {
            $this->trevipayBuyer = $this->trevipayBuyerAPI->retrieve($data[Buyer::ID]);
            $data[Buyer::ID] = substr($this->trevipayBuyer->getId(), 0, 8);
        }

        if (!empty($this->trevipayCustomer)) {
            if (!empty($this->trevipayCustomer->getCustomerStatus())) {
                $data[TreviPayCustomer::STATUS] = $this->trevipayCustomer->getCustomerStatus();
            }

            if (!empty($this->trevipayCustomer->getClientReferenceCustomerId())) {
                $data[TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID] = $this->trevipayCustomer->getClientReferenceCustomerId();
            }
        }

        if (!empty($this->trevipayBuyer)) {
            if (!empty($this->trevipayBuyer->getBuyerStatus())) {
                $data[Buyer::STATUS] = $this->trevipayBuyer->getBuyerStatus();
            }

            if (!empty($this->trevipayBuyer->getClientReferenceBuyerId())) {
                $data[Buyer::CLIENT_REFERENCE_BUYER_ID] = $this->trevipayBuyer->getClientReferenceBuyerId();
            }

            if ($this->trevipayBuyer->getCreditLimit() !== null) {
                $data[Buyer::CREDIT_LIMIT] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                    (float) $this->trevipayBuyer->getCreditLimit(),
                    $this->trevipayBuyer->getCurrency()
                );
            }

            if ($this->trevipayBuyer->getCreditAvailable() !== null) {
                $data[Buyer::CREDIT_AVAILABLE] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                    (float) $this->trevipayBuyer->getCreditAvailable(),
                    $this->trevipayBuyer->getCurrency()
                );
            }

            if ($this->trevipayBuyer->getCreditBalance() !== null) {
                $data[Buyer::CREDIT_BALANCE] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                    (float) $this->trevipayBuyer->getCreditBalance(),
                    $this->trevipayBuyer->getCurrency()
                );
            }

            if ($this->trevipayBuyer->getCreditAuthorized() !== null) {
                $data[Buyer::CREDIT_AUTHORIZED] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                    (float) $this->trevipayBuyer->getCreditAuthorized(),
                    $this->trevipayBuyer->getCurrency()
                );
            }
        }

        return $data;
    }

    private function hasEmptyTreviPayFields(array $data): bool
    {
        return !$this->hasCustomerWebhookProcessed($data)
            || !$this->hasBuyerWebhookProcessed($data);
    }

    private function hasCustomerWebhookProcessed(array $data): bool
    {
        return isset($data[TreviPayCustomer::ID]);
    }

    private function hasBuyerWebhookProcessed(array $data): bool
    {
        return isset($data[Buyer::ID]);
    }

    private function hasNeverLinkedToTreviPay(array $data): bool
    {
        return !isset($data[TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID])
            && !isset($data[Buyer::CLIENT_REFERENCE_BUYER_ID]);
    }
}
