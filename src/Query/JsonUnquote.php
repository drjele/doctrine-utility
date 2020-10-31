<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Query;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonUnquote extends FunctionNode
{
    const FUNCTION_NAME = 'JSON_UNQUOTE';

    public Node $jsonValExpr;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $jsonVal = $sqlWalker->walkStringPrimary($this->jsonValExpr);

        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return sprintf('%s(%s)', static::FUNCTION_NAME, $jsonVal);
        }

        throw DBALException::notSupported(static::FUNCTION_NAME);
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->jsonValExpr = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
