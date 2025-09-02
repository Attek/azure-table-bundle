<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Tests;

use Lsyh\TableServiceBundle\Azure\Entity;
use Lsyh\TableServiceBundle\Azure\TableService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TableServiceTest extends KernelTestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ParameterBagInterface $parameterBag;
    private SerializerInterface $serializer;
    private TableService $tableService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->serializer = static::getContainer()->get(SerializerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->parameterBag->method('get')
          ->willReturnMap([
            ['azure_table_service.azure_url', 'http://azurite:10002'],
            ['azure_table_service.azure_sas_token', 'Loremipsumdolorsitamet'],
          ]);

        $this->tableService = new TableService(
          $this->parameterBag,
          $this->httpClient,
          $this->serializer,
          $this->logger
        );
    }

    public function testGetTableReturnsSuccessWhenTableExists(): void
    {
        $tableName = 'existingTable';
        $responseBody = '{"value":[{"TableName":"existingTable"}]}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $result = $this->tableService->getTable($tableName);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(200, $result->getResponseCode());
        $this->assertEquals($responseBody, $result->getBody());
    }

    public function testGetTableReturnsErrorWhenTableDoesNotExist(): void
    {
        $tableName = 'nonExistingTable';
        $responseBody = '{"value":[]}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $result = $this->tableService->getTable($tableName);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('nonExistingTable does not exists', $result->getErrorMessage());
    }

    public function testCreateTableReturnsSuccessWhenTableIsCreated(): void
    {
        $tableName = 'newTable';
        $responseBody = '{"TableName":"newTable"}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(201, $responseBody));

        $result = $this->tableService->createTable($tableName);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(201, $result->getResponseCode());
        $this->assertEquals($responseBody, $result->getBody());
    }

    public function testCreateTableReturnsErrorWhenTableExists(): void
    {
        $tableName = 'existingTable';
        $responseBody = '{"odata.error":{"code":"TableAlreadyExists","message":{"lang":"en-US","value":"The table specified already exists.\nRequestId:3b4a2b99-9d4d-47fe-a8d9-5a3d282e59d6\nTime:2025-08-29T16:37:45.619Z"}}}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(409, $responseBody));

        $result = $this->tableService->createTable($tableName);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(409, $result->getResponseCode());
        $this->assertEquals('TableAlreadyExists', $result->getErrorCode());
    }

    public function testDeleteTableReturnsSuccessWhenTableIsDeleted(): void
    {
        $tableName = 'existingTable';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(204, ''));

        $result = $this->tableService->deleteTable($tableName);

        $this->assertTrue($result->isSuccess());
    }

    public function testDeleteTableReturnsErrorWhenTableIsNotExists(): void
    {
        $tableName = 'nonExistingTable';
        $responseBody = '{"odata.error":{"code":"ResourceNotFound","message":{"value":"The specified resource does not exist."}}}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(404, $responseBody));

        $result = $this->tableService->deleteTable($tableName);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('ResourceNotFound', $result->getErrorCode());
        $this->assertEquals('The specified resource does not exist.', $result->getErrorMessage());
    }

    public function testGetEntityReturnsSuccessWhenEntityExists(): void
    {
        $responseBody = '{"odata.metadata":"http://azurite:10002/devstoreaccount1/$metadata#mytable/@Element","odata.type":"devstoreaccount1.mytable","odata.id":"http://azurite:10002/devstoreaccount1/mytable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","odata.etag":"W/\"datetime\'2025-03-14T15%3A36%3A40.6174387Z\'\"","odata.editLink":"mytable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","PartitionKey":"partkey5","RowKey":"rowkey5","name":"John Doe","firstname":"John","age":30,"isStudent":true,"percent":0.1234567,"NumberOfOrders@odata.type":"Edm.Int64","NumberOfOrders":"2147483648","created@odata.type":"Edm.DateTime","created":"2025-03-14T15:36:40.6169530Z","Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-03-14T15:36:40.6174387Z"}';
        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $result = $this->tableService->getEntity('mytable', 'partkey5', 'rowkey5');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('partkey5', $result->getEntity()->getPartitionKey());
        $this->assertEquals('rowkey5', $result->getEntity()->getRowKey());
        $this->assertEquals('2147483648', $result->getEntity()->getNumberOfOrders());
        $this->assertEquals('John Doe', $result->getEntity()->getName());
        $this->assertEquals('John Doe', $result->getEntity()->name);
        $this->assertEquals('John Doe', $result->getEntity()->Name);
        $this->assertEquals('John', $result->getEntity()->getFirstname());
        $this->assertEquals('John', $result->getEntity()->getFirstName());
        $this->assertEquals('John', $result->getEntity()->FirstName);
        $this->assertEquals('John', $result->getEntity()->firstname);
        $this->assertEquals('John', $result->getEntity()->Firstname);
        $this->assertNull($result->getEntity()->propertyDoesNotExists);
        $this->assertEquals(30, $result->getEntity()->getAge());
        $this->assertTrue($result->getEntity()->isStudent());
        $this->assertEquals(0.1234567, $result->getEntity()->getPercent());
        $this->assertEquals('2025-03-14 15:36:40', $result->getEntity()->getCreated()->format('Y-m-d H:i:s'));
    }

    public function testGetEntityReturnsFalseWhenEntityDoesNotExists(): void
    {
        $responseBody = '{"odata.error":{"code":"ResourceNotFound","message":{"lang":"en-US","value":"The specified resource does not exist.\nRequestId:567b3d29-e3f1-4773-842e-e4977868118e\nTime:2025-08-29T15:27:22.067Z"}}}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(404, $responseBody));

        $result = $this->tableService->getEntity('mytable', 'partkey5', 'rowkey5');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('ResourceNotFound', $result->getErrorCode());
        $this->assertStringContainsStringIgnoringLineEndings('The specified resource does not exist.', $result->getErrorMessage());
    }

    public function testInsertEntity()
    {
        $entity = new Entity()
          ->setPartitionKey('partkey6')
          ->setRowKey('rowkey6');

        $responseBody = '{"odata.metadata":"http://azurite:10002/devstoreaccount1/$metadata#testTable/@Element","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'partkey6\',RowKey=\'rowkey6\')","odata.etag":"W/"datetime\'2025-08-29T16%3A11%3A04.0839690Z\'"","odata.editLink":"testTable(PartitionKey=\'partkey6\',RowKey=\'rowkey6\')","PartitionKey":"partkey6","RowKey":"rowkey6","Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-08-29T16:11:04.0839690Z"}';
        $this->httpClient->method('request')->willReturn($this->createMockResponse(201, $responseBody));

        $result = $this->tableService->insertEntity('mytable', $entity);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(201, $result->getResponseCode());
    }

    public function testUpdateEntityIfExists()
    {
        $entity = new Entity()
          ->setRowKey('rowkey6')
          ->setPartitionKey('partkey6')
          ->addProperty('name', 'test');

        $this->httpClient->method('request')->willReturn($this->createMockResponse(204, ''));

        $result = $this->tableService->updateEntity('mytable', $entity);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(204, $result->getResponseCode());
        $this->assertEmpty($result->getBody());
    }

    public function testUpdateEntityIfDoesNotExists()
    {
        // It creates a new row in table if entity does not exists.
        $entity = new Entity()
          ->setRowKey('rowkey7')
          ->setPartitionKey('partkey7')
          ->addProperty('name', 'test');

        $this->httpClient->method('request')->willReturn($this->createMockResponse(204, ''));

        $result = $this->tableService->updateEntity('mytable', $entity);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(204, $result->getResponseCode());
        $this->assertEmpty($result->getBody());
    }

    public function testDeleteEntityIfExists()
    {
        $this->httpClient->method('request')->willReturn($this->createMockResponse(204, ''));
        $result = $this->tableService->deleteEntity('mytable', 'partkey6', 'rowkey6');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(204, $result->getResponseCode());
        $this->assertEmpty($result->getBody());
    }

    public function testDeleteEntityIfDoesNotExists()
    {
        $responseBody = '{"odata.error":{"code":"ResourceNotFound","message":{"lang":"en-US","value":"The specified resource does not exist.\nRequestId:1cc53f70-b274-4045-8c72-5826b71c4e3a\nTime:2025-08-30T09:50:14.586Z"}}}';
        $this->httpClient->method('request')->willReturn($this->createMockResponse(304, $responseBody));

        $result = $this->tableService->deleteEntity('mytable', 'partkey5', 'rowkey5');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(304, $result->getResponseCode());
        $this->assertEquals('ResourceNotFound', $result->getErrorCode());
        $this->assertStringContainsStringIgnoringLineEndings('The specified resource does not exist.', $result->getErrorMessage());
    }

    public function testInvalidTableName()
    {
        $result = $this->tableService->createTable('123');
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid table name', $result->getErrorMessage());
    }

    public function testHttpNetworkError()
    {
        $this->httpClient->method('request')->willThrowException(new TransportException('Network error', 500));
        $result = $this->tableService->getTable('testTable');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Network error', $result->getErrorMessage());
        $this->assertEquals(500, $result->getErrorCode());
    }

    public function testJSONParseErrors()
    {
        $this->httpClient->method('request')->willThrowException(new NotEncodableValueException('Syntax error', 500));
        $result = $this->tableService->getTable('testTable');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Syntax error', $result->getErrorMessage());
        $this->assertEquals(500, $result->getErrorCode());
    }

    public function testEntityPropertySelect()
    {
        $responseBody = '{"odata.metadata":"http://azurite:10002/devstoreaccount1/$metadata#myTable/@Element","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","odata.etag":"W/\"datetime\'2025-08-31T18%3A31%3A38.4482428Z\'\"","odata.editLink":"testTable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","name":"John Doe","age":30}';
        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));
        $result = $this->tableService->getEntity('myTable', 'partkey5', 'rowkey5', 'name', 'age');

        $entity = $result->getEntity();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(200, $result->getResponseCode());
        $this->assertEquals('John Doe', $entity->getName());
        $this->assertFalse(method_exists($entity, 'getFirstname'));
    }

    public function testEntityFilter()
    {
        $responseBody = '{"odata.metadata":"http://azurite:10002/devstoreaccount1/$metadata#Tables/@Element","value":[{"odata.etag":"W/\"datetime\'2025-08-30T10%3A00%3A15.3833556Z\'\"","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'1\',RowKey=\'2\')","odata.editLink":"testTable(PartitionKey=\'1\',RowKey=\'2\')","PartitionKey":"1","RowKey":"2","name":"test","Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-08-30T10:00:15.3833556Z"},{"odata.etag":"W/\"datetime\'2025-08-30T10%3A02%3A50.3303733Z\'\"","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'1\',RowKey=\'21\')","odata.editLink":"testTable(PartitionKey=\'1\',RowKey=\'21\')","PartitionKey":"1","RowKey":"21","name":"test","Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-08-30T10:02:50.3303733Z"},{"odata.etag":"W/\"datetime\'2025-08-31T18%3A31%3A38.4482428Z\'\"","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","odata.editLink":"testTable(PartitionKey=\'partkey5\',RowKey=\'rowkey5\')","PartitionKey":"partkey5","RowKey":"rowkey5","name":"John Doe","firstname":"John","lastname":"Doe","age":30,"Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-08-31T18:31:38.4482428Z"},{"odata.etag":"W/\"datetime\'2025-08-29T16%3A11%3A04.0839690Z\'\"","odata.type":"devstoreaccount1.testTable","odata.id":"http://azurite:10002/devstoreaccount1/testTable(PartitionKey=\'partkey6\',RowKey=\'rowkey6\')","odata.editLink":"testTable(PartitionKey=\'partkey6\',RowKey=\'rowkey6\')","PartitionKey":"partkey6","RowKey":"rowkey6","Timestamp@odata.type":"Edm.DateTime","Timestamp":"2025-08-29T16:11:04.0839690Z"}]}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $result = $this->tableService->getEntityByFilter('myTable', 'and', 'Timestamp le datetime\'' . date('Y-m-d H:i:s') . '\'');

        $entities = $result->getEntity();
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(200, $result->getResponseCode());
        $this->assertEquals('John', $entities[2]->getFirstName());
        $this->assertEquals('Doe', $entities[2]->getLastName());
    }

    private function createMockResponse(int $statusCode, string $content): MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getContent')->willReturn($content);

        return $response;
    }
}
