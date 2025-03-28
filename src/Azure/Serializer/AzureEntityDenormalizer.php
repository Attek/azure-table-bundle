<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Serializer;

use Lsyh\TableServiceBundle\Azure\AzureEntity;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AzureEntityDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): AzureEntity
    {
        if (!is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array.');
        }

        foreach ($data as $name => $value) {
            if (str_ends_with($name, '@odata.type')) {
                unset($data[$name]);
                $propertyName = str_replace('@odata.type', '', $name);

                $data[$propertyName] = match ($value) {
                    'Edm.Int32' => (int)$data[$propertyName],
                    'Edm.Boolean' => (bool)$data[$propertyName],
                    'Edm.Double' => (float)$data[$propertyName],
                    'Edm.Binary' => base64_decode($data[$propertyName]),
                    'Edm.DateTime' => new \DateTime($data[$propertyName]),
                    default => (string)$data[$propertyName],
                };
            }
        }

        $azureEntity = new AzureEntity();
        foreach ($data as $name => $value) {
            $azureEntity->$name = $value;
        }

        return $azureEntity;

    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return AzureEntity::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AzureEntity::class => true,
        ];
    }
}
