<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

use Lsyh\TableServiceBundle\Azure\AzureEntity;
use Lsyh\TableServiceBundle\Azure\Serializer\AzureEntityDenormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AzureApiResponse
{
    private string $errorCode = '';
    private string $errorMessage = '';
    private bool $success = false;
    private int $responseCode;
    private string $body = '';

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function setErrorCode(string $errorCode): AzureApiResponse
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(string $errorMessage): AzureApiResponse
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): AzureApiResponse
    {
        $this->success = $success;

        return $this;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): AzureApiResponse
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): AzureApiResponse
    {
        $this->body = $body;

        return $this;
    }

    public function getEntity(): AzureEntity|array|null
    {
        if (empty($this->body)) {
            return null;
        }

        $serializer = new Serializer([new AzureEntityDenormalizer(), new ObjectNormalizer()], [new JsonEncoder()]);

        return $serializer->deserialize($this->body, AzureEntity::class, JsonEncoder::FORMAT);
    }
}
