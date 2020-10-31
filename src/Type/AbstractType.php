<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Drjele\DoctrineUtility\Exception\Exception;

abstract class AbstractType extends Type
{
    protected const NAME = null;

    public function getName()
    {
        if (null === static::NAME) {
            throw new Exception('Invalid name for type "' . static::class . '"');
        }

        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
