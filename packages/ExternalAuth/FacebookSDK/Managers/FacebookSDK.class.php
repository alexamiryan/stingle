<?php
/**
 * Wrapper class for Facebook SDK.Class for authorization, for get Albums and for get profile photos.
 * 
 * Class can connect to facebook, do authorization and get current user object. Can Get User Photo Albums, and can get user profile pictures album 
 * 
 *  
 * @author Aram Gevorgyan
 */
class FacebookSDK extends DbAccessor implements ExternalAuth
{
	public $config;
	
	private $redirectUrl = null;
	
	private $fbSDK = null;
	
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
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		$this->config = $config;
		
		$this->fbSDK = new Facebook\Facebook([
			'app_id' => $this->config->appId,
			'app_secret' => $this->config->appSecret,
			'default_graph_version' => $this->config->appVersion,
		]);
	}

	/**
	 * Function for get Login Url using Facebook SDK getLoginUrl function
	 * @param array $permissions, Array off perrmisions to get facebook fields
	 * @return string
	 */
	public function GetLoginUrl($permissions = null){
		
		$helper = $this->fbSDK->getRedirectLoginHelper();
		if($permissions === null) {
			$permissions = ['email']; // optional
		}
		if($this->redirectUrl !== null) {
			$rediectUrl = $this->redirectUrl;
		} else {
			$rediectUrl = $this->config->redirectUrl;
		}
		return $helper->getLoginUrl($rediectUrl, $permissions);
	}
	
	/**
	 * Function rewrite default redirect url and set new redirect url
	 * @param string $redirectUrl
	 */
	public function setRedirectUrl($redirectUrl) {
		$this->redirectUrl = $redirectUrl;
	}
	
	/**
	 * @see ExternalAuth::getExtUser()
	 */
	public function getExtUser($redirectUrl = null){
		$accessToken = $this->getAccessToken($redirectUrl);
	
		$fbUser = $this->getFBUserFromGraph($accessToken);
		$extUser = $this->createExternalUser($fbUser);
		return $extUser;
	}
	
	/**
	 * 
	 * Function connects to facebook's "oAuth" protocol and gets access token
	 * @param string $code is code from facebook
	 * @return string
	 */
	private function getAccessToken($redirectUrl = null) {
		$helper = $this->fbSDK->getRedirectLoginHelper();
		$accessToken = $helper->getAccessToken($redirectUrl);
		
		if (isset($accessToken)) {
			return $accessToken;
		}
		return false;
	}
	
	/**
	 * Help function 
	 * Function gets facebook's User object with access token from "Facebook Graph" 
	 * @param string $accessToken it's access token
	 * @return FacebookObject
	 */
	private function getFBUserFromGraph($accessToken){
		
		// Sets the default fallback access token so we don't have to pass it to each request
		$this->fbSDK->setDefaultAccessToken($accessToken);
		$response = $this->fbSDK->get('/me?fields=email,name,first_name,last_name,birthday,gender,picture&');
		
		return $response->getDecodedBody();
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
	 * Help function
	 * Function Creates new ExternalUser object, 
	 * from facebook's user object fills all members and returns ExtraUser object
	 * @param "stdClass object" $fbUser It's Facebook's User object
	 * @return ExternalUser
	 */
	private function createExternalUser($fbUser){
		$extUser = new ExternalUser();
		if(!empty($fbUser['first_name'])) {
			$extUser->firstName = addslashes($fbUser['first_name']);
		}
		if(!empty($fbUser['last_name'])) {
			$extUser->lastName = addslashes($fbUser['last_name']);
		}
		if(!empty($fbUser['email'])){
			$extUser->email = addslashes($fbUser['email']);
		}
		if(!empty($fbUser['birthday'])){
			$extUser->birthdate = $this->setBirthDate($fbUser['birthday']);
		}
		if(!empty($fbUser['id'])){
			$extUser->id = $fbUser['id'];
		}
		if(!empty($fbUser['picture']['data']['url'])){
			$extUser->picture = $fbUser['picture']['data']['url'];
		}
		if(!empty($fbUser['gender'])){
			switch ($fbUser['gender']) {
				case 'male' : $extUser->sex = ExternalUser::SEX_MALE; break;
				case 'female': $extUser->sex = ExternalUser::SEX_FEMALE; break;	 
			}
		}
		if(!empty($fbUser['name']))	{
			$extUser->otherFields = array();
			$extUser->otherFields['name'] = $fbUser['name'];
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
		
    	$albumsArray = array();
    	if($accessToken === null) {
    		$accessToken = $this->getAccessToken();
    		$this->fbSDK->setDefaultAccessToken($accessToken);
    	}
    	$response = $this->fbSDK->get('/me/albums');
    	$responseAlbumsArray = $response->getDecodedBody();
    	
    	if(!empty($responseAlbumsArray['data'])){
	 		foreach($responseAlbumsArray['data'] as $fbAlbum) {
	 			$albumObj = new FacebookPhotoAlbum();
	 			$albumObj->id = $fbAlbum['id'];
	 			$albumObj->name = $fbAlbum['name'];
	 			if($fbAlbum['name'] == "Profile Pictures"){
	 				$albumObj->type = "profile";
	 			}
	 			$albumsArray[] = $albumObj;	
	 		}	
 			return $albumsArray;
    	}
    	return false;
	}
	
	/**
	 * Function Connects to Facebook and get Profile pictures
	 * @return ArrayObject
	 */
	public function getProfilePhotos(){		
		$accessToken = $this->getAccessToken();
		$this->fbSDK->setDefaultAccessToken($accessToken);
		$extAlbum = $this->getAlbums($accessToken);

		$filesArray = array ();
    	if(is_array($extAlbum) and count($extAlbum)){
	 		foreach ($extAlbum as $albumObj) {
	 			if($albumObj->type == 'profile') {
	 				$response = $this->fbSDK->get('/' . $albumObj->id .'/photos?fields=height,width,picture,source&limit=50');
	 				$decodedBody = $response->getDecodedBody();
	 				
	 				if(!empty($decodedBody['data'])){
		 				foreach ($decodedBody['data'] as $excArray) {
		 					if($excArray['id'] !== null) {			
		 						$photoObj = new FacebookPhoto();
		 						$photoObj->id 		= $excArray['id'];
		 						$photoObj->picture 	= $excArray['picture'];
		 						$photoObj->source 	= $excArray['source'];
		 						$photoObj->width 	= $excArray['width'];
		 						$photoObj->height 	= $excArray['height'];
		 						$filesArray[] = $photoObj;
		 					}
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
									(`user_id`,`ext_user_id`, `name`, `picture_url`) VALUES
									(	'$userId', 
										'$extUser->id', 
										'".(isset($extUser->otherFields['name']) ? addslashes($extUser->otherFields['name']) : "")."',
										'$extUser->picture'
									)");
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
