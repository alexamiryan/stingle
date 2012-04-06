<?
class TextsValuesFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
	
		$this->qb->select(new Field("*", "texts_vals"))
			->from(Tbl::get('TBL_TEXTS_VALUES', 'TextsValuesManager'), "texts_vals");
	}
	
	public function setId($textValId){
		if(empty($textValId) or !is_numeric($textValId)){
			throw new InvalidIntegerArgumentException("\$textValId have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "texts_vals"), $textValId));
		return $this;
	}
	
	public function setIdIn($textValIds){
		if(empty($textValIds) or !is_array($textValIds)){
			throw new InvalidIntegerArgumentException("\$textValIds have to be not empty array");
		}
		
		$this->qb->andWhere($this->qb->expr()->in(new Field("id", "texts_vals"), $textValIds));
		return $this;
	}
	
	public function setTextId($textId){
		if(empty($textId) or !is_numeric($textId)){
			throw new InvalidIntegerArgumentException("\$textId have to be not empty");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("text_id", "texts_vals"), $textId));
		return $this;
	}
	
	public function setTextIdIn($textIds){
		if(empty($textIds) or !is_array($textIds)){
			throw new InvalidIntegerArgumentException("\$textIds have to be not empty array");
		}
		
		$this->qb->andWhere($this->qb->expr()->in(new Field("text_id", "texts_vals"), $textIds));
		return $this;
	}
	
	public function setHostLanguageId($hostLanguageId){
		if(empty($hostLanguageId) or !is_numeric($hostLanguageId)){
			throw new InvalidIntegerArgumentException("\$hostLanguageId have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("host_language", "texts_vals"), $hostLanguageId));
		return $this;
	}
	
	public function setDisplay($display){
		if(!is_numeric($display)){
			throw new InvalidIntegerArgumentException("\$display have to be integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("display", "texts_vals"), $display));
		return $this;
	}
	
}
?>