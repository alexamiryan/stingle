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
 * This class is responsible for building SQL query strings via an object oriented
 * PHP interface.
 *
 * @since 2.0
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Alex Amiryan <alex@amiryan.org>
 * @author Aram Gevorgyan <aram@web-emedianet.com>
 */

class QueryBuilder
{
    /* The query types. */
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;
    const INSERT = 3;
    
    /** The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * @var array The array of SQL parts collected.
     */
    private $_sqlParts = array(
        'select'  => array(),
        'insert'  => null,
        'from'    => array(),
        'join'    => array(),
        'set'     => array(),
        'where'   => null,
        'groupBy' => array(),
        'having'  => null,
        'orderBy' => array(),
        'limit' => null
    );

    /**
     * @var integer The type of query this is. Can be select, update or delete.
     */
    private $_type = self::SELECT;

    /**
     * @var integer The state of the query object. Can be dirty or clean.
     */
    private $_state = self::STATE_CLEAN;

    /**
     * @var string The complete SQL string for this query.
     */
    private $_sql;

    /**
     * @var array The query parameters.
     */
    private $_params = array();

    /**
     * @var array The parameter type map of this query.
     */
    private $_paramTypes = array();
    
    private $onDuplicateKeyUpdate = false;


    /**
     * Initializes a new <tt>QueryBuilder</tt>.
     */
    public function __construct()
    {
       
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * This producer method is intended for convenient inline usage. Example:
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where($qb->expr()->eq('u.id', 1));
     * </code>
     *
     * For more complex expression construction, consider storing the expression
     * builder object in a local variable.
     *
     * @return Expr
     */
    public function expr()
    {
        return new Expr();
    }

    /**
     * Get the type of the currently built query.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Get the state of this query builder instance.
     *
     * @return integer Either QueryBuilder::STATE_DIRTY or QueryBuilder::STATE_CLEAN.
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Get the complete SQL string formed by the current specifications of this QueryBuilder.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *     echo $qb->getSql(); // SELECT u FROM User u
     * </code>
     *
     * @return string The SQL query string.
     */
    public function getSQL()
    {
        if ($this->_sql !== null && $this->_state === self::STATE_CLEAN) {
            return $this->_sql;
        }

        $sql = '';

        switch ($this->_type) {
            case self::DELETE:
                $sql = $this->_getSQLForDelete();
                break;

            case self::UPDATE:
                $sql = $this->_getSQLForUpdate();
                break;
            
			case self::INSERT:
				$sql = $this->_getSQLForInsert();
				break;

            case self::SELECT:
            default:
                $sql = $this->_getSQLForSelect();
                break;
        }

        $this->_state = self::STATE_CLEAN;
        $this->_sql = $sql;

        return $sql;
    }

    /**
     * Gets the FIRST root alias of the query. This is the first entity alias involved
     * in the construction of the query.
     *
     * <code>
     * $qb = $em->createQueryBuilder()
     * ->select('u')
     * ->from('User', 'u');
     *
     * echo $qb->getRootAlias(); // u
     * </code>
     *
     * @deprecated Please use $qb->getRootAliases() instead.
     * @return string $rootAlias
     */
    public function getRootAlias()
    {
        $aliases = $this->getRootAliases();
        return $aliases[0];
    }

    /**
     * Gets the root aliases of the query. This is the entity aliases involved
     * in the construction of the query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     *
     *     $qb->getRootAliases(); // array('u')
     * </code>
     *
     * @return array $rootAliases
     */
    public function getRootAliases()
    {
        $aliases = array();

        foreach ($this->_sqlParts['from'] as &$fromClause) {
            if (is_string($fromClause)) {
                $spacePos = strrpos($fromClause, ' ');
                $from     = substr($fromClause, 0, $spacePos);
                $alias    = substr($fromClause, $spacePos + 1);

                $fromClause = new From($from, $alias);
            }

            $aliases[] = $fromClause->getAlias();
        }

        return $aliases;
    }
    
    public function getJoinAliases()
    {
    	$aliases = array();
    	foreach ($this->_sqlParts['join'] as $joins) {
    		foreach($joins as $join){
	    		if($join instanceof Join){
	    			$aliases[] = $join->getAlias();
	    		}
    		}
    	}
    	return $aliases;
    }
    
    public function getAliases()
    {
    	return array_merge($this->getRootAliases(), $this->getJoinAliases());
    }
    
    public function isAliasExtst($alias){
    	if(in_array($alias, $this->getAliases())){
    		return true;
    	}
    	return false;
    }

    /**
     * Gets the root entities of the query. This is the entity aliases involved
     * in the construction of the query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     *
     *     $qb->getRootEntities(); // array('User')
     * </code>
     *
     * @return array $rootEntities
     */
    public function getRootEntities()
    {
        $entities = array();

        foreach ($this->_sqlParts['from'] as &$fromClause) {
            if (is_string($fromClause)) {
                $spacePos = strrpos($fromClause, ' ');
                $from     = substr($fromClause, 0, $spacePos);
                $alias    = substr($fromClause, $spacePos + 1);

                $fromClause = new From($from, $alias);
            }

            $entities[] = $fromClause->getFrom();
        }

        return $entities;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param string|null $type PDO::PARAM_* or \Doctrine\DBAL\Types\Type::* constant
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameter($key, $value, $type = null)
    {
        $key = trim($key, ':');

        if ($type === null) {
            $type = Query\ParameterTypeInferer::inferType($value);
        }

        $this->_paramTypes[$key] = $type;
        $this->_params[$key] = $value;

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = :user_id1 OR u.id = :user_id2')
     *         ->setParameters(array(
     *             'user_id1' => 1,
     *             'user_id2' => 2
     *         ));
     * </code>
     *
     * @param array $params The query parameters to set.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameters(array $params, array $types = array())
    {
        foreach ($params as $key => $value) {
            $this->setParameter($key, $value, isset($types[$key]) ? $types[$key] : null);
        }

        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed.
     *
     * @return array The currently defined query parameters.
     */
    public function getParameters()
    {
        return $this->_params;
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param mixed $key The key (index or name) of the bound parameter.
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : null;
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'join', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string $sqlPartName
     * @param string $sqlPart
     * @param string $append
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function add($sqlPartName, $sqlPart, $append = false)
    {
        $isMultiple = is_array($this->_sqlParts[$sqlPartName]);

        // This is introduced for backwards compatibility reasons.
        if ($sqlPartName == 'join') {
            $newSqlPart = array();
            foreach ($sqlPart AS $k => $v) {
                if (is_numeric($k)) {
                    $newSqlPart[$this->getRootAlias()] = $v;
                } else {
                    $newSqlPart[$k] = $v;
                }
            }
            $sqlPart = $newSqlPart;
        }

        if ($append && $isMultiple) {
            if (is_array($sqlPart)) {
                $key = key($sqlPart);

                $this->_sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } else {
                $this->_sqlParts[$sqlPartName][] = $sqlPart;
            }
        } else {
            $this->_sqlParts[$sqlPartName] = ($isMultiple) ? array($sqlPart) : $sqlPart;
        }

        $this->_state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u', 'p')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p');
     * </code>
     *
     * @param mixed $select The selection expressions.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function select($select = null)
    {
        $this->_type = self::SELECT;

        if (empty($select)) {
            return $this;
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', new Select($selects), false);
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->addSelect('p')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p');
     * </code>
     *
     * @param mixed $select The selection expression.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addSelect($select = null)
    {
        $this->_type = self::SELECT;

        if (empty($select)) {
            return $this;
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', new Select($selects), true);
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->delete('User', 'u')
     *         ->where('u.id = :user_id');
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param string $delete The class/type whose instances are subject to the deletion.
     * @param string $alias The class/type alias used in the constructed query.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function delete($delete = null, $alias = null)
    {
        $this->_type = self::DELETE;

        if ( ! $delete) {
            return $this;
        }

        return $this->add('from', new From($delete, $alias));
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $update The class/type whose instances are subject to the update.
     * @param string $alias The class/type alias used in the constructed query.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function update($update = null, $alias = null)
    {
        $this->_type = self::UPDATE;

        if ( ! $update) {
            return $this;
        }

        return $this->add('from', new From($update, $alias));
    }

    /**
     * Create and add a query root corresponding to the entity identified by the given alias,
     * forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     * </code>
     *
     * @param string $from   The class name.
     * @param string $alias  The alias of the class.
     * @param string $indexBy The index for the from.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function from($from, $alias = null, $indexBy = null)
    {
        return $this->add('from', new From($from, $alias, $indexBy), true);
    }

    /**
     * Creates and adds a join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->join('u.Phonenumbers', 'p', Join::LEFT_JOIN, 'p.is_primary = 1');
     * </code>
     *
     * @param string $join The relationship to join
     * @param string $alias The alias of the join
     * @param string $joinType Type of the join
     * @param string $condition The condition for the join
     * @param string $indexBy The index for the join
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function join($join, $alias, $joinType, $condition = null, $indexBy = null)
    {
        $rootAlias = substr($join, 0, strpos($join, '.'));
        
        if ( ! in_array($rootAlias, $this->getRootAliases())) {
        	$rootAlias = $this->getRootAlias();
        }
        
        if(!$this->isAliasExtst($alias)){
        	$join = new Join($joinType, $join, $alias, $condition, $indexBy);
        
        	return $this->add('join', array($rootAlias => $join), true);
        }
        return $this;
    }

    /**
     * Creates and adds a join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->innerJoin('u.Phonenumbers', 'p', 'p.is_primary = 1');
     *
     * @param string $join The relationship to join
     * @param string $alias The alias of the join
     * @param string $condition The condition for the join
     * @param string $indexBy The index for the join
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function innerJoin($join, $alias, $condition = null, $indexBy = null)
    {
        return $this->join($join, $alias, Join::INNER_JOIN, $condition, $indexBy);
    }

    /**
     * Creates and adds a right join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->rightJoin('u.Phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $join The relationship to join
     * @param string $alias The alias of the join
     * @param string $condition The condition for the join
     * @param string $indexBy The index for the join
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function rightJoin($join, $alias, $condition = null, $indexBy = null)
    {
        return $this->join($join, $alias, Join::RIGHT_JOIN, $condition, $indexBy);
    }
    
    /**
     * Creates and adds a outer join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->outerJoin('u.Phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $join The relationship to join
     * @param string $alias The alias of the join
     * @param string $condition The condition for the join
     * @param string $indexBy The index for the join
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function outerJoin($join, $alias, $condition = null, $indexBy = null)
    {
    	return $this->join($join, $alias, Join::OUTER_JOIN, $condition, $indexBy);
    }
    
    /**
     * Creates and adds a left join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $join The relationship to join
     * @param string $alias The alias of the join
     * @param string $condition The condition for the join
     * @param string $indexBy The index for the join
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function leftJoin($join, $alias, $condition = null, $indexBy = null)
    {
    	return $this->join($join, $alias, Join::LEFT_JOIN, $condition, $indexBy);
    }

    /**
     * Sets a new value for a field in a bulk update query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $key The key/field to set.
     * @param string $value The value, expression, placeholder, etc.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function set(Field $key, $value)
    {
    	if ( !($value instanceof Literal) and !($value instanceof Field)) {
    		$value = $this->expr()->quoteLiteral($value);
    	}
    	
        return $this->add('set', new Comparison($key, Comparison::EQ, $value), true);
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = ?');
     *
     *     // You can optionally programatically build and/or expressions
     *     $qb = $em->createQueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->add($qb->expr()->eq('u.id', 1));
     *     $or->add($qb->expr()->eq('u.id', 2));
     *
     *     $qb->update('User', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where($or);
     * </code>
     *
     * @param mixed $predicates The restriction predicates.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function where($predicates)
    {
        if ( ! (func_num_args() == 1 && $predicates instanceof Composite)) {
            $predicates = new Andx(func_get_args());
        }

        return $this->add('where', $predicates);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @param mixed $where The query restrictions.
     * @return QueryBuilder This QueryBuilder instance.
     * @see where()
     */
    public function andWhere($where)
    {
        $where = $this->getSQLPart('where');
        $args = func_get_args();

        if ($where instanceof Andx) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new Andx($args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @param mixed $where The WHERE statement
     * @return QueryBuilder $qb
     * @see where()
     */
    public function orWhere($where)
    {
        $where = $this->getSqlPart('where');
        $args = func_get_args();

        if ($where instanceof Orx) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new Orx($args);
        }

        return $this->add('where', $where, true);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string $groupBy The grouping expression.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function groupBy($groupBy)
    {
        return $this->add('groupBy', new GroupBy(func_get_args()));
    }


    /**
     * Adds a grouping expression to the query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.lastLogin');
     *         ->addGroupBy('u.createdAt')
     * </code>
     *
     * @param string $groupBy The grouping expression.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new GroupBy(func_get_args()), true);
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed $having The restriction over the groups.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function having($having)
    {
        if ( ! (func_num_args() == 1 && ($having instanceof Andx || $having instanceof Orx))) {
            $having = new Andx(func_get_args());
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to append.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function andHaving($having)
    {
        $having = $this->getSqlPart('having');
        $args = func_get_args();

        if ($having instanceof Andx) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new Andx($args);
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to add.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orHaving($having)
    {
        $having = $this->getSqlPart('having');
        $args = func_get_args();

        if ($having instanceof Orx) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new Orx($args);
        }

        return $this->add('having', $having);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort The ordering expression.
     * @param string $order The ordering direction.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy',  $sort instanceof OrderBy ? $sort
                : new OrderBy($sort, $order));
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort The ordering expression.
     * @param string $order The ordering direction.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', new OrderBy($sort, $order), true);
    }
    
    /**
     * Specifies an limit for the query
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u', 'p')
     *         ->from('User', 'u')
     *         ->limit(0, 20);
     * </code>
     *
     * @param mixed $select The selection expressions.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function limit($offset, $length = null)
    {
    	if($length === null){
    		return $this->add('limit', "0, $offset", false);
    	}
    	return $this->add('limit', "$offset, $length", false);
    }
    
    public function insert($tblName, $type = Insert::TYPE_NORMAL){
    	
    	$this->_type = self::INSERT;
    	
    	return $this->add('insert', new Insert($tblName, $type), false);
    }
    
    public function fields($fields){
    	if($this->_sqlParts['insert'] instanceof Insert){
    		if(!is_array($fields)){
    			$fields = func_get_args();
    		}
    		$this->_sqlParts['insert']->setFields($fields);
    	}
    	
    	return $this;
    }
    
    public function values($values){
    	if($this->_sqlParts['insert'] instanceof Insert){
    		if(!is_array($values)){
    			$values = func_get_args();
    		}
    		$this->_sqlParts['insert']->setValues($values);
    	}
    	
    	return $this;
    }
    
    public function onDuplicateKeyUpdate(){
   	 	$this->onDuplicateKeyUpdate = true;
   	 	
   	 	return $this;
    }

    /**
     * Get a query part by its name.
     *
     * @param string $queryPartName
     * @return mixed $queryPart
     */
    public function getSQLPart($queryPartName)
    {
        return $this->_sqlParts[$queryPartName];
    }

    /**
     * Get all query parts.
     *
     * @return array $sqlParts
     */
    public function getSQLParts()
    {
        return $this->_sqlParts;
    }

    private function _getSQLForDelete()
    {
         return 'DELETE'
              . $this->_getReducedSQLQueryPart('from', array('pre' => ' ', 'separator' => ', '))
              . $this->_getReducedSQLQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedSQLQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }

    private function _getSQLForUpdate()
    {
         return 'UPDATE'
              . $this->_getReducedSQLQueryPart('from', array('pre' => ' ', 'separator' => ', '))
              . $this->_getReducedSQLQueryPart('set', array('pre' => ' SET ', 'separator' => ', '))
              . $this->_getReducedSQLQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedSQLQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '));
    }
    
    private function _getSQLForInsert()
    {
    	return 'INSERT'
    	. $this->_getReducedSQLQueryPart('insert', array('pre' => ' ', 'separator' => ''))
    	. ($this->onDuplicateKeyUpdate ? $this->_getReducedSQLQueryPart('set', array('pre' => ' ON DUPLICATE KEY UPDATE ', 'separator' => ', ')) : '');
    }

    private function _getSQLForSelect()
    {
        $sql = 'SELECT' . $this->_getReducedSQLQueryPart('select', array('pre' => ' ', 'separator' => ', '));

        $fromParts   = $this->getSQLPart('from');
        $joinParts   = $this->getSQLPart('join');
        $fromClauses = array();

        // Loop through all FROM clauses
        if ( ! empty($fromParts)) {
            $sql .= ' FROM ';

            foreach ($fromParts as $from) {
                $fromClause = (string) $from;

                if ($from instanceof From && isset($joinParts[$from->getAlias()])) {
                    foreach ($joinParts[$from->getAlias()] as $join) {
                        $fromClause .= ' ' . ((string) $join);
                    }
                }

                $fromClauses[] = $fromClause;
            }
        }

        $sql .= implode(', ', $fromClauses)
              . $this->_getReducedSQLQueryPart('where', array('pre' => ' WHERE '))
              . $this->_getReducedSQLQueryPart('groupBy', array('pre' => ' GROUP BY ', 'separator' => ', '))
              . $this->_getReducedSQLQueryPart('having', array('pre' => ' HAVING '))
              . $this->_getReducedSQLQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedSQLQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ', '));

        return $sql;
    }

    private function _getReducedSQLQueryPart($queryPartName, $options = array())
    {
        $queryPart = $this->getSQLPart($queryPartName);

        if (empty($queryPart)) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        return (isset($options['pre']) ? $options['pre'] : '')
             . (is_array($queryPart) ? implode($options['separator'], $queryPart) : $queryPart)
             . (isset($options['post']) ? $options['post'] : '');
    }

    /**
     * Reset SQL parts
     *
     * @param array $parts
     * @return QueryBuilder
     */
    public function resetSQLParts($parts = null)
    {
        if (is_null($parts)) {
            $parts = array_keys($this->_sqlParts);
        }
        foreach ($parts as $part) {
            $this->resetSQLPart($part);
        }
        return $this;
    }

    /**
     * Reset single SQL part
     *
     * @param string $part
     * @return QueryBuilder;
     */
    public function resetSQLPart($part)
    {
        if (is_array($this->_sqlParts[$part])) {
            $this->_sqlParts[$part] = array();
        } else {
            $this->_sqlParts[$part] = null;
        }
        $this->_state = self::STATE_DIRTY;
        return $this;
    }
    
    /**
     * Merge to 
     * @param QueryBuilder $qb
     */
    public function merge(QueryBuilder $qb){
    	$parts = $qb->getSQLParts();
    	
    	/*'select'  => array(),
        'from'    => array(),
        'join'    => array(),
        'set'     => array(),
        'where'   => null,
        'groupBy' => array(),
        'having'  => null,
        'orderBy' => array(),
        'limit' => null*/
    	
    	foreach($parts['select'] as $select){
    		$this->add("select", $select, true);
    	}
    	
    	foreach($parts['from'] as $from){
    		$this->add("from", $from, true);
    	}
    	
    	foreach($parts['join'] as $join){
    		$this->add("join", $join, true);
    	}
    	
    	foreach($parts['set'] as $set){
    		$this->add("set", $set, true);
    	}
    	
    	foreach($parts['where'] as $where){
    		$this->add("where", $where, true);
    	}
    	
    	foreach($parts['groupBy'] as $groupBy){
    		$this->add("groupBy", $groupBy, true);
    	}
    	
    	foreach($parts['having'] as $having){
    		$this->add("having", $having, true);
    	}
    	
    	foreach($parts['orderBy'] as $orderBy){
    		$this->add("orderBy", $orderBy, true);
    	}
    	
    	$this->add("limit", $parts['limit'], false);
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     */
    public function __toString()
    {
        return $this->getSQL();
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_sqlParts AS $part => $elements) {
            if (is_array($this->_sqlParts[$part])) {
                foreach ($this->_sqlParts[$part] AS $idx => $element) {
                    if (is_object($element)) {
                        $this->_sqlParts[$part][$idx] = clone $element;
                    }
                }
            } else if (\is_object($elements)) {
                $this->_sqlParts[$part] = clone $elements;
            }
        }
    }
}