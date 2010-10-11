<?php
class Info
{
	private $elements;
	private $sess_ref;

	/**
	 * Information transportation class
	 *
	 * @param string $session_variable (e.g. $_SESSION['error'])
	 */
	public function __construct(&$session_variable){
		$this->elements=array();
		$this->sess_ref=&$session_variable;
		if(!empty($session_variable)){
			$sess_obj=unserialize($this->sess_ref);
			if(is_object($sess_obj)){
				if(($el_arr=$sess_obj->getAll(false))){
					$this->elements=$el_arr;
				}
			}
		}
	}
	public function __destruct(){
		$this->updateSessionObj();
	}

	/**
	 * Get count of information units
	 *
	 * @return int
	 */
	public function getCount(){
		return count($this->elements);
	}

	/**
	 * Add new information unit with replation
	 * NOTE: Replace works only with shiftLang()
	 *
	 * @param string $text
	 * @param string $replace1
	 * @param string $replace2
	 * ..............
	 * ..............
	 */
	public function add($text){
		$replace=array();
		$args_num = func_num_args();
		$args = func_get_args();
		if($args_num>1){
			for($i=1;$i<$args_num;$i++){
				array_push($replace, $args[$i]);
			}
		}
		array_push($this->elements, array($text, $replace));
		$this->updateSessionObj();
	}

	/**
	 * Shift one information unit
	 *
	 * @return string
	 */
	public function shift(){
		if($this->getCount()>0 and ($text=array_shift($this->elements))){
			$this->updateSessionObj();
			return $text;
		}
		return false;
	}

	/**
	 * Clear queue
	 *
	 * @return none
	 */
	public function clear(){
		$this->elements=array();
		$this->updateSessionObj();
		return true;
	}

	/**
	 * Shift one information unit and eval it with constant
	 *
	 * @return string
	 */
	public function shiftLang(){
		if($this->getCount()>0){
			$sh=$this->shift();
				$value = @constant($sh[0]);
			if(count($sh[1])){
				for ($i=0;$i<count($sh[1]);$i++){
					$sh[1][$i]="'" . str_replace("'", "\\'", $sh[1][$i]) . "'";
				}
				$args=implode(", ", $sh[1]);
				eval('$value = sprintf($value,'.$args.');');
			}
			if(!empty($value)){
				return nl2br($value);
			}
		}
		return false;
	}

	public function isEmptyQueue(){
		if(count($this->elements)==0){
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with all information units
	 *
	 * @return array
	 */
	public function getAll($is_lang=true){
		$ret_array=array();
		foreach ($this->elements as $el){
			if($is_lang){
				$ret_array[]=$this->shiftLang();
			}
			else{
				$ret_array[]=$this->shift();
			}
		}
		return $ret_array;
	}

	/**
	 * Empty the queue without shifting
	 *
	 */
	public function emptyQueue(){
		unset($this->elements);
		$this->elements=array();
	}

	private function updateSessionObj(){
		$this->sess_ref=serialize($this);
	}
}
?>