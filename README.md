# Azure Table Service for Symfony

## Installation:

### Step 1: Download the Bundle
Open a command console, enter your project directory and execute:
```console
composer require lsyh/table-service-bundle:@dev
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Lsyh\TableServiceBundle\TableServiceBundle::class => ['all' => true],
];
```

## Usage:
### Dependency Injection:

```php
class AzureTestCommand extends Command
{
    public function __construct(
        private TableService $tableService,
    ) {
        parent::__construct();
    }
```

### Create table:
```php
   $azureApiResponse = $this->tableService->createTable('myTable');
```

### Get table:
```php
   $azureApiResponse = $this->tableService->getTable('myTable');
```

### Delete table:
```php
   $azureApiResponse = $this->tableService->deleteTable('myTable');
```

### Insert Entity:
```php
    $entity = (new Entity())
                    ->setPartitionKey('partkey1')
              ->setRowKey('rowkey1')
              ->addProperty('name', 'John Doe')
              ->addProperty('age', 30)
              ->addProperty('isStudent', true)
              ->addProperty('created', new \DateTime());

   $azureApiResponse = $this->tableService->insertEntity('myTable', $entity);
```

### Update Entity:
```php
    $entity = (new Entity())
                    ->setPartitionKey('partkey1')
              ->setRowKey('rowkey1')
              ->addProperty('name', 'John Doe')
              ->addProperty('age', 30)
              ->addProperty('isStudent', true)
              ->addProperty('created', new \DateTime())
              ->addProperty('binaryTest', 'SomeBinaryData', EdmType::BINARY);

   $azureApiResponse = $this->tableService->updateEntity('myTable', $entity);
```

### Delete Entity:
```php
   $azureApiResponse = $this->tableService->delelteEntity('myTable', 'partkey1', 'rowkey1');
```

### Get Entity:
```php
   $azureApiResponse = $this->tableService->getEntity('myTable', 'partkey1', 'rowkey1');

   $azureApiResponse->getEntity();
```

### Get Entity, filter response:
```php
   $azureApiResponse = $this->tableService->getEntity('myTable', 'partkey1', 'rowkey1', 'name', 'age');
```