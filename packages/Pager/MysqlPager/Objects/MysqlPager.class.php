<?php
class MysqlPager extends Pager{

	private $query;
	private $forcedAlgorithm = null;
	
	const METHOD_REPLACE = 1;
	const METHOD_CALC_ROWS = 2;

	/**
	 * Constructor. It defines MysqlQuery object
	 * to be able to use Mysql queries.
	 * @param integer $resultsPerPage
	 * @param string $id
	 */
	public function __construct($resultsPerPage, $id = null){
		parent::__construct($resultsPerPage, $id);

		$this->query = MySqlDbManager::getQueryObject();
	}

	public function setAlgorithm($algorithm){
		if(!in_array($algorithm, $this->getConstsArray("METHOD"))){
			throw new InvalidArgumentException("Invalid algorithm specified.");
		}
		$this->forcedAlgorithm = $algorithm;
	}
	
	/**
	 * Atlers query. It inserts "COUNT(*) as `count`" after
	 * first SELECT statement preserving SELECT's special keywords.
	 * @param string $query
	 * @return string
	 */
	private function alterQueryForReplace($query){
		if(empty($query)){
			throw new InvalidArgumentException("\$query have to be non empty string");
		}

		if(!preg_match("/^SELECT\\s/i", $query)){
			throw new InvalidArgumentException("Query must have SELECT statement at the beggining");
		}

		$query = preg_replace("/^(SELECT\\s+?[(ALL)|(DISTINCT)|(DISTINCTROW)|(HIGH_PRIORITY)|(STRAIGHT_JOIN)|(SQL_SMALL_RESULT)|(SQL_BIG_RESULT)|(SQL_BUFFER_RESULT)|(SQL_CACHE)|(SQL_NO_CACHE)|(SQL_CALC_FOUND_ROWS)]*?)(.+?)(FROM)/is", '${1} COUNT(*) as `count` ${3}', $query);

		return $query;
	}
	
	/**
	 * Atlers query. It inserts "SQL_CALC_FOUND_ROWS" after
	 * first SELECT statement.
	 * @param string $query
	 * @return string
	 */
	private function alterQueryForCalcRows($query){
		if(empty($query)){
			throw new InvalidArgumentException("\$query have to be non empty string");
		}

		if(!preg_match("/^SELECT\\s/is", $query)){
			throw new InvalidArgumentException("Query must have SELECT statement at the beggining");
		}

		$query = preg_replace("/^SELECT\\s/i", "SELECT SQL_CALC_FOUND_ROWS ", $query);

		return $query;
	}
	
	/**
	 * Atlers query. It inserts LIMIT statement at the end of query.
	 * @param string $query
	 * @param integer $offset
	 * @param integer $length
	 * @return string
	 */
	private function alterQueryForLimit($query, $offset, $length){
		if(empty($query)){
			throw new InvalidArgumentException("\$query have to be non empty string");
		}

		if(preg_match("/LIMIT\\s+?\\d+?\\s*?(?:,\\s*?\\d+?\\s*?)?$/is", $query)){
			throw new InvalidArgumentException("Query mustn't have LIMIT statement at the end");
		}

		$query = preg_replace("/\\s*$/i", "", $query) . " LIMIT $offset, $length";

		return $query;
	}
	
	/**
	 * Excute given query using Calc Rows method
	 * @param string $query
	 * @return MySqlQuery
	 */
	private function executePagedSQLUsingCalcRows($query, $cacheMinutes = 0, $cacheTag = null){
		$offsetLength = $this->getOffsetLength();
		$query = $this->alterQueryForCalcRows($query);
		$query = $this->alterQueryForLimit($query, $offsetLength['offset'], $offsetLength['length']);
		$this->query->exec($query, $cacheMinutes, $cacheTag);

		$executedQueryObj = clone($this->query);

		$this->setTotalRecordsCount($this->query->getFoundRowsCount($cacheMinutes, $cacheTag));

		return $executedQueryObj;
	}
	
	/**
	 * Excute given query using Replace method
	 * @param string $query
	 * @return MySqlQuery
	 */
	private function executePagedSQLUsingReplace($query, $cacheMinutes = 0, $cacheTag = null){
		$countQuery = $this->alterQueryForReplace($query);
		$this->query->exec($countQuery, $cacheMinutes);
		$recordsCount = $this->query->fetchField("count");
		
		$this->setTotalRecordsCount($recordsCount);
		
		$offsetLength = $this->getOffsetLength();
		$queryWithLimit = $this->alterQueryForLimit($query, $offsetLength['offset'], $offsetLength['length']);
		$this->query->exec($queryWithLimit, $cacheMinutes, $cacheTag);

		return clone($this->query);
	}
	
	/**
	 * Execute given query using given method, or
	 * let it choose method itself
	 * @param string $query
	 * @param int $alterMethod
	 * @return MySqlQuery
	 */
	public function executePagedSQL($query, $cacheMinutes = 0, $alterMethod = null, $cacheTag = null){
		if(empty($query)){
			throw new InvalidArgumentException("\$query have to be non empty string");
		}
		
		
		if($this->forcedAlgorithm === null){
			if($alterMethod === null){
				$alterMethod = static::METHOD_REPLACE;
			}
			
			if(preg_match("/GROUP\\s+?BY/is", $query)){
				$alterMethod = static::METHOD_CALC_ROWS;
			}
		}
		else{
			$alterMethod = $this->forcedAlgorithm;
		}
		
		$queryWithResult = null;
		switch ($alterMethod) {
			case static::METHOD_REPLACE :
				$queryWithResult = $this->executePagedSQLUsingReplace($query, $cacheMinutes, $cacheTag);
				break;
			case static::METHOD_CALC_ROWS :
				$queryWithResult = $this->executePagedSQLUsingCalcRows($query, $cacheMinutes, $cacheTag);
				break;
			default :
				throw new InvalidArgumentException("Incorrect alter method given!");
		}
		
		return $queryWithResult;
	}
	
	/**
	 * Alters given sql query, executes it and returns
	 * assoc array of results. You can specify the alter
	 * method or you can let it choose automaticly.
	 * @param string $query
	 * @param int $alterMethod
	 * @return array
	 */
	public function getRecords($query, $cacheMinutes = 0, $alterMethod = null){
		return $this->executePagedSQL($query, $cacheMinutes = 0, $alterMethod)->fetchRecords();
	}
}
