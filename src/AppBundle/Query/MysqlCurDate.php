<?php
namespace AppBundle\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * Provides mysql current date CURDATE() function
 *
 * Provides mysql current date function, to ensure that date time comparisons always use the same time zone (the one
 * from the mysql server)
 *
 * @package AppBundle\Query
 */
class MysqlCurDate extends FunctionNode
{
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'CURDATE()';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}