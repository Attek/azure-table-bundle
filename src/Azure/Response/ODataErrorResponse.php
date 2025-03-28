<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ODataErrorResponse
{
    #[SerializedName('odata.error')]
    private ODataError $odataError;

    public function getODataError(): ODataError
    {
        return $this->odataError;
    }

    public function setODataError(ODataError $odataError): ODataErrorResponse
    {
        $this->odataError = $odataError;
        return $this;
    }
}
