<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Function;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Drjele\Doctrine\Utility\Exception\Exception;

class JsonUnquote extends FunctionNode
{
    public const FUNCTION_NAME = 'JSON_UNQUOTE';

    public Node $jsonValExpr;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $jsonVal = $sqlWalker->walkStringPrimary($this->jsonValExpr);

        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return \sprintf('%s(%s)', static::FUNCTION_NAME, $jsonVal);
        }

        throw new Exception(\sprintf('method `%s` is not supported', static::FUNCTION_NAME));
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->jsonValExpr = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
