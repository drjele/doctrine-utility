<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Service;

use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Drjele\Doctrine\Utility\Exception\Exception;
use Drjele\Doctrine\Utility\Exception\MysqlLockException;
use PDO;
use Throwable;

class MysqlLockService
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function isLocked(string $lockName, string $entityManagerName = null): bool
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager($entityManagerName);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $sql = \sprintf('SELECT IS_FREE_LOCK(\'%s\') AS lock_is_free', $this->getLockName($lockName, $entityManagerName));

        $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);

        return 1 !== $row['lock_is_free'];
    }

    public function acquire(string $lockName, int $timeout = 1, string $entityManagerName = null): self
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager($entityManagerName);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $sql = \sprintf(
            'SELECT GET_LOCK(\'%s\', %s) AS lock_acquired',
            $this->getLockName($lockName, $entityManagerName),
            $timeout
        );

        try {
            $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
            throw new MysqlLockException(
                \sprintf('failed acquiring lock `%s`: `%s`', $lockName, $t->getMessage()),
                $t->getCode(),
                $t
            );
        }

        switch ($row['lock_acquired']) {
            case 1:
                /* all ok */
                break;
            case 0:
                throw new MysqlLockException(
                    \sprintf('failed acquiring lock `%s`: another operation with the same id is already in progress', $lockName)
                );
            default:
                throw new MysqlLockException(
                    \sprintf('failed acquiring lock `%s`: an error occurred (such as running out of memory or the thread was killed with mysqladmin kill)', $lockName)
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
        } catch (Exception $t) {
            $this->releaseLocks($lockNames, $entityManagerName);

            throw $t;
        }

        return $this;
    }

    public function release(string $lockName, string $entityManagerName = null): self
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager($entityManagerName);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $sql = \sprintf('SELECT RELEASE_LOCK(\'%s\') AS lock_released', $this->getLockName($lockName));

        $row = $connection->query($sql)->fetch(PDO::FETCH_ASSOC);

        switch ($row['lock_released']) {
            case 1:
                /* all ok */
                break;
            case 0:
                throw new MysqlLockException(
                    \sprintf('failed releasing lock `%s`: lock was not established by this thread (in which case the lock is not released)', $lockName)
                );
            default:
                throw new MysqlLockException(
                    \sprintf('failed releasing lock `%s`: the named lock did not exist', $lockName)
                );
        }

        return $this;
    }

    public function releaseLocks(array $lockNames, string $entityManagerName = null, bool $throwException = false): self
    {
        $exception = null;

        foreach ($lockNames as $lockName) {
            try {
                $this->release($lockName, $entityManagerName);
            } catch (Throwable $t) {
                $exception = $t;
            }
        }

        if (true === $throwException && $exception) {
            throw $exception;
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
