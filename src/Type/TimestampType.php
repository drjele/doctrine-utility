<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class TimestampType extends DateTimeType
{
    public function getName(): string
    {
        return 'timestamp';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): ?string
    {
        if ($platform instanceof MySqlPlatform) {
            $base = 'TIMESTAMP';
            $onUpdate = empty($fieldDeclaration['update']) ? null : ' ON UPDATE CURRENT_TIMESTAMP';

            return $base . $onUpdate;
        }

        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }
}
