<?php
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
	
	private $redirectUrl = null;
	
	/**
	 * @var NAME of current social site 
	 */
	const NAME = 'facebook';
	
	/**
	 * @var TBL_EXT_AUTH of facebook maping with own users
	 */
	const TBL_EXT_AUTH = 'extauth_map_facebook';
	
	/**
	 * 
	 * @var TBL_FB_USER_INFO name of information table of facebook user 
	 */
	const TBL_FB_USER_INFO = 'facebook_users_info';
	
	/**
	 * Constructor for FacebookAuth class,
	 * @param Config $config
	 */
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		$this->config = $config;
	}
	
	/**
	 * Function rewrite default redirect url and set new redirect url
	 * @param string $redirectUrl
	 */
	public function setRedirectUrl($redirectUrl) {
		$this->redirectUrl = $redirectUrl;
	}
	
	/**
	 * Help function
	 * If after redirect from Facebook there is no "code" parameter, 
	 * function again does redirect to facebook's oAuth system 
	 */
	private function redirectToDialog(){
		if($this->redirectUrl !== null) {
			$rediectUrl = $this->redirectUrl;
		} else {
			$rediectUrl = $this->config->redirectUrl;
		}
		$dialogUrl = "https://www.facebook.com/dialog/oauth?client_id=" 
            . $this->config->appId . "&redirect_uri=" . urlencode($rediectUrl);	
        echo("<script> top.location.href='" . $dialogUrl . "'</script>");
	}
	
	/**
	 * Help function
	 * Function connects to facebook's "oAuth" protocol and gets access token
	 * @param string $code is code from facebook
	 * @return string
	 */
	private function getAccessToken($code) {
		if($this->redirectUrl !== null) {
			$rediectUrl = $this->redirectUrl;
		} else {
			$rediectUrl = $this->config->redirectUrl;
		}
		$tokenUrl = "https://graph.facebook.com/oauth/access_token?client_id="
        		.  $this->config->appId 
        		. "&redirect_uri="  . urlencode($rediectUrl) 
        		. "&client_secret=" . $this->config->appSecret 
        		. "&code=" . $code;
        try{
    		return file_get_contents($tokenUrl);
        }
        catch(ErrorException $e){
        	return false;
        }
	}
	
	/**
	 * Help function 
	 * Function gets facebook's User object with access token from "Facebook Graph" 
	 * @param string $accessToken it's access token
	 * @return FacebookObject
	 */
	private function getFBUserFromGraph($accessToken){
		
		$graphUrl = "https://graph.facebook.com/me?fields=email,name,first_name,last_name,birthday,gender&" 
				. $accessToken;
		try{
    		$fbUser = json_decode(file_get_contents($graphUrl));
	 	}
        catch(ErrorException $e){
        	return false;
        }
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
		if(is_array($fbUser)){
			foreach ($fbUser as $key => $field) {
				if($field != null) {
					$othersArray[$key] = $field;
				}
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
	private function createExternalUser($fbUser){
		$extUser = new ExternalUser();
		if(!empty($fbUser->first_name)) {
			$extUser->firstName = addslashes($fbUser->first_name);
			$fbUser->first_name = null;
		}
		if(!empty($fbUser->last_name)){
			$extUser->lastName = addslashes($fbUser->last_name);
			$fbUser->last_name = null;
		}
		if(!empty($fbUser->email)){
			$extUser->email = addslashes($fbUser->email);
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
		$extUser->otherFields = $fbUser;
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
		$code = null;
		if(isset($_GET["code"])){
			$code = $_GET["code"];
		}
		if(empty($code)) {
     		$this->redirectToDialog();   
    	}
    	$accessToken = $this->getAccessToken($code);
    	$fbUser = $this->getFBUserFromGraph($accessToken);
    	$extUser = $this->createExternalUser($fbUser);
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
	
	/**
	 * @see ExternalAuth::getExternalUserId()
	 */
	public function getExternalUserId($userId){
		if(!is_numeric($userId)) {
			throw new Exception("User Id is not numeric");
		}
		$this->query->exec("SELECT `ext_user_id` 
							FROM `".Tbl::get('TBL_EXT_AUTH')."` 
							WHERE `user_id`='$userId'
							LIMIT 1");
		
		return $this->query->fetchField('ext_user_id');
	}
	
	/**
	 * @see ExternalAuth::deleteLocalUserFromMap()
	 */
	public function deleteUserIDFromMap($userId){
		if(!is_numeric($userId)) {
			throw new Exception("id is not numeric");
		}
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_EXT_AUTH')."` 
							WHERE `user_id`='$userId'
							LIMIT 1");
	}
	
	/**
	 * Function connects to Facebook and gets photo albums 
	 * @param string $accessToken is access token, by default It`s null
	 * @return ArrayObject
	 */
	public function getAlbums($accessToken = null) {
		$code = $_GET["code"];
		if(empty($code)) {
     		$this->redirectToDialog();   
    	}    	
    	$albumsArray = array();
    	if($accessToken === null) {
    		$accessToken = $this->getAccessToken($code);
    	}
    	$extAlbumUrl = "https://graph.facebook.com/me/albums?$accessToken&limit=100&offset=0";
    	try{
 			$extAlbum = json_decode(file_get_contents($extAlbumUrl));
	 	}
        catch(ErrorException $e){
        	return false;
        }
        
 		foreach($extAlbum->data as $fbAlbum) {
 			$albumObj = new FacebookPhotoAlbum();
 			$albumObj->id = $fbAlbum->id;
 			$albumObj->name = $fbAlbum->name;
 			$albumObj->photo = "https://graph.facebook.com/".$fbAlbum->id."/picture?$accessToken";
 			$albumObj->type = $fbAlbum->type;
 			$albumsArray[] = $albumObj;	
 		}	
 		return $albumsArray;
	}
	
	/**
	 * Function Connects to Facebook and get Profile pictures
	 * @return ArrayObject
	 */
	public function getProfilePhotos(){		
		$code = $_GET["code"];
		if(empty($code)) {
     		$this->redirectToDialog();   
    	}    	
    	$accessToken = $this->getAccessToken($code);
 		$extAlbum = $this->getAlbums($accessToken);
    	$filesArray = array ();
    	if(is_array($extAlbum) and count($extAlbum)){
	 		foreach ($extAlbum as $albumObj) {
	 			if($albumObj->type == 'profile') {
	 				$extPhotosUrl = "https://graph.facebook.com/".$albumObj->id."/photos?$accessToken";
	 				try{
	 					$extPhotosObject = json_decode(file_get_contents($extPhotosUrl));
			 		}
			        catch(ErrorException $e){
			        	return false;
			        }
	 				foreach ($extPhotosObject->data as $excObj) {
	 					if($excObj->source !== null) {
	 						$photoObj = new FacebookPhoto();
	 						$photoObj->id = $excObj->id;
	 						$photoObj->picture = $excObj->picture;
	 						$photoObj->source = $excObj->source;
	 						$photoObj->width = $excObj->width;
	 						$photoObj->height = $excObj->height;
	 						$filesArray[] = $photoObj;
	 					}
	 				}
	 			}
	 		}
    	}
 		return $filesArray;
	}
	
	/**
	 * Check if photo meets requirements for minimal size.
	 *
	 * @param FacebookPhoto $photoObj
	 * @param integer $largeSideMinSize
	 * @param integer $smallSideMinSize
	 * @return boolean 
	 */
	public function isSizeMeetRequirements(FacebookPhoto $photoObj, $largeSideMinSize, $smallSideMinSize){
		$width = $photoObj->width;
		$height = $photoObj->height;
		if($width >= $height){
			if($width >= $largeSideMinSize and $height >= $smallSideMinSize){
				return true;
			}
		}
		else{
			if($height >= $largeSideMinSize and $width >= $smallSideMinSize){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get External User information from info table. 
	 * @param integer $userId user ID
	 * @throws InvalidArgumentException
	 * @return ArrayObject
	 */
	public function getExtUserInfo($userId){
		if(!is_numeric($userId)) {
			throw new InvalidArgumentException("User Id Is not numeric");
		}
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_FB_USER_INFO')."` 
							WHERE `user_id`='$userId'
							LIMIT 1");
		return $this->query->fetchRecord();
	}
	
	/**
	 * Add to facebook info table External user information.
	 * @param integer $userId
	 * @param ExternalUser $extUser
	 * @throws InvalidArgumentException
	 */
	public function addExtUserInfo($userId, ExternalUser $extUser){
		if(!is_numeric($userId)) {
			throw new InvalidArgumentException("User Id Is not numeric");
		}
		if(!is_numeric($extUser->id)) {
			throw new InvalidArgumentException("External Id Is not numeric");
		}
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_FB_USER_INFO')."` 
									(`user_id`,`ext_user_id`, `name`) VALUES
									('$userId', '$extUser->id', '".(isset($extUser->otherFields->name) ? addslashes($extUser->otherFields->name) : "")."')");
	}
	
	/**
	 * delete all information from info table for current user
	 * @param integer $userId
	 * @throws InvalidArgumentException
	 */
	public function deleteExtUserInfo($userId) {
		if(!is_numeric($userId)) {
			throw new InvalidArgumentException("User Id Is not numeric");
		}
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_FB_USER_INFO')."` 
							WHERE `user_id`='$userId'
							LIMIT 1");
	}
	
	/**
	 * Function Updates local User Id from map table and user info table
	 * @param integer $userId current user Id
	 * @param ExternalUser $extUser External user object
	 * @throws InvalidArgumentException
	 */
	public function updateLocalUserId($userId, ExternalUser $extUser){
		if(!is_numeric($userId)) {
			throw new InvalidArgumentException("User Id Is not numeric");
		}
		if(!is_numeric($extUser->id)) {
			throw new InvalidArgumentException("External Id Is not numeric");
		}
		$this->query->exec(
							"UPDATE `".Tbl::get('TBL_EXT_AUTH')."` 
							 SET `user_id` = '{$userId}'
							 WHERE `ext_user_id` = {$extUser->id}");
		$this->query->exec(
							"UPDATE `".Tbl::get('TBL_FB_USER_INFO')."` 
							 SET `user_id` = '{$userId}'
							 WHERE `ext_user_id` = {$extUser->id}");
	}
}
