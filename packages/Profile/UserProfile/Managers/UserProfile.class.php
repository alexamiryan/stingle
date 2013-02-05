<?php
class UserProfile extends Profile{
	
	const TBL_PROFILE_SAVE = "users_profile";
	
	/**
	 * @var int
	 */
	private $userId;
	
	/**
	 * @var array
	 */
	private $profileAnswers = array();
	
	/**
	 * Class Constructor
	 *
	 * @param integer $user_id
	 */
	public function __construct($userId = 0, $cacheMinutes = 0, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		if(!is_numeric($userId)){
			throw new InvalidArgumentException("Received invalid user_id");
		}
		
		$this->userId = $userId;
		
		$this->initUserAnswers($cacheMinutes);
	}
	
	/**
	 * Loads user's answers into private $this->profileAnsers field
	 *
	 */
	private function initUserAnswers($cacheMinutes = 0){
		$qb = new QueryBuilder();
		$qb->select(new Field('profile_id'))
			->from(Tbl::get('TBL_PROFILE_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $this->userId));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		$this->profileAnswers = $this->query->fetchFields("profile_id");
	}
	
	/**
	 * Get all the answers the user has given sorted by the according questions.
	 * This function returns a list of questions with the user given answers in
	 * embeded in the question's array. If you want to get just a list of user's
	 * given answers use getOnlyAnsers()
	 *
	 *
	 * @param boolean $onlySelected if set to TRUE only answered options will be returned
	 *
	 * @return array ( question => ( answers => (id => answer_id, value => answer_value, is_selected = [0,1]), type => type_id) )
	 */
	public function getAnswers($onlySelected = false, $cacheMinutes = 0){
		$questions = $this->getQuestions($cacheMinutes);
		$returnArray = array();
		
		foreach($questions as $question => &$val){
			foreach($val["answers"] as $answerKey => &$answer){
				if(count($this->profileAnswers)){
					if(in_array($answer["id"], $this->profileAnswers)){
						$answer["is_selected"] = 1;
					}
					else{
						$answer["is_selected"] = 0;
						if($onlySelected) {
							unset($val["answers"][$answerKey]);
						}
					}
				}
				else{
					$answer["is_selected"] = 0;
					if($onlySelected) {
						unset($val["answers"][$answerKey]);
					}
				}
			}
		}
		
		return $questions;
	}
	
	public function getAnswersByQuestion($question, $onlySelected = true, $cacheMinutes = 0){
		$cuteAnswers = array();
		$extendedAnswers = $this->getAnswers($onlySelected, $cacheMinutes);
		foreach($extendedAnswers as $questionType => $answer) {
			if($question == $questionType && !empty($answer['answers'])){
				$cuteAnswers[$question] = $answer['answers'];
			}
		}
		return $cuteAnswers;
	}
	
	public function deleteAnswers($profileId){
		if(empty($profileId)){
			throw new InvalidArgumentException("Given Profile Id is empty");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_PROFILE_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $this->userId));

		if(is_array($profileId)){
			$qb->andWhere($qb->expr()->in(new Field('profile_id'), $profileId));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('profile_id'), $profileId));
		}	
			
		$this->query->exec($qb->getSQL());
	}
	
	public function addAnswer($profileId){
		if(empty($profileId)){
			throw new InvalidArgumentException("Given Profile Id is empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_PROFILE_SAVE'))
			->values(array("user_id" => $this->userId, "profile_id" => $profileId));
		
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Set user answers by their ids
	 *
	 * @param array $answers an array containing user's answers
	 */
	public function setAnswersByIds($answers){
		if(is_array($answers)){
			$qb = new QueryBuilder();
			$qb->delete(Tbl::get('TBL_PROFILE_SAVE'))
				->where($qb->expr()->equal(new Field("user_id"), $this->userId));	
			$this->query->exec($qb->getSQL());
			
			foreach($answers as $answer){
				if(is_numeric($answer)){
					$qb = new QueryBuilder();
					$qb->insert(Tbl::get('TBL_PROFILE_SAVE'))
						->values(array(
							"user_id" => $this->userId, 
							"profile_id" => $answer 
						)
					);	
					$this->query->exec($qb->getSQL());
				}
			}
			$this->initUserAnswers();
		}
		else{
			throw new UnexpectedValueException("\$answers have to array");
		}
	}
	
	public function getOnlyAnswers($cacheMinutes = 0) {
		$cuteAnswers = array();
		$extendedAnswers = $this->getAnswers(true, $cacheMinutes);
		foreach($extendedAnswers as $question => $answer) {
			$cuteAnswers[$question] = $answer['answers'];
		}
		
		return $cuteAnswers;
	}
	
	/**
	 * Return's the number of questions the user has given answers for
	 *
	 * @return integer
	 */
	public function getAnswerCount($cacheMinutes){
		$tmp = array();
		$total_answers_count = 0;
		foreach ($this->profileAnswers as $answer_id){
			$question = $this->getQuestionByProfileId($answer_id, $cacheMinutes);
			if(!in_array($question, $tmp)){
				$total_answers_count++;
				array_push($tmp, $question);
			}
		}
		return $total_answers_count;
	}
}

