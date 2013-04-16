<?php
	/**
	 * External User connection with local User Management
	 *
	 * @author Aram Gevorgyan
	 */
	class ExternalUserManager {
		
		/**
		 * Function is static. It's doing extraUser registration.
		 * return Own user id
		 * 
		 * @param ExternalUser object $extUser
		 * @param ExternalAuth $extObj
		 * 
		 * @return integer|boolean
		 */
		public static function registerExtUser(ExternalUser $extUser, ExternalAuth $extAuth) {
			$um = Reg::get(ConfigManager::getConfig("Users", "Users")->Objects->UserManager);
						
			$tepmOwnUser = new User();
						
			$randomPassword = generateRandomString(12);
			$username = static::findFreeRandomUsername($extAuth->getName());
			$userId = $um->createUser($username, $randomPassword, $tepmOwnUser);
			if($userId !== false) {
				$extAuth->addToExtMap($userId, $extUser);
			}
			return $userId;
		}
		
		/**
		 * Function get random username
		 * @param string $prefix is name of current external plugin
		 * @return string 
		 */
		private static function findFreeRandomUsername($prefix){
			$um = Reg::get(ConfigManager::getConfig("Users", "Users")->Objects->UserManager);
			$possibleUsername = $prefix . "_" . generateRandomString(6);
			if(!$um->isLoginExists($possibleUsername, 0)){
				return $possibleUsername; 
			}
			else{
				return static::findFreeRandomUsername($prefix);
			}
		}
	}
