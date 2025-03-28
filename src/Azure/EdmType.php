<?php

declare(strict_types=1);

namespace Lsyh\TableServiceBundle\Azure;

enum EdmType: string
{
    case DATETIME = 'Edm.DateTime';
    case BINARY = 'Edm.Binary';
    case BOOLEAN = 'Edm.Boolean';
    case DOUBLE = 'Edm.Double';
    case GUID = 'Edm.Guid';
    case INT32 = 'Edm.Int32';
    case INT64 = 'Edm.Int64';
    case STRING = 'Edm.String';
}
