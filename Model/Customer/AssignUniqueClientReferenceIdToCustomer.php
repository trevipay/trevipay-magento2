<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use TreviPay\TreviPayMagento\Exception\NotUniqueClientReferenceIdException;
use TreviPay\TreviPayMagento\Model\ResourceModel\IsExistsClientReferenceCustomerId;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

class AssignUniqueClientReferenceIdToCustomer
{
    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var IsExistsClientReferenceCustomerId
     */
    private $isExistsClientReferenceCustomerId;

    public function __construct(
        UuidGenerator $uuidGenerator,
        CustomerRepositoryInterface $customerRepository,
        IsExistsClientReferenceCustomerId $isExistsClientReferenceCustomerId
    ) {
        $this->uuidGenerator = $uuidGenerator;
        $this->customerRepository = $customerRepository;
        $this->isExistsClientReferenceCustomerId = $isExistsClientReferenceCustomerId;
    }

    /**
     * @param CustomerInterface $m2Customer
     * @param int $attemptNumber
     * @return string
     * @throws NotUniqueClientReferenceIdException
     * @throws InputException
     * @throws LocalizedException
     * @throws InputMismatchException
     */
    public function execute(CustomerInterface $m2Customer, int $attemptNumber = 0): string
    {
        try {
            $clientReferenceCustomerId = $this->uuidGenerator->execute();
            $treviPayCustomer = new TreviPayCustomer($m2Customer);
            $treviPayCustomer->setClientReferenceCustomerId($clientReferenceCustomerId);

            if ($this->isExistsClientReferenceCustomerId->execute($m2Customer, $clientReferenceCustomerId)) {
                throw new NotUniqueClientReferenceIdException(
                    __('Could not assign a unique Client Reference ID to the Customer.')
                );
            }

            $this->customerRepository->save($m2Customer);
        } catch (LocalizedException | NotUniqueClientReferenceIdException $e) {
            if ($attemptNumber >= 10) {
                throw new NotUniqueClientReferenceIdException(
                    __('Could not assign a unique Client Reference ID to the Customer.')
                );
            }
            $attemptNumber++;

            return $this->execute($m2Customer, $attemptNumber);
        }

        return $clientReferenceCustomerId;
    }
}
