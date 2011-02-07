<?
/**
 * Class does Authorization against facebook.
 * 
 * Class can connect to facebook, do authorization and get current user object. 
 * It implements ExternalAuth
 *  
 * @author Aram Gevorgyan
 */
class FacebookAuth extends DbAccessor implements ExternalAuth
{
	private $config;
	
	/**
	 * @var NAME of current social site 
	 */
	const NAME = 'facebook';
	
	/**
	 * @var TBL_EXT_AUTH of facebook maping with own users
	 */
	const TBL_EXT_AUTH = 'extauth_map_facebook';
	
	/**
	 * Constructor for FacebookAuth class,
	 * @param Config $config
	 */
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		$this->config = $config;
	}
	
	/**
	 * Help function
	 * If after redirect from Facebook there is no "code" parameter, 
	 * function again does redirect to facebook's oAuth system 
	 */
	private function redirectToDialog(){
		$dialogUrl = "http://www.facebook.com/dialog/oauth?client_id=" 
            . $this->config->appId . "&redirect_uri=" . urlencode($this->config->redirectUrl);	
        echo("<script> top.location.href='" . $dialogUrl . "'</script>");
	}
	
	/**
	 * Help function
	 * Function connects to facebook's "oAuth" protocol and gets access token
	 * @param string $code is code from facebook
	 * @return string
	 */
	private function getAccessToken($code) {
		$tokenUrl = "https://graph.facebook.com/oauth/access_token?client_id="
        		.  $this->config->appId 
        		. "&redirect_uri="  . urlencode($this->config->redirectUrl) 
        		. "&client_secret=" . $this->config->appSecret 
        		. "&code=" . $code;
    	return file_get_contents($tokenUrl);
	}
	
	/**
	 * Help function 
	 * Function gets facebook's User object with access token from "Facebook Graph" 
	 * @param string $accessToken it's access token
	 * @return FacebookObject
	 */
	private function getFBUserFromGraph($accessToken){
		
		$graphUrl = "https://graph.facebook.com/me?" . $accessToken;
    	$fbUser = json_decode(file_get_contents($graphUrl));
    	return $fbUser;
	}
	
	/**
	 * Help Function
	 * Function Set BirthDate with  Default date format and return it
	 * @param  $fbBirthDate birdthdate field from Facebook user object
	 * @return string
	 */
	private function setBirthDate($fbBirthDate){
		
		$utime = strtotime($fbBirthDate); 
		$birthdate = date(DEFAULT_DATE_FORMAT, $utime);
		return $birthdate;
	}
	
	/**
	 * Help Function
	 * Fill Others fields from facebook user object to External user object field(otherFields)
	 * @param Facebook User object $fbUser
	 * @return ArrayObject
	 */
	private function fillOtherFieldsFromFBUser($fbUser){
		$othersArray = array(); 
		foreach ($fbUser as $key => $field) {
			if($field != null) {
				$othersArray[$key] = $field;
			}
		}
		return 	$othersArray;
	}
	
	/**
	 * Help function
	 * Function Creates new ExternalUser object, 
	 * from facebook's user object fills all members and returns ExtraUser object
	 * @param "stdClass object" $fbUser It's Facebook's User object
	 * @return ExternalUser
	 */
	private function createExtraUser($fbUser){
		$extUser = new ExternalUser();
		if(!empty($fbUser->first_name)) {
			$extUser->firstName = $fbUser->first_name;
			$fbUser->first_name = null;
		}
		if(!empty($fbUser->last_name)){
			$extUser->lastName = $fbUser->last_name;
			$fbUser->last_name = null;
		}
		if(!empty($fbUser->email)){
			$extUser->email = $fbUser->email;
			$fbUser->email = null;
		}
		if(!empty($fbUser->birthday)){
			$extUser->birthdate = $this->setBirthDate($fbUser->birthday);
			$fbUser->birthday = null;
		}
		if(!empty($fbUser->id)){
			$extUser->id = $fbUser->id;
			$fbUser->id = null;
		}
		if(!empty($fbUser->gender)){
			switch ($fbUser->gender) {
				case 'male' : $extUser->sex = ExternalUser::SEX_MALE; break;
				case 'female': $extUser->sex = ExternalUser::SEX_FEMALE; break;	 
			}
			$fbUser->gender = null;
		}
		$extUser->otherFields = $this->fillOtherFieldsFromFBUser($fbUser);
		return $extUser;
	}
	
	/**
	 * @see ExternalAuth::getName()
	 */
	public function getName(){
		return static::NAME;
	}

	/**
	 * @see ExternalAuth::getExtUser()
	 */
	public function getExtUser(){
		$code = $_GET["code"];
		if(empty($code)) {
     		$this->redirectToDialog();   
    	}    	
    	$accessToken = $this->getAccessToken($code);
    	$fbUser = $this->getFBUserFromGraph($accessToken);
    	$extUser = $this->createExtraUser($fbUser);
		return $extUser;    	
	}
	
	/**
	 * @see ExternalAuth::setExtMap()
	 */
	public function addToExtMap($userId,ExternalUser $extUser){
		if(!is_numeric($userId)) {
			throw new InvalidArgumentException("User Id Is not numeric");
		}
		if(!is_numeric($extUser->id)) {
			throw new InvalidArgumentException("External Id Is not numeric");
		}
		$extUserId = $extUser->id;
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_EXT_AUTH')."` 
									(`user_id`,`ext_user_id`) VALUES
									('$userId','$extUserId')");
		return true;								
	}
	
	/**
	 * @see ExternalAuth::getLocalUserIDFromMap()
	 */
	public function getLocalUserIDFromMap(ExternalUser $extUser){
		if(!is_numeric($extUser->id)) {
			throw new Exception("id is not numeric");
		}
		$extUserId = $extUser->id;
		$this->query->exec("SELECT `user_id` 
							FROM `".Tbl::get('TBL_EXT_AUTH')."` 
							WHERE `ext_user_id`='$extUserId'
							LIMIT 1");
		
		return $this->query->fetchField('user_id');
	}
}

?>