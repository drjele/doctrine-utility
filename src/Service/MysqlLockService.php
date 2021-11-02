<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Service;

use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Drjele\Doctrine\Utility\Exception\MysqlLockException;
use PDO;
use Throwable;

class MysqlLockService
{
    private ManagerRegistry $managerRegistry;
    private array $locks;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->locks = [];
    }

    public function acquire(string $lockName, int $timeout = 0, string $entityManagerName = null): self
    {
        try {
            /** @var EntityManager $em */
            $em = $this->managerRegistry->getManager($entityManagerName);

            /** @var Connection $connection */
            $connection = $em->getConnection();

            $preparedLockName = $this->getLockName($lockName, $entityManagerName);

            $sql = \sprintf('SELECT GET_LOCK(%s, %s) AS lockAcquired', $preparedLockName, $timeout);

            $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);

            switch ($row['lockAcquired']) {
                case 1:
                    /* all ok */
                    $this->locks[$lockName] = $preparedLockName;
                    break;
                case 0:
                    throw new MysqlLockException('another operation with the same id is already in progress');
                default:
                    throw new MysqlLockException('an error occurred (such as running out of memory or the thread was killed)');
            }
        } catch (Throwable $t) {
            throw new MysqlLockException(
                \sprintf('failed acquiring lock `%s`/`%s`: `%s`', $lockName, $preparedLockName ?? '~', $t->getMessage()),
                $t->getCode(),
                $t
            );
        }

        return $this;
    }

    public function release(string $lockName, string $entityManagerName = null): self
    {
        try {
            /** @var EntityManager $em */
            $em = $this->managerRegistry->getManager($entityManagerName);

            /** @var Connection $connection */
            $connection = $em->getConnection();

            $preparedLockName = $this->getLockName($lockName, $entityManagerName);

            $sql = \sprintf('SELECT RELEASE_LOCK(%s) AS lockReleased', $preparedLockName);

            $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);

            switch ($row['lockReleased']) {
                case 1:
                    /* all ok */
                    unset($this->locks[$lockName]);
                    break;
                case 0:
                    throw new MysqlLockException('lock was not established by this thread');
                default:
                    throw new MysqlLockException('the named lock did not exist');
            }
        } catch (Throwable $t) {
            throw new MysqlLockException(
                \sprintf('failed releasing lock `%s`/`%s`: %s', $lockName, $preparedLockName ?? '~', $t->getMessage()),
                $t->getCode(),
                $t
            );
        }

        return $this;
    }

    public function acquireLocks(array $lockNames, int $timeout = 0, string $entityManagerName = null): self
    {
        \asort($lockNames); /* sort the array to try and avoid deadlocks */

        try {
            foreach ($lockNames as $lockName) {
                $this->acquire($lockName, $timeout, $entityManagerName);
            }
        } catch (Throwable $t) {
            $this->releaseLocks($lockNames, $entityManagerName);

            throw new MysqlLockException($t->getMessage(), $t->getCode(), $t);
        }

        return $this;
    }

    public function isLocked(string $lockName, string $entityManagerName = null): bool
    {
        try {
            /** @var EntityManager $em */
            $em = $this->managerRegistry->getManager($entityManagerName);

            /** @var Connection $connection */
            $connection = $em->getConnection();

            $sql = \sprintf('SELECT IS_FREE_LOCK(%s) AS lockIsFree', $this->getLockName($lockName, $entityManagerName));

            $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);

            return 1 !== (int)$row['lockIsFree'];
        } catch (Throwable $t) {
            throw new MysqlLockException($t->getMessage(), $t->getCode(), $t);
        }
    }

    public function releaseLocks(array $lockNames = null, string $entityManagerName = null, bool $throw = false): self
    {
        foreach (($lockNames ?? \array_keys($this->locks)) as $lockName) {
            try {
                $this->release($lockName, $entityManagerName);
            } catch (Throwable $t) {
                if (true === $throw) {
                    throw new MysqlLockException($t->getMessage(), $t->getCode(), $t);
                }
            }
        }

        return $this;
    }

    private function getLockName(string $lockName, string $entityManagerName = null): string
    {
        if (\strlen($lockName) > 64) {
            $lockName = \substr($lockName, 0, 10) . '>>' . \md5($lockName) . '<<' . \substr($lockName, -10);
        }

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager($entityManagerName);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        return $connection->quote($lockName);
    }
}
