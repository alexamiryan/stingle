<?
class FacebookAuth implements ExternalAuth
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	private function redirectToDialog(){

		$dialogUrl = "http://www.facebook.com/dialog/oauth?client_id=" 
            . $this->config->appId . "&redirect_uri=" . urlencode($this->config->redirectUrl);	
        echo("<script> top.location.href='" . $dialogUrl . "'</script>");
	}
	
	private function getAccessToken($code) {
		$tokenUrl = "https://graph.facebook.com/oauth/access_token?client_id="
        		.  $this->config->appId 
        		. "&redirect_uri="  . urlencode($this->config->redirectUrl) 
        		. "&client_secret=" . $this->config->appSecret 
        		. "&code=" . $code;
    	return file_get_contents($tokenUrl);
	}
	
	private function getFBUserFromGraph($accessToken){
		
		$graphUrl = "https://graph.facebook.com/me?" . $accessToken;
    	$fbUser = json_decode(file_get_contents($graphUrl));
    	return $fbUser;
	}
	
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
		return $extUser;
	}
		
	public function getExtUser(/*polimorf*/){
		$count = func_num_args();
		if($count == 0) {
			throw new Exception("For Auth in facebook you have to give code paramenter");
		}
		$args = func_get_args();
		$code = $args[0];
		if(empty($code)) {
     		$this->redirectToDialog();   
    	}    	
    	$accessToken = $this->getAccessToken($code);
    	$fbUser = $this->getFBUserFromGraph($accessToken);
    	$extUser = $this->createExtraUser($fbUser);
		return $extUser;    	
	}
}

?>