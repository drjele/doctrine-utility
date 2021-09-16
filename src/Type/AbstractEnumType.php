<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drjele\Doctrine\Utility\Exception\InvalidTypeValueException;

abstract class AbstractEnumType extends AbstractType
{
    abstract public function getValues(): array;

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null !== $value && !\in_array($value, $this->getValues(), true)) {
            throw new InvalidTypeValueException(
                \sprintf(
                    'invalid value `%s`, expected one of `%s`, for `%s`',
                    $value,
                    \implode(', ', $this->getValues()),
                    $this->getName()
                )
            );
        }

        return (null === $value) ? null : (string)$value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        return (null === $value) ? null : (string)$value;
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $values = [];

        foreach ($this->getValues() as $value) {
            $values[] = $platform->quoteStringLiteral($value);
        }

        if ($platform instanceof MySqlPlatform) {
            return 'ENUM(' . \implode(',', $values) . ')';
        }

        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }
}
