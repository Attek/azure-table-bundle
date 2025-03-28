<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure;

use Lsyh\TableServiceBundle\Azure\Response\AzureApiResponse;

interface TableServiceInterface
{
    public function getTable(string $tableName): AzureApiResponse;

    public function createTable(string $tableName): AzureApiResponse;

    public function deleteTable(string $tableName): AzureApiResponse;

    public function getEntity(string $tableName, string $partitionKey, string $rowKey): AzureApiResponse;

    public function insertEntity(string $tableName, Entity $entity): AzureApiResponse;

    public function updateEntity(string $tableName, Entity $entity): AzureApiResponse;

    public function deleteEntity(string $tableName, string $partitionKey, string $rowKey): AzureApiResponse;
}
