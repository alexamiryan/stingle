<?php
class LoaderUserSessions extends Loader{
	protected function includes(){
		stingleInclude ('Filters/UserSessionFilter.class.php');
		stingleInclude ('Objects/UserSession.class.php');
		stingleInclude ('Managers/UserSessionsManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserSessionsManager');
	}
	
	protected function loadUserSessions(){
		$this->register(new UserSessionsManager($this->config->AuxConfig));
	}
	
	public function hookGetUserFromToken(){
		if($this->config->AuxConfig->registerUserObjectFromToken){
			$token = null;
			switch($this->config->AuxConfig->tokenPlace){
				case 'cookie':
					if(isset($_COOKIE[$this->config->AuxConfig->tokenName])){
						$token = $_COOKIE[$this->config->AuxConfig->tokenName];
					}
					break;
				case 'post':
					if(isset($_POST[$this->config->AuxConfig->tokenName])){
						$token = $_POST[$this->config->AuxConfig->tokenName];
					}
					break;
				case 'get':
					if(isset($_GET[$this->config->AuxConfig->tokenName])){
						$token = $_GET[$this->config->AuxConfig->tokenName];
					}
					break;
			}
			
			if(!empty($token)){
				$user = Reg::get($this->config->Objects->UserSessions)->getUserFromSession($token);
				if(is_a($user, "User")){
					Reg::register(ConfigManager::getConfig('Users', 'Users')->ObjectsIgnored->User, $user);
				}
			}
		}
	}
	
}
