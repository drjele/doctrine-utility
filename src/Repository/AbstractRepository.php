<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Drjele\Doctrine\Utility\Exception\Exception;
use Drjele\Doctrine\Utility\Join\JoinCollection;
use ReflectionClass;

abstract class AbstractRepository
{
    public const JOIN_LEFT = Join::LEFT_JOIN;
    public const JOIN_INNER = Join::INNER_JOIN;

    private ManagerRegistry $managerRegistry;

    abstract public static function getEntityClass(): string;

    public static function getAlias(): string
    {
        return \lcfirst((new ReflectionClass(static::class))->getShortName());
    }

    final public function setManagerRegistry(ManagerRegistry $managerRegistry): self
    {
        $this->managerRegistry = $managerRegistry;

        return $this;
    }

    final protected function execute(string $query, array $parameters = [], string $connectionName = null): Result
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection($connectionName);

        $stmt = $connection->prepare($query);

        return $stmt->executeQuery($parameters);
    }

    final protected function getManagerRegistry(): ?ManagerRegistry
    {
        return $this->managerRegistry;
    }

    final protected function createQueryBuilder(string $managerName = null): QueryBuilder
    {
        return $this->getDoctrineRepository($managerName)->createQueryBuilder(static::getAlias());
    }

    final protected function createQueryBuilderFromFilters(
        array $filters,
        bool $selectJoins = false,
        string $managerName = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder($managerName);

        $joinCollection = $this->attachFilters($qb, $filters, $managerName);

        if (true === $selectJoins && null !== $joinCollection) {
            $qb->addSelect($joinCollection->getAliases());
        }

        return $qb;
    }

    final protected function attachFilters(
        QueryBuilder $qb,
        array $filters,
        string $managerName = null
    ): ?JoinCollection {
        [$genericFilters, $customFilters] = $this->sortFilters($filters, $managerName);

        if ($genericFilters) {
            $this->attachGenericFilters($qb, $genericFilters);
        }

        $joinCollection = null;
        if ($customFilters) {
            $joinCollection = $this->attachCustomFilters($qb, $customFilters);
        }

        if (isset($joinCollection) && $joinCollection->getJoins()) {
            $this->attachJoins($qb, $joinCollection);
        }

        return $joinCollection;
    }

    final protected function sortFilters(array $filters, string $managerName = null): array
    {
        $genericFilters = $customFilters = [];

        foreach ($filters as $filter => $value) {
            if ($this->getDoctrineRepository($managerName)->hasField($filter)) {
                $genericFilters[$filter] = $value;
                continue;
            }

            $customFilters[$filter] = $value;
        }

        return [$genericFilters, $customFilters];
    }

    final protected function attachGenericFilters(QueryBuilder $qb, array $filters): void
    {
        foreach ($filters as $key => $value) {
            $condition = \is_array($value) ? 'IN (:' . $key . ')' : '=:' . $key;

            $qb->andWhere(static::getAlias() . '.' . $key . ' ' . $condition)
                ->setParameter($key, $value);
        }
    }

    final protected function attachJoins(
        QueryBuilder $qb,
        JoinCollection $joinCollection
    ): void {
        foreach ($joinCollection->getJoins() as $join) {
            switch ($join->getJoinType()) {
                case static::JOIN_INNER:
                    $qb->innerJoin(
                        $join->getJoin(),
                        $join->getAlias(),
                        $join->getConditionType(),
                        $join->getCondition(),
                        $join->getIndexBy()
                    );
                    break;
                case static::JOIN_LEFT:
                    $qb->leftJoin(
                        $join->getJoin(),
                        $join->getAlias(),
                        $join->getConditionType(),
                        $join->getCondition(),
                        $join->getIndexBy()
                    );
                    break;
                default:
                    throw new Exception(\sprintf('invalid join type `%s`', $join->getJoinType()));
            }
        }
    }

    final protected function getDoctrineRepository(string $managerName = null): DoctrineRepository
    {
        $managerName ??= $this->getManagerName();

        return $this->managerRegistry->getRepository(static::getEntityClass(), $managerName);
    }

    protected function getManagerName(): ?string
    {
        /* overwrite if the entity has a different manager than the default */
        return null;
    }

    protected function attachCustomFilters(QueryBuilder $qb, array $filters): JoinCollection
    {
        throw new Exception(
            \sprintf('overwrite `%s` in `%s`', __METHOD__, static::class)
        );
    }
}
