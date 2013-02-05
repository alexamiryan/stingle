<?php
abstract class Pager extends Model{
	
	/**
	 * ID of this Pager object
	 * @var string
	 */
	private $id;
	
	/**
	 * Number of results to show in one page
	 * @var integer
	 */
	private $resultsPerPage;
	
	/**
	 * Current page number
	 * @var integer
	 */
	private $pageNumber = 1;
	
	/**
	 * Number of overall records in the 
	 * resultset (not in page) 
	 * @var integer
	 */
	private $totalRecordsCount;
	
	/**
	 * Static array of Pager instances
	 * @var array
	 */
	protected static $pagerInstances = array();
	
	/**
	 * Url Param for getting Page number from $_GET
	 * @var string
	 */
	protected $UrlParam = "p";
	
	/**
	 * Class constructor.
	 * @param integer $resultsPerPage
	 * @param string $id = null
	 */
	public function __construct($resultsPerPage, $id = null){
		if(empty($resultsPerPage) or !is_numeric($resultsPerPage)){
			throw new InvalidArgumentException("\$resultPerPage have to be positive integer number");
		}
		
		$this->resultsPerPage = $resultsPerPage;
		if($id !== null){
			$this->id = $id;
		}
		
		$this->initPageNumberFromGet();
		
		static::$pagerInstances[$this->id] = $this;
	}
	
	/**
	 * Initiate page namber from $_GET[]
	 */
	private function initPageNumberFromGet(){
		if(array_key_exists($this->getUrlParam(), $_GET) and is_numeric($_GET[$this->getUrlParam()]) and $_GET[$this->getUrlParam()] > 0){
			$this->pageNumber = intval($_GET[$this->getUrlParam()]);
		}
	}
	
	/**
	 * Set current page number. This must be use 
	 * if you don't want Pager to take it from GET
	 * @param integer $pageNumber
	 */
	public function setPageNumber($pageNumber){
		if(empty($pageNumber) or !is_numeric($pageNumber)){
			throw new InvalidArgumentException("\$pageNumber have to be positive integer number");
		}
		
		$this->pageNumber = $pageNumber;
	}
	
	/**
	 * Get current pager object's ID
	 * @return string
	 */
	public function getId(){
		return $this->id;
	}
	
	/**
	 * Get current page number
	 * @return integer
	 */
	public function getCurrentPageNumber(){
		return $this->pageNumber;
	}
	
	/**
	 * Set total records count. Child classes have to 
	 * call this function to set total records count.
	 * @param integer $count
	 */
	protected function setTotalRecordsCount($count){
		if(!is_numeric($count)){
			throw new InvalidArgumentException("\$count have to be integer");
		}
		$this->totalRecordsCount = $count;
	}
	
	/**
	 * Get total records count. Previosly it has to be 
	 * set by setTotalRecordsCount() function otherwise 
	 * exception is throwed.
	 * @return integer
	 */
	public function getTotalRecordsCount(){
		if($this->totalRecordsCount === null){
			throw new RuntimeException("TotalRecordsCount is not yet initialized.");
		}
		
		return $this->totalRecordsCount;
	}
	
	/**
	 * Get total pages count. If totalRecordsCount have 
	 * not been set previosly exception is throwed.
	 * @return integer
	 */
	public function getTotalPagesCount(){
		return ceil($this->getTotalRecordsCount() / $this->resultsPerPage);
	}
	
	/**
	 * Get current Url Parameter
	 * @return string
	 */
	public function getUrlParam(){
		return $this->id . $this->UrlParam;
	}
	
	/**
	 * Set Url Parameter other than default.
	 * @param string $urlParam
	 */
	public function setUrlParam($urlParam){
		if(empty($urlParam)){
			throw new InvalidArgumentException("\$urlParam have to be non empty string");
		}
		
		$this->UrlParam = $urlParam;
		
		//Reinit page number
		$this->initPageNumberFromGet();
	}
	
	/**
	 * Get offset and length. This function is 
	 * for child classes. 
	 * @return array(offset, length)
	 */
	protected function getOffsetLength(){
		return array(   "offset" => ($this->pageNumber-1) * $this->resultsPerPage, 
						"length" => $this->resultsPerPage);
	}
	
	/**
	 * Get pager instance with id $id
	 * @param string $id
	 * @return Pager
	 */
	public static function getPager($id = null){
		return static::$pagerInstances[$id];
	}
}
