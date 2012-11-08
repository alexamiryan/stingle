<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * This class is used to generate SQL expressions via a set of PHP static functions
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class Expr
{
    /**
     * Creates an ASCending order expression.
     *
     * @param $sort
     * @return OrderBy
     */
    public function asc($expr)
    {
        return new OrderBy($expr, OrderBy::ASC);
    }

    /**
     * Creates a DESCending order expression.
     *
     * @param $sort
     * @return OrderBy
     */
    public function desc($expr)
    {
        return new OrderBy($expr, OrderBy::DESC);
    }

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?1
     *     $expr->equal('u.id', '?1');
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function equal($x, $y)
    {
        return new Comparison($x, Comparison::EQ, $y);
    }

    /**
     * Creates an instance of Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <> <right expr>. Example:
     *
     *     [php]
     *     // u.id <> ?1
     *     $q->where($q->expr()->notEqual('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function notEqual($x, $y)
    {
        return new Comparison($x, Comparison::NEQ, $y);
    }

    /**
     * Creates an instance of Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> < <right expr>. Example:
     *
     *     [php]
     *     // u.id < ?1
     *     $q->where($q->expr()->less('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function less($x, $y)
    {
        return new Comparison($x, Comparison::LT, $y);
    }

    /**
     * Creates an instance of Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <= <right expr>. Example:
     *
     *     [php]
     *     // u.id <= ?1
     *     $q->where($q->expr()->lessEqual('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function lessEqual($x, $y)
    {
        return new Comparison($x, Comparison::LTE, $y);
    }

    /**
     * Creates an instance of Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> > <right expr>. Example:
     *
     *     [php]
     *     // u.id > ?1
     *     $q->where($q->expr()->greater('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function greater($x, $y)
    {
        return new Comparison($x, Comparison::GT, $y);
    }

    /**
     * Creates an instance of Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> >= <right expr>. Example:
     *
     *     [php]
     *     // u.id >= ?1
     *     $q->where($q->expr()->greaterEqual('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Comparison
     */
    public function greaterEqual($x, $y)
    {
        return new Comparison($x, Comparison::GTE, $y);
    }

    /**
     * Creates an instance of AVG() function, with the given argument.
     *
     * @param mixed $x Argument to be used in AVG() function.
     * @return Func
     */
    public function avg($x)
    {
        return new Func('AVG', array($x));
    }

    /**
     * Creates an instance of MAX() function, with the given argument.
     *
     * @param mixed $x Argument to be used in MAX() function.
     * @return Func
     */
    public function max($x, $alias = null)
    {
        return new Func('MAX', array($x), $alias);
    }

    /**
     * Creates an instance of MIN() function, with the given argument.
     *
     * @param mixed $x Argument to be used in MIN() function.
     * @return Func
     */
    public function min($x, $alias = null)
    {
        return new Func('MIN', array($x), $alias);
    }

    /**
     * Creates an instance of COUNT() function, with the given argument.
     *
     * @param mixed $x Argument to be used in COUNT() function.
     * @return Func
     */
    public function count($x, $alias = null)
    {
        return new Func('COUNT', array($x), $alias);
    }

    /**
     * Creates an instance of COUNT(DISTINCT) function, with the given argument.
     *
     * @param mixed $x Argument to be used in COUNT(DISTINCT) function.
     * @return string
     */
    public function countDistinct($x, $alias = null)
    {
    	$params = func_get_args();
    	foreach($params as &$param){
    		$param = Expr::quoteLiteral($param);
    	}
    	
        $returnStr = 'COUNT(DISTINCT ' . implode(', ', $params) . ')';
        
        if($alias != null){
        	$returnStr .= "as `$alias`";
        }
        
        return $returnStr;
    }

    /**
     * Creates an instance of EXISTS() function, with the given SQL Subquery.
     *
     * @param mixed $subquery SQL Subquery to be used in EXISTS() function.
     * @return Func
     */
    public function exists($subquery)
    {
        return new Func('EXISTS', array($subquery));
    }

    /**
     * Creates an instance of ALL() function, with the given SQL Subquery.
     *
     * @param mixed $subquery SQL Subquery to be used in ALL() function.
     * @return Func
     */
    public function all($subquery)
    {
        return new Func('ALL', array($subquery));
    }

    /**
     * Creates a SOME() function expression with the given SQL subquery.
     *
     * @param mixed $subquery SQL Subquery to be used in SOME() function.
     * @return Func
     */
    public function some($subquery)
    {
        return new Func('SOME', array($subquery));
    }

    /**
     * Creates an ANY() function expression with the given SQL subquery.
     *
     * @param mixed $subquery SQL Subquery to be used in ANY() function.
     * @return Func
     */
    public function any($subquery)
    {
        return new Func('ANY', array($subquery));
    }

    /**
     * Creates a negation expression of the given restriction.
     *
     * @param mixed $restriction Restriction to be used in NOT() function.
     * @return Func
     */
    public function not($restriction)
    {
        return new Func('NOT', array($restriction));
    }

    /**
     * Creates an ABS() function expression with the given argument.
     *
     * @param mixed $x Argument to be used in ABS() function.
     * @return Func
     */
    public function abs($x)
    {
        return new Func('ABS', array($x));
    }

    /**
     * Creates a product mathematical expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> * <right expr>. Example:
     *
     *     [php]
     *     // u.salary * u.percentAnualSalaryIncrease
     *     $q->expr()->prod('u.salary', 'u.percentAnualSalaryIncrease')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Math
     */
    public function prod($x, $y)
    {
        return new Math($x, '*', $y);
    }

    /**
     * Creates a difference mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> - <right expr>. Example:
     *
     *     [php]
     *     // u.monthlySubscriptionCount - 1
     *     $q->expr()->diff('u.monthlySubscriptionCount', '1')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Math
     */
    public function diff($x, $y)
    {
        return new Math($x, '-', $y);
    }

    /**
     * Creates a sum mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> + <right expr>. Example:
     *
     *     [php]
     *     // u.numChildren + 1
     *     $q->expr()->diff('u.numChildren', '1')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Math
     */
    public function sum($x, $y)
    {
        return new Math($x, '+', $y);
    }

    /**
     * Creates a quotient mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> / <right expr>. Example:
     *
     *     [php]
     *     // u.total / u.period
     *     $expr->quot('u.total', 'u.period')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Math
     */
    public function quot($x, $y)
    {
        return new Math($x, '/', $y);
    }

    /**
     * Creates a SQRT() function expression with the given argument.
     *
     * @param mixed $x Argument to be used in SQRT() function.
     * @return Func
     */
    public function sqrt($x)
    {
        return new Func('SQRT', array($x));
    }

    /**
     * Creates an IN() expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IN() function
     * @param mixed $y Argument to be used in IN() function.
     * @return Func
     */
    public function in($x, $y)
    {
        /*if($y instanceof QueryBuilder){
        	$y = array($y->getSQL());
        }
        elseif($y instanceof Unionx){
        	$y = array($y);
        }*/
        return new Func($x . ' IN', $y);
    }

    /**
     * Creates a NOT IN() expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by NOT IN() function
     * @param mixed $y Argument to be used in NOT IN() function.
     * @return Func
     */
    public function notIn($x, $y)
    {
        /*if($y instanceof QueryBuilder){
        	$y = array($y->getSQL());
        }*/
        
        return new Func($x . ' NOT IN', (array) $y);
    }

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IS NULL
     * @return string
     */
    public function isNull($x)
    {
        return $x . ' IS NULL';
    }

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IS NOT NULL
     * @return string
     */
    public function isNotNull($x)
    {
        return $x . ' IS NOT NULL';
    }

    /**
     * Creates a LIKE() comparison expression with the given arguments.
     *
     * @param string $x Field in string format to be inspected by LIKE() comparison.
     * @param mixed $y Argument to be used in LIKE() comparison.
     * @return Comparison
     */
    public function like($x, $y)
    {
        return new Comparison($x, 'LIKE', $y);
    }

    /**
     * Creates a CONCAT() function expression with the given arguments.
     *
     * @param mixed $x First argument to be used in CONCAT() function.
     * @param mixed $x Second argument to be used in CONCAT() function.
     * @return Func
     */
    public function concat($x, $y)
    {
        return new Func('CONCAT', array($x, $y));
    }

    /**
     * Creates a SUBSTRING() function expression with the given arguments.
     *
     * @param mixed $x Argument to be used as string to be cropped by SUBSTRING() function.
     * @param integer $from Initial offset to start cropping string. May accept negative values.
     * @param integer $len Length of crop. May accept negative values.
     * @return Func
     */
    public function substring($x, $from, $len = null)
    {
        $args = array($x, $from);
        if (null !== $len) {
            $args[] = $len;
        }
        return new Func('SUBSTRING', $args);
    }

    /**
     * Creates a LOWER() function expression with the given argument.
     *
     * @param mixed $x Argument to be used in LOWER() function.
     * @return Func A LOWER function expression.
     */
    public function lower($x)
    {
        return new Func('LOWER', array($x));
    }

    /**
     * Creates an UPPER() function expression with the given argument.
     *
     * @param mixed $x Argument to be used in UPPER() function.
     * @return Func An UPPER function expression.
     */
    public function upper($x)
    {
        return new Func('UPPER', array($x));
    }

    /**
     * Creates a LENGTH() function expression with the given argument.
     *
     * @param mixed $x Argument to be used as argument of LENGTH() function.
     * @return Func A LENGTH function expression.
     */
    public function length($x)
    {
        return new Func('LENGTH', array($x));
    }

    /**
     * Quotes a literal value, if necessary, according to the SQL syntax.
     *
     * @param mixed $literal The literal value.
     * @return string
     */
    public static function quoteLiteral($literal){
    	if (($literal instanceof Literal) or ($literal instanceof Field) or ($literal instanceof QueryBuilder) or ($literal instanceof Func) or ($literal instanceof Math)) {
    		return $literal;
    	}
    	else{
	        if (is_numeric($literal) && !is_string($literal)) {
	            return (string) $literal;
	        } 
	        else {
	            return "'" . mysql_real_escape_string($literal) . "'";
	        }
    	}
    }

    /**
     * Creates an instance of BETWEEN() function, with the given argument.
     *
     * @param mixed $val Valued to be inspected by range values.
     * @param integer $x Starting range value to be used in BETWEEN() function.
     * @param integer $y End point value to be used in BETWEEN() function.
     * @return Func A BETWEEN expression.
     */
    public function between($val, $x, $y)
    {
        return Expr::quoteLiteral($val) . ' BETWEEN ' . Expr::quoteLiteral($x) . ' AND ' . Expr::quoteLiteral($y);
    }

    /**
     * Creates an instance of TRIM() function, with the given argument.
     *
     * @param mixed $x Argument to be used as argument of TRIM() function.
     * @return Func a TRIM expression.
     */
    public function trim($x)
    {
        return new Func('TRIM', $x);
    }
}
