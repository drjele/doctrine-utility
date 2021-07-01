<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;

class DateTimeType extends \Doctrine\DBAL\Types\DateTimeType
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): ?string
    {
        $sqlDeclaration = parent::getSQLDeclaration($fieldDeclaration, $platform);

        if (($platform instanceof MySqlPlatform) && false === empty($fieldDeclaration['update'])) {
            return $sqlDeclaration . ' ON UPDATE CURRENT_TIMESTAMP';
        }

        return $sqlDeclaration;
    }
}
