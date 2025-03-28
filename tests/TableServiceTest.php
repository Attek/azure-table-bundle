<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\Azure\Response\ODataError;
use App\Service\Azure\Response\ODataErrorMessage;
use App\Service\Azure\Response\ODataErrorResponse;
use App\Service\Azure\Response\TableItem;
use App\Service\Azure\Response\TablesResponse;
use App\Service\Azure\TableService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->parameterBag->method('get')
          ->willReturnMap([
            ['azure_url', 'http://azurite:10002'],
            ['azure_sas_token', 'Loremipsumdolorsitamet'],
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
        $responseBody = '{\"value\":[{\"TableName\":\"existingTable\"}]}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $tablesResponse = (new TablesResponse())->setTables([(new TableItem())->setTableName('existingTable')]);

        $this->serializer->method('deserialize')->willReturn($tablesResponse);

        $result = $this->tableService->getTable($tableName);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(200, $result->getResponseCode());
        $this->assertEquals($responseBody, $result->getBody());
    }

    public function testGetTableReturnsErrorWhenTableDoesNotExist(): void
    {
        $tableName = 'nonExistingTable';
        $responseBody = '{\"value\":[]}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));
        $this->serializer->method('deserialize')->willReturn(new TablesResponse([]));

        $result = $this->tableService->getTable($tableName);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('nonExistingTable does not exists', $result->getErrorMessage());
    }

    public function testCreateTableReturnsSuccessWhenTableIsCreated(): void
    {
        $tableName = 'newTable';
        $responseBody = '{\"TableName\":\"newTable\"}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(201, $responseBody));
        $this->serializer->method('deserialize')->willReturn((new TableItem())->setTableName('newTable'));

        $result = $this->tableService->createTable($tableName);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(201, $result->getResponseCode());
        $this->assertEquals($responseBody, $result->getBody());
    }

    public function testCreateTableReturnsErrorWhenTableExists(): void
    {
        $tableName = 'existingTable';
        $responseBody = '{\"TableName\":\"existingTable\"}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(409, $responseBody));

        $message = (new ODataErrorMessage())->setValue('The table specified already exists.');
        $odataError = (new ODataError())->setCode('TableAlreadyExists')->setMessage($message);
        $errorObj = (new ODataErrorResponse())->setODataError($odataError);

        $this->serializer->method('deserialize')->willReturn($errorObj);

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
        $responseBody = '{\"odata.error\":{\"code\":\"ResourceNotFound\",\"message\":{\"value\":\"The specified resource does not exist.\"}}}';

        $this->httpClient->method('request')->willReturn($this->createMockResponse(404, $responseBody));

        $message = (new ODataErrorMessage())->setValue('The specified resource does not exist.');
        $odataError = (new ODataError())->setCode('ResourceNotFound')->setMessage($message);
        $errorObj = (new ODataErrorResponse())->setODataError($odataError);

        $this->serializer->method('deserialize')->willReturn($errorObj);

        $result = $this->tableService->deleteTable($tableName);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('ResourceNotFound', $result->getErrorCode());
        $this->assertEquals('The specified resource does not exist.', $result->getErrorMessage());
    }

    public function testGetEntityReturnsSuccessWhenEntityExists(): void
    {
        $responseBody = "{\"odata.metadata\":\"http://azurite:10002/devstoreaccount1/\$metadata#mytable/@Element\",\"odata.type\":\"devstoreaccount1.mytable\",\"odata.id\":\"http://azurite:10002/devstoreaccount1/mytable(PartitionKey='partkey5',RowKey='rowkey5')\",\"odata.etag\":\"W/\\\"datetime'2025-03-14T15%3A36%3A40.6174387Z'\",\"odata.editLink\":\"mytable(PartitionKey='partkey5',RowKey='rowkey5')\",\"PartitionKey\":\"partkey5\",\"RowKey\":\"rowkey5\",\"name\":\"John Doe\",\"firstname\":\"John\",\"age\":30,\"isStudent\":true,\"percent\":0.1234567,\"NumberOfOrders@odata.type\":\"Edm.Int64\",\"NumberOfOrders\":\"2147483648\",\"created@odata.type\":\"Edm.DateTime\",\"created\":\"2025-03-14T15:36:40.6169530Z\",\"Timestamp@odata.type\":\"Edm.DateTime\",\"Timestamp\":\"2025-03-14T15:36:40.6174387Z\"}";

        $this->httpClient->method('request')->willReturn($this->createMockResponse(200, $responseBody));

        $result = $this->tableService->getEntity('mytable', 'partkey5', 'rowkey5');
        ;
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

        //getEntity
    //insertEntity
    //updateEntity
    //deleteEntity

    private function createMockResponse(int $statusCode, string $content): MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getContent')->willReturn($content);

        return $response;
    }
}
