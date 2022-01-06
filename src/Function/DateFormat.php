<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\Doctrine\Utility\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class DateFormat extends FunctionNode
{
    public const FUNCTION_NAME = 'DATE_FORMAT';

    public Node $firstDateExpression;
    public Node $secondDateExpression;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            '%s(%s, %s)',
            static::FUNCTION_NAME,
            $this->firstDateExpression->dispatch($sqlWalker),
            $this->secondDateExpression->dispatch($sqlWalker)
        );
    }
}
