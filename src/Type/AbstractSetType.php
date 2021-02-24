<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drjele\DoctrineUtility\Exception\InvalidTypeValueException;

abstract class AbstractSetType extends AbstractType
{
    abstract public function getValues(): array;

    public function convertToDatabaseValue($values, AbstractPlatform $platform): ?string
    {
        if (null !== $values) {
            $values = (array)$values;

            if ($diff = \array_diff($values, $this->getValues())) {
                throw new InvalidTypeValueException(
                    \sprintf(
                        'Invalid value "%s", expected one of "%s", for "%s"!',
                        \implode(', ', $diff),
                        \implode(', ', $this->getValues()),
                        $this->getName()
                    )
                );
            }

            $values = empty($values) ? '0' : \implode(',', $values);
        }

        return (null === $values) ? null : $values;
    }

    public function convertToPHPValue($values, AbstractPlatform $platform): ?array
    {
        return (null === $values) ? null : \explode(',', $values);
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $values = [];

        foreach ($this->getValues() as $value) {
            $values[] = $platform->quoteStringLiteral($value);
        }

        if ($platform instanceof MySqlPlatform) {
            return 'SET(' . \implode(',', $values) . ')';
        }

        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }
}
