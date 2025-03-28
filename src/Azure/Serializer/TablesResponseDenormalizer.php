<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Serializer;

use Lsyh\TableServiceBundle\Azure\Response\TableItem;
use Lsyh\TableServiceBundle\Azure\Response\TablesResponse;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TablesResponseDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): TablesResponse
    {
        $tables = [];
        foreach ($data['value'] as $tableData) {
            $tableItem = new TableItem();
            $tableItem->setTableName($tableData['TableName']);
            $tables[] = $tableItem;
        }

        $tablesResponse = new TablesResponse();
        $tablesResponse->setTables($tables);

        return $tablesResponse;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return TablesResponse::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TablesResponse::class => true,
        ];
    }
}
