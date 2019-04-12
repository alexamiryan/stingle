<?php

class ProfileDeprecated extends DbAccessor{

	/**
	 * Identifier for profile questions
	 * that have to have only one answer
	 *
	 * @const integer
	 */
	const TYPE_SINGLE = 1;

	/**
	 * Identifier for profile questions
	 * that can have multiple answers
	 *
	 * @const integer
	 */
	const TYPE_MULTIPLE = 2;

	const TBL_PROFILE_KEYS = "users_profile_keys";

	/**
	 * Class constructor
	 *
	 */
	public function __construct($instanceName = null){
		parent::__construct($instanceName);
	}

	/**
	 * Get all questions with their answers
	 *
	 * @return array ( question => ( answers => (id => answer_id, value => answer_value), type => type_id) )
	 */
	public function getQuestions($cacheMinutes = 0){
		$return_array = array();
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_PROFILE_KEYS'))
			->orderBy(new Field('key'))
			->addOrderBy(new Field('sort_id'));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords()){
			while(($row = $this->query->fetchRecord()) != false){
				if(!isset($return_array[$row['key']])){
					$return_array[$row['key']] = array("answers" => array(), "type" => $row['type']);
				}
				array_push($return_array[$row['key']]["answers"], array("id" => $row['id'], "value" => $row['value']));
			}
		}
		return $return_array;
	}

	/**
	 * Return's all possible answers the question may have
	 * @param string $question
	 * @return array
	 */
	public function getQuestionOptions($question, $cacheMinutes = 0){
		if(!is_string($question) or empty($question)){
			throw new InvalidArgumentException("\$question have to be string");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('value'))
			->from(Tbl::get('TBL_PROFILE_KEYS'))
			->orderBy(new Field('sort_id'));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchFields("value");
	}

	/**
	 * get Question by option id
	 *
	 * @param $profile_id
	 * @return string
	 */
	public function getQuestionByProfileId($profile_id, $cacheMinutes = 0){
		if(empty($profile_id) or !is_numeric($profile_id)){
			throw new InvalidArgumentException("\$profile have to be numeric id of the profile");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('key'))
			->from(Tbl::get('TBL_PROFILE_KEYS'))
			->where($qb->expr()->equal(new Field('id'), $profile_id));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField("key");
	}

	/**
	 * get option value by id
	 *
	 * @param $profile_id
	 * @return string
	 */
	public function getOptionValueById($profile_id, $cacheMinutes = 0){
		if(empty($profile_id) or !is_numeric($profile_id)){
			throw new InvalidArgumentException("\$profile have to be numeric id of the profile");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('value'))
			->from(Tbl::get('TBL_PROFILE_KEYS'))
			->where($qb->expr()->equal(new Field('id'), $profile_id));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField("value");
	}

	/**
	 * add a new quaestion
	 *
	 * @param string $question a key by which this question will be addressed in the future
	 * @param array $options an array containing possible answers to the question. En exmample is:
	 * 						array('red', 'green', 'blue')
	 * @param $type
	 */
	public function addQuestion($question, $options, $type = TYPE_SINGLE){
		if(empty($question)){
			throw new InvalidArgumentException("\$question have be not null string");
		}
		if(!is_array($options) or count($options) == 0){
			throw new InvalidArgumentException("\$answers have to be not null array");
		}

		$sort_id_counter = 10;
		foreach($options as $answer){
			$qb = new QueryBuilder();
			$qb->insert(Tbl::get('TBL_PROFILE_KEYS'))
				->values(array(
							"key" => $question, 
							"value" => $answer, 
							"type" => $type, 
							"sort_id" => $sort_id_counter, 
						)
					);	
			$this->query->exec($qb->getSQL());
			$sort_id_counter += 10;
		}
	}

	/**
	 * Update the sortId of an already created answer option
	 *
	 * @param integer $profile_id
	 * @param integer $new_sort_id
	 */
	public function setSortId($profile_id, $new_sort_id){
		if(empty($profile_id) or !is_numeric($profile_id)){
			throw new InvalidArgumentException("\$profile have to be numeric id of the profile");
		}
		if(!is_numeric($new_sort_id)){
			throw new InvalidArgumentException("\$new_sort_id have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_PROFILE_KEYS'))
			->set(new Field('sort_id'), $new_sort_id)
			->where($qb->expr()->equal(new Field('id'), $profile_id));
		$this->query->exec($qb->getSQL());
	}

	/**
	 * Add a new answer option to an existing question
	 *
	 * @param string $question
	 * @param string $answer the text of the option to be created
	 * @param integer $sort_id an id to alter the position of the field in the final listing
	 */
	public function addOption($question, $answer, $sort_id = 0){
		if(empty($question)){
			throw new InvalidArgumentException("\$question must not be a null string");
		}
		if(empty($answer)){
			throw new InvalidArgumentException("\$answer must not be a null string");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('type'))
			->from(Tbl::get('TBL_PROFILE_KEYS'))
			->where($qb->expr()->equal(new Field('key'), $question))
			->limit(1);
		$this->query->exec($qb->getSQL());
		if(!$this->query->countRecords()){
			throw new OutOfRangeException("Can't add answer to non existent question");
		}
		$type = $this->query->fetchField("type");
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_PROFILE_KEYS'))
			->values(array(
							"key" => $question, 
							"value" => $answer, 
							"type" => $type, 
							"sort_id" => $sort_id, 
						)
					);	
		$this->query->exec($qb->getSQL());
	}

	/**
	 * Delete's the given question
	 *
	 * @param string $question
	 */
	public function deleteQuestion($question){
		if(empty($question)){
			throw new InvalidArgumentException("\$question have be not null string");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_PROFILE_KEYS'))
			->where($qb->expr()->equal(new Field("key"), $question));	
		$this->query->exec($qb->getSQL());
		
		if($this->query->affected() == 0){
			throw new OutOfRangeException("Can't delete non existent question");
		}
	}

	/**
	 * deletes an already created answer
	 *
	 * @param integer $profile_id
	 */
	public function deleteOptionById($profile_id){
		if(empty($profile_id) or !is_numeric($profile_id)){
			throw new InvalidArgumentException("\$profile have to be numeric id of the profile");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_PROFILE_KEYS'))
			->where($qb->expr()->equal(new Field("id"), $profile_id));	
		$this->query->exec($qb->getSQL());
	}

	/**
	 * edit an already created question
	 *
	 * @param $question
	 * @param $answers
	 * @param $type should be one of the public static properties TYPE_SINGLE or TYPE_MULTIPLE
	 */
	public function editQuestion($question, $answers, $type = 1){
		$this->deleteQuestion($question);
		$this->addQuestion($question, $answers, $type);
	}

	/**
	 * Edit an already created answer option
	 *
	 * @param integer $profile_id
	 * @param string $new_answer
	 * @param integer $sort_id
	 */
	public function editOptionById($profile_id, $new_answer, $sort_id = null){
		$additional_sql = '';
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_PROFILE_KEYS'))
			->set(new Field('value'), $new_answer);

		if(empty($profile_id) or !is_numeric($profile_id)){
			throw new InvalidArgumentException("\$profile have to be numeric id of the profile");
		}
		if(empty($new_answer)){
			throw new InvalidArgumentException("\$new_answer have be not null string");
		}
		if(!empty($sort_id) and !is_numeric($sort_id)){
			throw new InvalidArgumentException("\$sort_id have to have numeric value");
		}
		else{
			$qb->set(new Field('sort_id'), $sort_id);
			$additional_sql .= ", `sort_id`='$sort_id'";
		}
		$qb->where($qb->expr()->equal(new Field('id'), $profile_id));
		
		$this->query->exec($qb->getSQL());
	}
}

