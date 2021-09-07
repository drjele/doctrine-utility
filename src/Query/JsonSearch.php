<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Query;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Drjele\Doctrine\Utility\Exception\Exception;

class JsonSearch extends FunctionNode
{
    public const FUNCTION_NAME = 'JSON_SEARCH';

    public const MODE_ONE = 'one';
    public const MODE_ALL = 'all';

    public string $mode;
    public Node $jsonDocExpr;
    public Node $jsonSearchExpr;
    public Node $jsonEscapeExpr;
    public array $jsonPaths = [];

    public function getSql(SqlWalker $sqlWalker): string
    {
        $jsonDoc = $sqlWalker->walkStringPrimary($this->jsonDocExpr);
        $mode = $sqlWalker->walkStringPrimary($this->mode);
        $searchArgs = $sqlWalker->walkStringPrimary($this->jsonSearchExpr);

        if (!empty($this->jsonEscapeExpr)) {
            $searchArgs .= ', ' . $sqlWalker->walkStringPrimary($this->jsonEscapeExpr);

            if (!empty($this->jsonPaths)) {
                $jsonPaths = [];
                foreach ($this->jsonPaths as $path) {
                    $jsonPaths[] = $sqlWalker->walkStringPrimary($path);
                }
                $searchArgs .= ', ' . \implode(', ', $jsonPaths);
            }
        }

        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return \sprintf('%s(%s, %s, %s)', static::FUNCTION_NAME, $jsonDoc, $mode, $searchArgs);
        }

        throw new Exception(\sprintf('Method "%s" is not suported!', static::FUNCTION_NAME));
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->jsonDocExpr = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->parsePathMode($parser);

        $parser->match(Lexer::T_COMMA);

        $this->jsonSearchExpr = $parser->StringPrimary();

        if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->jsonEscapeExpr = $parser->StringPrimary();

            while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
                $this->jsonPaths[] = $parser->StringPrimary();
            }
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    protected function parsePathMode(Parser $parser): void
    {
        $lexer = $parser->getLexer();
        $value = $lexer->lookahead['value'];

        if (0 === \strcasecmp(self::MODE_ONE, $value)) {
            $this->mode = self::MODE_ONE;
            $parser->StringPrimary();

            return;
        }

        if (0 === \strcasecmp(self::MODE_ALL, $value)) {
            $this->mode = self::MODE_ALL;
            $parser->StringPrimary();

            return;
        }

        throw new Exception(
            \sprintf('Mode "%s" is not suported by "%s"', $value, static::FUNCTION_NAME)
        );
    }
}
