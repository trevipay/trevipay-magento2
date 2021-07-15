<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Sales\Model\Order;
use TreviPay\TreviPayMagento\Model\Order\TreviPayOrder;

class AddOrderStatus implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status', 'label'],
            [
                [
                    'status' => TreviPayOrder::PENDING_TREVIPAY,
                    'label' => 'Pending Application Approval'
                ],
            ]
        );

        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default', 'visible_on_front'],
            [
                [
                    'status' => TreviPayOrder::PENDING_TREVIPAY,
                    'state' => Order::STATE_PENDING_PAYMENT,
                    'is_default' => 0,
                    'visible_on_front' => 1,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status' => TreviPayOrder::PENDING_TREVIPAY]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
