<?php

namespace TreviPay\TreviPayMagento\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetConfigId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(string $path, string $scope, int $scopeId): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            $connection->getTableName('core_config_data')
        )->where(
            'path = ?',
            $path
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        );
        $value = $connection->fetchOne($select);
        if ($value) {
            return (int)$value;
        }

        return null;
    }
}
