<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TablesResponse
{
    /** @var array<TableItem> */
    #[SerializedName('value')]
    private array $tables = [];

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables): TablesResponse
    {
        $this->tables = $tables;
        return $this;
    }

    public function hasTable(string $tableName): bool
    {
        if (count($this->tables) > 0) {
            foreach ($this->tables as $table) {
                if ($table->getTableName() === $tableName) {
                    return true;
                }
            }
        }

        return false;
    }
}
