<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drjele\DoctrineUtility\Exception\Exception;

class EnumType extends AbstractType
{
    public function getName(): string
    {
        return 'enum';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return (null === $value) ? null : (string)$value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        return (null === $value) ? null : (string)$value;
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $values = [];

        foreach ($this->getValues($fieldDeclaration) as $value) {
            $values[] = $platform->quoteStringLiteral($value);
        }

        if ($platform instanceof MySqlPlatform) {
            return 'ENUM(' . \implode(',', $values) . ')';
        }

        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    private function getValues(array $field): array
    {
        if (!empty($field['values']) && \is_array($field['values'])) {
            return \array_values($field['values']);
        }

        throw new Exception(\sprintf('Field "%s" declaration is missing "values"', $field['name']));
    }
}
