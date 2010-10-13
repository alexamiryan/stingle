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
		$this->query->exec("SELECT `profile_id` FROM `".Tbl::get('TBL_PROFILE_SAVE')."` WHERE `user_id`='{$this->userId}'", $cacheMinutes);
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
	
	/**
	 * Set user answers by their ids
	 *
	 * @param array $answers an array containing user's answers
	 */
	public function setAnswersByIds($answers){
		if(is_array($answers)){
			$this->query->exec("DELETE FROM `".Tbl::get('TBL_PROFILE_SAVE')."` WHERE `user_id`='{$this->userId}'");
			
			foreach($answers as $answer){
				if(is_numeric($answer)){
					$this->query->exec("INSERT INTO `".Tbl::get('TBL_PROFILE_SAVE')."` (`user_id`,`profile_id`) VALUES('{$this->userId}','$answer')");
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

?>