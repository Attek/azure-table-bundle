<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure;

use Lsyh\TableServiceBundle\Azure\Response\AzureApiResponse;
use Lsyh\TableServiceBundle\Azure\Response\ODataErrorResponse;
use Lsyh\TableServiceBundle\Azure\Response\TableItem;
use Lsyh\TableServiceBundle\Azure\Response\TablesResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TableService implements TableServiceInterface
{
    private string $azureUrl;
    private string $azureSASToken;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly HttpClientInterface $httpClient,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
    ) {
        $this->azureUrl = $this->parameterBag->get('azure_table_service.azure_url');
        $this->azureSASToken = $this->parameterBag->get('azure_table_service.azure_sas_token');
    }

    private function getOptions(): array
    {
        $date = (new \DateTime())->format('D, d M Y H:i:s T');

        return [
            'headers' => [
                'User-Agent' => 'AzureAgent/1.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json;odata=fullmetadata',
                'x-ms-date' => $date,
            ],
        ];
    }

    public function getTable(string $tableName): AzureApiResponse
    {
        $url = sprintf('%s/Tables?%s', $this->azureUrl, $this->azureSASToken);

        $azureApiResponse = $this->sendRequest(Request::METHOD_GET, $url, $this->getOptions(), $tableName);

        if (!empty($azureApiResponse->getBody())) {
            $tables = $this->serializer->deserialize($azureApiResponse->getBody(), TablesResponse::class, JsonEncoder::FORMAT);

            if ($tables->hasTable($tableName)) {
                return $azureApiResponse->setSuccess(true);
            }

            $this->logger->error('Azure Table Service error: ' . $tableName . ' does not exists');

            return $azureApiResponse->setErrorMessage($tableName . ' does not exists');
        }

        return $azureApiResponse;
    }

    public function createTable(string $tableName): AzureApiResponse
    {
        $url = sprintf('%s/Tables?%s', $this->azureUrl, $this->azureSASToken);

        $options = $this->getOptions();
        $options += ['json' => ['TableName' => $tableName]];

        $azureApiResponse = $this->sendRequest(Request::METHOD_POST, $url, $options, $tableName);

        if (!empty($azureApiResponse->getBody())) {
            $table = $this->serializer->deserialize($azureApiResponse->getBody(), TableItem::class, JsonEncoder::FORMAT);

            if ($table->getTableName() === $tableName) {
                return $azureApiResponse->setSuccess(true);
            }

            $this->logger->error('Azure Table Service error: ' . $tableName . ' already exists');

            return $azureApiResponse->setErrorMessage($tableName . ' already exists');
        }

        return $azureApiResponse;
    }

    public function deleteTable(string $tableName): AzureApiResponse
    {
        $url = sprintf('%s/Tables(\'%s\')?%s', $this->azureUrl, $tableName, $this->azureSASToken);

        $azureApiResponse = $this->sendRequest(Request::METHOD_DELETE, $url, $this->getOptions(), $tableName);

        if (empty($azureApiResponse->getErrorCode())) {
            return $azureApiResponse->setSuccess(true);
        }

        return $azureApiResponse;
    }

    public function getEntity(string $tableName, string $partitionKey, string $rowKey, ...$filter): AzureApiResponse
    {
        $url = sprintf('%s/%s(PartitionKey=\'%s\',RowKey=\'%s\')?%s',
            $this->azureUrl, $tableName, $partitionKey, $rowKey, $this->azureSASToken);

        if ($filter) {
            $url .= '&$select=' . implode(',', $filter);
        }

        $azureApiResponse = $this->sendRequest(Request::METHOD_GET, $url, $this->getOptions(), $tableName);

        if (empty($azureApiResponse->getErrorCode())) {
            return $azureApiResponse->setSuccess(true);
        }

        return $azureApiResponse;
    }

    public function insertEntity(string $tableName, Entity $entity): AzureApiResponse
    {
        $url = sprintf('%s/%s?%s', $this->azureUrl, $tableName, $this->azureSASToken);

        $options = $this->getOptions();
        $options += ['json' => $entity->getProperties()];

        $azureApiResponse = $this->sendRequest(Request::METHOD_POST, $url, $options, $tableName);

        if (empty($azureApiResponse->getErrorCode())) {
            return $azureApiResponse->setSuccess(true);
        }

        return $azureApiResponse;
    }

    public function updateEntity(string $tableName, Entity $entity): AzureApiResponse
    {
        $url = sprintf('%s/%s(PartitionKey=\'%s\',RowKey=\'%s\')?%s',
            $this->azureUrl, $tableName, $entity->getPartitionKey(), $entity->getRowKey(), $this->azureSASToken);

        $options = $this->getOptions();
        $options += ['json' => $entity->getProperties()];

        $azureApiResponse = $this->sendRequest(Request::METHOD_PUT, $url, $options, $tableName);

        if (empty($azureApiResponse->getErrorCode())) {
            return $azureApiResponse->setSuccess(true);
        }

        return $azureApiResponse;
    }

    public function deleteEntity(string $tableName, string $partitionKey, string $rowKey): AzureApiResponse
    {
        $url = sprintf('%s/%s(PartitionKey=\'%s\',RowKey=\'%s\')?%s',
            $this->azureUrl, $tableName, $partitionKey, $rowKey, $this->azureSASToken);

        $options = $this->getOptions();
        $options['headers']['If-Match'] = '*';

        $azureApiResponse = $this->sendRequest(Request::METHOD_DELETE, $url, $options, $tableName);

        if (empty($azureApiResponse->getErrorCode())) {
            return $azureApiResponse->setSuccess(true);
        }

        return $azureApiResponse;
    }

    private function sendRequest(string $method, string $url, array $options, string $tableName): AzureApiResponse
    {
        $azureApiResponse = new AzureApiResponse();

        if (!$this->isTableNameValid($tableName)) {
            return $azureApiResponse->setErrorMessage('Invalid table name');
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);

            $responseBody = $response->getContent(false);

            if (!str_starts_with((string) $response->getStatusCode(), '2')) {
                $error = $this->serializer->deserialize($responseBody, ODataErrorResponse::class, JsonEncoder::FORMAT);
                $this->logger->error('Azure Table Service error: ' . $error->getODataError()->getCode() . ': ' . $error->getODataError()->getMessage()->getValue());

                return $azureApiResponse
                  ->setErrorMessage($error->getODataError()->getMessage()->getValue())
                  ->setErrorCode($error->getODataError()->getCode())
                  ->setResponseCode($response->getStatusCode());
            }

            return $azureApiResponse
              ->setResponseCode($response->getStatusCode())
              ->setBody($responseBody);

        } catch (\Throwable $e) {
            $this->logger->error('Azure Table Service error: ' . $e->getMessage());

            return $azureApiResponse
              ->setErrorMessage($e->getMessage())
              ->setErrorCode((string) $e->getCode());
        }
    }

    private function isTableNameValid(string $tableName): bool
    {
        $pattern = '/^[A-Za-z][A-Za-z0-9]{2,62}$/';

        return (bool) preg_match($pattern, $tableName);
    }
}
