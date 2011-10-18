<?
class Info
{
	private $elements = array();
	private $sessRef;

	/**
	 * @param string $sessionVariable (e.g. $_SESSION['error'])
	 */
	public function __construct(&$sessionVariable){
		$this->sessRef=&$sessionVariable;
		if(!empty($sessionVariable) and is_array($sessionVariable)){
			$this->elements=$sessionVariable;
		}
	}
	
	/**
	 * Destructor
	 */
	public function __destruct(){
		$this->updateSessionObj();
	}

	/**
	 * Get count of elements
	 *
	 * @return int
	 */
	public function getCount(){
		return count($this->elements);
	}

	/**
	 * Add new element
	 *
	 * @param string $text
	 */
	public function add($text){
		array_push($this->elements, $text);
		$this->updateSessionObj();
	}

	/**
	 * Shift one element
	 *
	 * @return string
	 */
	public function shift(){
		$text = array_shift($this->elements);
		if($text != null){
			$this->updateSessionObj();
			return $text;
		}
		return false;
	}

	/**
	 * Clear queue
	 *
	 */
	public function clear(){
		$this->elements=array();
		$this->updateSessionObj();
	}

	/**
	 * Returns true if elements queue is empty
	 */
	public function isEmptyQueue(){
		if(count($this->elements)==0){
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with all elements
	 *
	 * @return array
	 */
	public function getAll(){
		$elementsToReturn = $this->elements;
		$this->emptyQueue();
		return $elementsToReturn;
	}

	/**
	 * Empty the queue without shifting
	 *
	 */
	public function emptyQueue(){
		unset($this->elements);
		$this->elements=array();
	}

	/**
	 * Update session variable
	 */
	private function updateSessionObj(){
		$this->sessRef = $this->elements;
	}
}
?>