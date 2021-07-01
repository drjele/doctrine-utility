<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Repository;

use Doctrine\ORM\EntityRepository;

class DoctrineRepository extends EntityRepository
{
    final public function hasField(string $fieldName): bool
    {
        return $this->getClassMetadata()->hasField($fieldName)
            || ($this->getClassMetadata()->hasAssociation($fieldName) && !$this->getClassMetadata()->isAssociationInverseSide($fieldName));
    }
}
