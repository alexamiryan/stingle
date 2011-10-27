<?
class TextsValuesFilter extends Filter {
	
	
	public function setId($textValId, $match = Filter::MATCH_EQUAL){
		if(empty($textValId)){
			throw new InvalidIntegerArgumentException("\$textValId have to be not empty");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		if(!is_array($textValId)){
			if(!is_numeric($textValId)){
				throw new InvalidIntegerArgumentException("\$textValId have to be non zero integer");
			}
			$textValId = array($textValId);
		}
		
		if(count($textValId) == 1){
			$this->setCondition(TextsValuesManager::FILTER_ID_FIELD, $match, $textValId[0]);
		}
		else{
			$this->setCondition(TextsValuesManager::FILTER_ID_FIELD, $match, $textValId);
		}
		return $this;
	}
	
	public function setTextId($textId, $match = Filter::MATCH_EQUAL){
		if(empty($textId)){
			throw new InvalidIntegerArgumentException("\$textId have to be not empty");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		if(!is_array($textId)){
			if(!is_numeric($textId)){
				throw new InvalidIntegerArgumentException("\$textId have to be non zero integer");
			}
			$textId = array($textId);
		}
		
		if(count($textId) == 1){
			$this->setCondition(TextsValuesManager::FILTER_TEXT_ID_FIELD, $match, $textId[0]);
		}
		else{
			$this->setCondition(TextsValuesManager::FILTER_TEXT_ID_FIELD, $match, $textId);
		}
		return $this;
	}
	
	public function setHostLanguageId($hostLanguageId, $match = Filter::MATCH_EQUAL){
		if(empty($hostLanguageId)){
			throw new InvalidIntegerArgumentException("\$hostLanguageId have to be not empty");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(TextsValuesManager::FILTER_HOST_LANGUAGE_FIELD, $match, $hostLanguageId);
		
		return $this;
	}
	
	public function setDisplay($display){
		if(!is_numeric($display)){
			throw new InvalidIntegerArgumentException("\$display have to be integer");
		}
		
		$this->setCondition(TextsValuesManager::FILTER_DISPLAY_FIELD, Filter::MATCH_EQUAL, $display);
		return $this;
	}
	
}
?>