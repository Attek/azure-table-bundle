# Azure Table Service for Symfony

## Installation with symfony recipe:
* Add the following lines to composer.json
```consolse
    "extra": {
        "symfony": {
            "endpoint": ["https://api.github.com/repos/Attek/azure-table-bundle-recipe/contents/index.json", "flex://defaults"]
        }
    }
```
 
* Download the Bundle
Open a command console, enter your project directory and execute:
```console
composer require lsyh/azure-table-service-bundle
```

## Installation manually:
* Enable the Bundle

Enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Lsyh\TableServiceBundle\TableServiceBundle::class => ['all' => true],
];
```

* Create table_service.yaml in config/packages folder.
```yaml
table_service:
  azure_url: '%env(AZURE_URL)%'
  azure_table_name: '%env(AZURE_TABLE_NAME)%'
  azure_sas_token: '%env(AZURE_SAS_TOKEN)%'
```

* Download the Bundle
  Open a command console, enter your project directory and execute:
```console
composer require lsyh/azure-table-service-bundle
```

## Usage:
### Dependency Injection:

```php
class AzureTestCommand extends Command
{
    public function __construct(
        private TableServiceInterface $tableService,
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

### Get Entity, select properties response:
```php
   $azureApiResponse = $this->tableService->getEntity('myTable', 'partkey1', 'rowkey1', 'name', 'age');
```

### Filter Entity by timestamp
```php
$azureApiResponse = $this->tableService->getEntityByFilter('myTable', 'and', 'Timestamp le datetime\'' . $date . '\'');
```