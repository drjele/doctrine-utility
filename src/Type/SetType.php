<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drjele\DoctrineUtility\Exception\Exception;

class SetType extends AbstractType
{
    public function getName(): string
    {
        return 'set';
    }

    public function convertToDatabaseValue($values, AbstractPlatform $platform): ?string
    {
        if (\is_array($values)) {
            return empty($values) ? '0' : \implode(',', (array)$values);
        }

        return (null === $values) ? null : $values;
    }

    public function convertToPHPValue($values, AbstractPlatform $platform): ?array
    {
        return (null === $values) ? null : \array_filter(
            \explode(',', $values),
            function ($value) {
                return 0 != \strlen($value);
            }
        );
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $values = [];

        foreach ($this->getValues($fieldDeclaration) as $value) {
            $values[] = $platform->quoteStringLiteral($value);
        }

        if ($platform instanceof MySqlPlatform) {
            return 'SET(' . \implode(',', $values) . ')';
        }

        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    private function getValues(array $field): array
    {
        if (!empty($field['values']) && \is_array($field['values'])) {
            return \array_values($field['values']);
        }

        throw new Exception(
            \sprintf('Field "%s" declaration is missing "values"', $field['name'])
        );
    }
}
