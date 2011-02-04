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
	 * @var NAME of  
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
	 * Help function
	 * Function Creates new ExternalUser object, 
	 * from facebook's user object fills all members and returns ExtraUser object
	 * @param "stdClass object" $fbUser It's Facebook's User object
	 * @return ExternalUser
	 */
	private function createExtraUser($fbUser){
		$extUser = new ExternalUser();
		if($fbUser->first_name) {
			$extUser->firstName = $fbUser->first_name;
		}
		if($fbUser->last_name){
			$extUser->lastName = $fbUser->last_name;
		}
		if($fbUser->email){
			$extUser->email = $fbUser->email;
		}
		if($fbUser->birthday){
			$extUser->birthdate = $fbUser->birthday;
		}
		if($fbUser->id){
			$extUser->id = $fbUser->id;
		}
		if($fbUser->gender){
			$extUser->sex = $fbUser->gender;
		}
		if($fbUser->timezone){
			$extUser->timezone = $fbUser->timezone;
		}
		if($fbUser->timezone){
			$extUser->timezone = $fbUser->timezone;
		}
		if($fbUser->hometown){
			$extUser->hometown = $fbUser->hometown->name;
		}
		if($fbUser->location){
			$extUser->location = $fbUser->location->name;
		}
		if($fbUser->updated_time){
			$extUser->updatedTime = $fbUser->updated_time;
		}
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
		if(!is_numeric($userId) || !is_numeric($extUser->extraUserId)) {
			throw new InvalidArgumentException("User Id or ExtUserId Is not numeric");
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