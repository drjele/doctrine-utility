<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Walker;

use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\SqlWalker;
use Drjele\DoctrineUtility\Exception\Exception;

/**
 * $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, MySqlWalker::class);
 * $qb->setHint(MySQLWalker::HINT_IGNORE_INDEX, 'PRIMARY, other_index');.
 */
class MySqlWalker extends SqlWalker
{
    public const HINT_USE_INDEX = 'MySqlWalker.UseIndex';
    public const HINT_IGNORE_INDEX = 'MySqlWalker.IgnoreIndex';
    public const HINT_FORCE_INDEX = 'MySqlWalker.ForceIndex';
    public const HINT_SELECT_FOR_UPDATE = 'MySqlWalker.SelectForUpdate';
    public const HINT_IGNORE_INDEX_ON_JOIN = 'MySqlWalker.IgnoreIndexOnJoin';

    public function walkFromClause($fromClause): string
    {
        $regex = '/(\s+FROM\s+[`\w\.]+\s+\w*)/';

        $result = parent::walkFromClause($fromClause);

        if ($index = $this->getQuery()->getHint(self::HINT_USE_INDEX)) {
            $result = \preg_replace($regex, '\1 USE INDEX (' . $index . ')', $result);
        }

        if ($index = $this->getQuery()->getHint(self::HINT_IGNORE_INDEX)) {
            $result = \preg_replace($regex, '\1 IGNORE INDEX (' . $index . ')', $result);
        }

        if ($index = $this->getQuery()->getHint(self::HINT_FORCE_INDEX)) {
            $result = \preg_replace($regex, '\1 FORCE INDEX (' . $index . ')', $result);
        }

        return $result;
    }

    public function walkWhereClause($whereClause): string
    {
        $result = parent::walkWhereClause($whereClause);

        if ($index = $this->getQuery()->getHint(self::HINT_SELECT_FOR_UPDATE)) {
            $result .= ' FOR UPDATE';
        }

        return $result;
    }

    public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = Join::JOIN_TYPE_INNER, $condExpr = null): string
    {
        $result = parent::walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType, $condExpr);

        if ($ignoreIndex = $this->getQuery()->getHint(static::HINT_IGNORE_INDEX_ON_JOIN)) {
            [$index, $table] = $ignoreIndex;
            if (2 != \count($ignoreIndex) || empty($table)) {
                throw new Exception('Ignore index on join hint with invalid parameters!');
            }

            if (\preg_match('/`' . $table . '`/', $result)) {
                $result = \preg_replace('/ON/', 'IGNORE INDEX (' . $index . ') ON', $result);
            }
        }

        return $result;
    }
}
