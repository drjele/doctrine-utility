<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ReflectionClass;

abstract class AbstractType extends Type
{
    final public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    final public function getName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
