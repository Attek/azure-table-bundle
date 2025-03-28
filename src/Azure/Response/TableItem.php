<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TableItem
{
    #[SerializedName('TableName')]
    private string $tableName;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): TableItem
    {
        $this->tableName = $tableName;
        return $this;
    }
}
