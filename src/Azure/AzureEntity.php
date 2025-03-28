<?php declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure;

use BadMethodCallException;

class AzureEntity
{
    private array $properties = [];

    public function __set(string $name, mixed $value): void
    {
        $name = $this->sanitizeName($name);
        $this->properties[$name] = $value;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $name = $this->sanitizeName($name);

        return $this->properties[$name] ?? throw new BadMethodCallException("Method $name does not exist.");
    }
    public function __get(string $name): mixed
    {
        $name = $this->sanitizeName($name);

        return $this->properties[$name] ?? null;
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace('/^get|\(\)\z/', '', strtolower($name));
    }
}