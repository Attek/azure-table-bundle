<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

class ODataError
{
    private string $code;
    private ODataErrorMessage $message;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): ODataError
    {
        $this->code = $code;
        return $this;
    }

    public function getMessage(): ODataErrorMessage
    {
        return $this->message;
    }

    public function setMessage(ODataErrorMessage $message): ODataError
    {
        $this->message = $message;
        return $this;
    }
}
