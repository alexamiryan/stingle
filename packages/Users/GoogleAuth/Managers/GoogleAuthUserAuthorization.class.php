<?php
class GoogleAuthUserAuthorization extends DbAccessor{
	
	const TBL_GOOGLE_AUTH_MAP = "wum_google_auth_map";
	
	const STATUS_GOOGLE_AUTH_ENABLED = 1;
	const STATUS_GOOGLE_AUTH_DISABLED = 0;
	
	protected $googleAuth;
	protected $googleAuthManager;
	
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->googleAuth = new GoogleAuthenticator();
		$this->googleAuthManager = new GoogleAuthManager();
	}
	
	
	public function auth(User $user, $googleAuthOTP = null){
		$hookParams = array('user'=>$user, 'googleAuthOTP'=>$googleAuthOTP);
		HookManager::callHook("BeforeGoogleAuth", $hookParams);
		
		if($this->googleAuthManager->isEnabled($user->id)){
			if(empty($googleAuthOTP)){
				throw new GoogleAuthRequiredException("GoogleAuth code is required for authorization");
			}
			
			if(!$this->googleAuth->verifyCode($this->googleAuthManager->getSecret($user->id),$googleAuthOTP, 2)){
				throw new InvalidGoogleAuthException("GoogleAuth code Validation Failed");
			}
			
			HookManager::callHook("OnSuccessGoogleAuth", $hookParams);
			return true;
		}
		
		HookManager::callHook("OnNotActiveGoogleAuth", $hookParams);
		
		return false;
	}
}
