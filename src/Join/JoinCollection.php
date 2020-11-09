<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Join;

use Doctrine\ORM\Query\Expr\Join;
use Drjele\DoctrineUtility\Exception\Exception;

class JoinCollection
{
    private array $joins = [];

    /** @return Join[] */
    public function getJoins(): ?array
    {
        return $this->joins;
    }

    public function addJoin(Join $join): self
    {
        $alias = $join->getAlias();

        if (isset($this->joins[$alias])) {
            throw new Exception(\sprintf('Duplicate alias "%s" in join collection!', $alias));
        }

        $this->joins[$alias] = $join;

        return $this;
    }

    public function getAliases(): array
    {
        return \array_keys($this->joins);
    }
}
