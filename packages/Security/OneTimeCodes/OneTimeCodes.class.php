<?
class OneTimeCodes extends DbAccessor {
	
	const TBL_ONE_TIME_CODES	= "security_one_time_codes";
	
	const CODE_MAX_LENGTH = 1024;
	private $config;
	
	public function __construct (Config $config, $dbInstanceKey = null) {
		parent::__construct($dbInstanceKey); 
		$this->config = $config;
	}
	
	/**
	 * 
	 * Generate code using given parameters
	 * @param array $paramsArray
	 */
	public function generate(OTCConfig $config = null){
		if($config === null){
			$config = new OTCConfig();
		}
		
		$paramsArray = $config->paramsArray;
		
		if(isset($paramsArray['r'])){
			throw new RuntimeException("Key 'r' is not allowed to be present in \$paramsArray. Please remove or rename it.");
		}
		
		$paramsArray['r'] = generateRandomString(12);
		$keyValArray = array();
		$keysUniqueCheckArray = array();
		foreach ($paramsArray as $key => $value){
			if(preg_match("/[:;]/", $key) or preg_match("/[:;]/", $value)){
				throw new RuntimeException("Invalid characters in \$paramsArray. No ; or : characters are allowed!");
			}
			if(in_array($key, $keysUniqueCheckArray)){
				throw new RuntimeException("Duplicate key '$key' in \$paramsArray. It's not allowed!");
			}
			array_push($keysUniqueCheckArray, $key);
			array_push($keyValArray, "$key:$value");
		}
		$stringToEncrypt = implode(";", $keyValArray);
		$encryptedString = AES256::encrypt($stringToEncrypt);
		
		if(strlen($encryptedString) > static::CODE_MAX_LENGTH){
			throw new RuntimeException("Resulting code is longer than allowed " . static::CODE_MAX_LENGTH . " characters!");
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_ONE_TIME_CODES')."` 
									(`code`, `multi`, `usage_limit`, `not_cleanable`, `valid_until`) 
									VALUES(	'$encryptedString', 
											'".($config->multiUse ? '1' : '0')."',
											".($config->usageLimit ? "'{$config->usageLimit}'" : "NULL" ).",
											'".($config->notCleanable ? '1' : '0')."',
											".($config->validityTime ? "FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()) + {$config->validityTime})" : 'NULL').")");
		
		return $encryptedString;
	}
	
	
	/**
	 * Validate given code using paramsArray
	 * @param string $code
	 * @param array $paramsArray
	 * @return boolean
	 */
	public function validate($code, $paramsArray = array()){
		if(empty($code)){
			throw new InvalidArgumentException("Empty \$code supplied for validation!");
		}
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_ONE_TIME_CODES')."` WHERE `code`='$code' and (`valid_until` IS NULL OR `valid_until`>=NOW())");
		
		if($this->query->countRecords() == 0){
			return false;
		}
		
		$dbRow = $this->query->fetchRecord();
		
		$paramsArrayFromCode = $this->getArrayFromCode($dbRow['code']);
		
		if($paramsArrayFromCode === false){
			return false;
		}
		
		$resultingArray = array_diff_assoc($paramsArray, $paramsArrayFromCode);
		
		if(count($resultingArray) != 0){
			return false;
		}
		
		if($dbRow['multi'] == '1'){
			if($dbRow['usage_limit'] > 0){
				if($dbRow['usage_count'] < $dbRow['usage_limit']){
					$this->query->exec("UPDATE `".Tbl::get('TBL_ONE_TIME_CODES')."` SET `usage_count`=`usage_count`+1 WHERE `id`='{$dbRow['id']}'");
				}
				else{
					$this->query->exec("DELETE FROM `".Tbl::get('TBL_ONE_TIME_CODES')."` WHERE `id`='{$dbRow['id']}'");
					return false;
				}
			}
		}
		else{
			$this->query->exec("DELETE FROM `".Tbl::get('TBL_ONE_TIME_CODES')."` WHERE `id`='{$dbRow['id']}'");
		}
		
		return true;
	}
	
	public function revokeCode($code){
		if(empty($code)){
			throw new InvalidArgumentException("Empty \$code supplied for revokation!");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_ONE_TIME_CODES')."` WHERE `code`='$code'");
		
		return ($this->query->affected() > 0 ? true : false);
	}
	
	public function getArrayFromCode($code){
		$decryptedString = AES256::decrypt($code);
		$keyValuePairs = explode(";", $decryptedString);
		
		$resultingArray = array();
		foreach($keyValuePairs as $keyVal){
			$keyValArr = explode(":", $keyVal);
			if(count($keyValArr) == 2){
				$resultingArray[$keyValArr[0]] = $keyValArr[1];
			}
		}
		
		if(count($resultingArray) ==0 or !isset($resultingArray['r'])){
			return false;
		}
		
		unset($resultingArray['r']);
		
		return $resultingArray; 
	}
	
	/**
	 * CleanUp codes from DB that are too old
	 */
	public function cleanUp(){
		$time = 60*60*24*$this->config->cleanUpTimeOut;
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_ONE_TIME_CODES')."` 
								WHERE 	
										(
											UNIX_TIMESTAMP(`issue_date`) < UNIX_TIMESTAMP(NOW()) - $time AND
											`not_cleanable` = 0 AND 
											`valid_until` IS NULL
										) 
										OR 
										(
											`valid_until` IS NOT NULL AND 
											`valid_until` < NOW()
										)");
		
		return $this->query->affected();
	}
}
?>