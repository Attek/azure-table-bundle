<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

class ODataErrorMessage
{
    private string $lang;
    private string $value;

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): ODataErrorMessage
    {
        $this->lang = $lang;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): ODataErrorMessage
    {
        $this->value = $value;
        return $this;
    }
}
