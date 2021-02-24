<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Query;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Drjele\DoctrineUtility\Exception\Exception;

class JsonContains extends FunctionNode
{
    public const FUNCTION_NAME = 'JSON_CONTAINS';

    public Node $jsonDocExpr;
    public Node $jsonValExpr;
    public Node $jsonPathExpr;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $jsonDoc = $sqlWalker->walkStringPrimary($this->jsonDocExpr);
        $jsonVal = $sqlWalker->walkStringPrimary($this->jsonValExpr);

        $jsonPath = '';
        if (!empty($this->jsonPathExpr)) {
            $jsonPath = ', ' . $sqlWalker->walkStringPrimary($this->jsonPathExpr);
        }

        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return \sprintf('%s(%s, %s)', static::FUNCTION_NAME, $jsonDoc, $jsonVal . $jsonPath);
        }

        throw new Exception(\sprintf('Method "%s" is not suported!', static::FUNCTION_NAME));
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->jsonDocExpr = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->jsonValExpr = $parser->StringPrimary();

        if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->jsonPathExpr = $parser->StringPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
