<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Exception\NotUniqueClientReferenceIdException;
use TreviPay\TreviPayMagento\Model\ResourceModel\IsExistsClientReferenceCustomerId;

class ValidateClientReferenceCustomerIdUniquenessObserver implements ObserverInterface
{
    /**
     * @var IsExistsClientReferenceCustomerId
     */
    private $isExistsClientReferenceCustomerId;

    public function __construct(
        IsExistsClientReferenceCustomerId $isExistsClientReferenceCustomerId
    ) {
        $this->isExistsClientReferenceCustomerId = $isExistsClientReferenceCustomerId;
    }

    /**
     * @param Observer $observer
     * @throws NotUniqueClientReferenceIdException
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        /** @var Customer $customer */
        $customer = $observer->getData('customer');

        if (!$customer->getTreviPayM2ClientReferenceCustomerId()) {
            return;
        }

        $isExistsClientReferenceId = $this->isExistsClientReferenceCustomerId->execute(
            $customer->getDataModel(),
            $customer->getTreviPayM2ClientReferenceCustomerId()
        );
        if ($isExistsClientReferenceId) {
            throw new NotUniqueClientReferenceIdException(
                __('Could not assign a unique Client Reference ID to the Customer.')
            );
        }
    }
}
