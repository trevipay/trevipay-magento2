<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class LoadM2Customer
{

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(string $m2CustomerId): CustomerInterface
    {
        return $this->customerRepository->getById($m2CustomerId);
    }
}
