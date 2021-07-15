<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin\Model\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\M2Customer;
use TreviPay\TreviPayMagento\Model\PriceFormatter;

class DataProviderWithDefaultAddressesPlugin
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(
        PriceFormatter $priceFormatter
    ) {
        $this->priceFormatter = $priceFormatter;
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

            $result[$customerId]['customer'] = $this->prepareM2CustomerData($data, $currentCustomer);
        }

        return $result;
    }

    private function prepareM2CustomerData(array $data, Customer $customer): array
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
            $customerId = $data[TreviPayCustomer::ID];
            $data[TreviPayCustomer::ID] = substr($customerId, 0, 8);
        }

        if (!empty($data[Buyer::ID])) {
            $buyerId = $data[Buyer::ID];
            $data[Buyer::ID] = substr($buyerId, 0, 8);
        }

        if (!empty($data[TreviPayCustomer::STATUS])) {
            $data[TreviPayCustomer::STATUS] = $customer->getAttribute(TreviPayCustomer::STATUS)
                ->getFrontend()
                ->getValue($customer);
        }

        if (!empty($data[Buyer::STATUS])) {
            $data[Buyer::STATUS] = $customer->getAttribute(Buyer::STATUS)
                ->getFrontend()
                ->getValue($customer);
        }

        if (!empty($data[Buyer::CLIENT_REFERENCE_BUYER_ID])) {
            $data[Buyer::CLIENT_REFERENCE_BUYER_ID] = $customer
                ->getAttribute(Buyer::CLIENT_REFERENCE_BUYER_ID)
                ->getFrontend()
                ->getValue($customer);
        }

        if (!empty($data[TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID])) {
            $data[TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID] = $customer
                ->getAttribute(TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID)
                ->getFrontend()
                ->getValue($customer);
        }

        $currency = '';
        if (!empty($data[Buyer::CURRENCY])) {
            $currency = $data[Buyer::CURRENCY];
        }

        if (isset($data[Buyer::CREDIT_LIMIT])) {
            $data[Buyer::CREDIT_LIMIT] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                (float) $data[Buyer::CREDIT_LIMIT],
                $currency
            );
        }

        if (isset($data[Buyer::CREDIT_AVAILABLE])) {
            $data[Buyer::CREDIT_AVAILABLE] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                (float) $data[Buyer::CREDIT_AVAILABLE],
                $currency
            );
        }

        if (isset($data[Buyer::CREDIT_BALANCE])) {
            $data[Buyer::CREDIT_BALANCE] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                (float) $data[Buyer::CREDIT_BALANCE],
                $currency
            );
        }

        if (isset($data[Buyer::CREDIT_AUTHORIZED])) {
            $data[Buyer::CREDIT_AUTHORIZED] = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat(
                (float)$data[Buyer::CREDIT_AUTHORIZED],
                $currency
            );
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
