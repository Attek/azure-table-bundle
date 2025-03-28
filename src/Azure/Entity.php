<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure;

class Entity
{
    private array $properties = [];

    public function setPartitionKey(string $value): Entity
    {
        if (!$this->isSystemKeyValueValid($value)) {
            throw new \InvalidArgumentException('PartitionKey value is invalid');
        }

        $this->properties['PartitionKey'] = $value;

        return $this;
    }

    public function getPartitionKey(): string
    {
        return $this->properties['PartitionKey'] ?? '';
    }

    public function setRowKey(string $value): Entity
    {
        if (!$this->isSystemKeyValueValid($value)) {
            throw new \InvalidArgumentException('RowKey value is invalid');
        }

        $this->properties['RowKey'] = $value;

        return $this;
    }

    public function getRowKey(): string
    {
        return $this->properties['RowKey'] ?? '';
    }

    public function addProperty(string $name, mixed $value, ?EdmType $edmType = null): Entity
    {
        if (!$this->isPropertyNameValid($name)) {
            throw new \InvalidArgumentException('Property name is invalid');
        }

        $type = $edmType ?? $this->getPropertyType($value);

        if (EdmType::STRING !== $type) {
            $this->properties[$name . '@odata.type'] = $type->value;
        }

        $this->properties[$name] = $value;

        if (EdmType::DATETIME == $type && $value instanceof \DateTimeInterface) {
            $this->properties[$name] = $value->format("Y-m-d\TH:i:s.u0\Z");
        }

        if (EdmType::BINARY == $type) {
            $this->properties[$name] = base64_encode($value);
        }

        if (EdmType::INT64 == $type) {
            $this->properties[$name] = (string) $value;
        }

        return $this;
    }

    public function getProperties(): array
    {
        if (count($this->properties) > 255) {
            throw new \InvalidArgumentException('The entity properties count exceeds the limit');
        }

        return $this->properties;
    }

    private function getPropertyType(mixed $value): EdmType
    {
        $int32Max = min(PHP_INT_MAX, 2147483647);
        if (is_int($value)) {
            if ($value <= $int32Max) {
                return EdmType::INT32;
            } else {
                return EdmType::INT64;
            }
        } elseif (is_float($value)) {
            return EdmType::DOUBLE;
        } elseif (is_bool($value)) {
            return EdmType::BOOLEAN;
        } elseif ($value instanceof \DateTime) {
            return EdmType::DATETIME;
        } else {
            return EdmType::STRING;
        }
    }

    private function isPropertyNameValid(string $name): bool
    {
        $pattern = '/^(?!.*[\x00-\x1F\x7F\x81\x{E000}-\x{F8FF}\/\\\\#\?\[\]@!])\w{1,255}$/u';

        return (bool) preg_match($pattern, $name);
    }

    private function isSystemKeyValueValid(string $value): bool
    {
        $pattern = '/^(?!.*[\/\\\\#\?\x00-\x1F\x7F-\x9F]).{1,1024}$/u';

        return (bool) preg_match($pattern, $value);
    }
}
