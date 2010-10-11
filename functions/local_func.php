<?
function construct_html_header(){
	global $smarty;
	
}

function get_mail_cover($header, $body, $email, $language){
	global $smarty;
	$smarty->assign("header_txt", $header);
	$smarty->assign("text", $body);
	$smarty->assign("opt_out_link", get_opt_out_footer($email, $language));
	$smarty->assign("receiver_lang", $lang_id);
	$html=$smarty->fetch("mails/mail.tpl");
	return $html;
}

function send_newsmail($user_id, $title, $text){
	global $um;
	global $site_url;
	global $site_name;
	
	global $from_mail;
	if(empty($user_id) or empty($title) or empty($text)){
		return false;
	}
	$user = $um->getObjectById($user_id);

	$send_html = get_mail_cover($title, $text, $user->email, new Language( $user->my_lang )) ;

	if(send_mail($user->email, $title, $send_html)){
		return true;
	}
	else{
		return false;
	}
}

/**
 *
 * Markes inactive online workers logged out
 *
 * @return int Number of loged out users
 */
function clear_unresponsable_users(){
	global $um, $sql, $userAuth;
	$number_of_logged_out_users = 0;
	$filter = new UsersFilter(false);
	$filter->setOnlineStatus(DatingClubUserManagement::STATE_ONLINE_ONLINE);
	$filter->setLastPing(time() - (4 * ONLINE_PING_INTERVAL/1000), Filter::MATCH_LESS);
	foreach ($um->getUsersList($filter) as $user){
		if($user->hasGroup(WORKER_USERS_GROUP_NAME) or $user->hasGroup(WORKER_GROUP_NAME)){
			$userAuth->insertToWorkersOnline($user);
			$sql->exec("insert into `worker_statistics` (`user`, `action`) values('{$user->getLogin()}', 'STAT_LOGGED_OUT')");
			write_log('debug_wa_ping_iniciated_logout', 'worker:' . $um->getPrimaryGroup($user->getId()) . ', user:' . $user->getLogin());
			$number_of_logged_out_users++;
		}
		$um->updateUserExtra($user->getId(), array('online'=>'0'));
		write_log("clear","User '{$user->getLogin()}' set offline as unresponsable user by shell script","Set Offline");
	}
	
	return $number_of_logged_out_users;
}

function site_online_ping(){
	global $um, $usr, $userAuth;
	if($usr->isAuthorized() and time()-$usr->last_ping >= ONLINE_PING_INTERVAL/1000 ){
		$now = time();
		
		$um->updateUserExtra($usr->getId(), array("last_ping" => $now));
		
		$usr->last_ping = $now;
		$userAuth->serializeUserObject();
	}
}

function is_logined(){
	global $error, $usr;
	if(!$usr->isAuthorized()){
		if(empty($_GET["ajax"])){
			$_SESSION['redirect_after_login']=$_SERVER["REQUEST_URI"];
			$error->add('ERR_YOU_HAVE_TO_LOGIN');
		}
		redirect(SITE_PATH);
	}
	return true;
}



function get_hours($seconds){
	$hours = floor($seconds/3600);
	$left_secs = $seconds - $hours * 3600;
	$minutes = floor($left_secs/60);
	$left_secs = $left_secs - $minutes*60;
	//return set_leading_zeros($hours) . ':' . set_leading_zeros($minutes) . ':' . set_leading_zeros($left_secs);
	return set_leading_zeros($hours) . ':' . set_leading_zeros($minutes);
}



function handle_reg_security(){
	global $sql, $error, $reg_max_count_in_minute;
	$sql->exec("delete from `reg_security` where UNIX_TIMESTAMP()-UNIX_TIMESTAMP(`time`)>120");
	$sql->exec("select count(*) as `count` from `reg_security` where `remote_addr`='{$_SERVER['REMOTE_ADDR']}' and `forwarded`='{$_SERVER['HTTP_X_FORWARDED_FOR']}' and UNIX_TIMESTAMP()-UNIX_TIMESTAMP(`time`)<=60");
	$reg_count=$sql->fetchField('count');
	if($reg_count>=$reg_max_count_in_minute){
		$error->add("TOO_MANY_REGS");
	}
	else{
		$sql->exec("insert into `reg_security` (`remote_addr`,`forwarded`) values('{$_SERVER['REMOTE_ADDR']}','{$_SERVER['HTTP_X_FORWARDED_FOR']}')");
	}
}

function handle_lang(){
	global $lm,$lcm, $usr, $lang, $domains2lng, $um, $host_ext, $userAuth;
	if(!empty($domains2lng[$host_ext]) and $lcm->languageExists($domains2lng[$host_ext])){
		$lm->setLang($domains2lng[$host_ext]);
	}
	
	// Update user's language to the current one if they don't match
	if($usr->my_lang != $lm->getCurrentLangId()) {
		$usr->my_lang = $lm->getCurrentLangId();
		
		$userAuth->serializeUserObject();
		$um->updateUserExtra($usr->getId(), array('my_lang'=>$usr->my_lang));
	}
	
	$lm->defineAllConsts();
	$lang=$lm->getCurrentLangName();
}



function get_opt_out_footer($email, $language){
	global  $site_url,  $db, $error, $lm;

	$optout_link=$site_url . "/" . "action:opt_out/email:" . base64_encode($email);

	return sprintf($lm->getValueOf("OPT_OUT_LINK", $language), $optout_link, $optout_link);
}

function write_log($name, $value, $action=''){
	global $sql;
	$sess_id=session_id();
	$ip=$_SERVER['REMOTE_ADDR'];
	$name=addslashes($name);
	$value=addslashes($value);
	if(!empty($action)){
		$action=addslashes($action);
	}
	if($sql->exec( "insert delayed into `mixed_log` (`session_id`,`name`,`value`,`ip`".(!empty($action) ? ",`action`" : '').")
					values('$sess_id','$name','$value','$ip'".(!empty($action) ? ",'$action'" : '').")")){
		return true;
	}
	return false;
}

/**
 *
 */
function guessCountry(){
	global $gi;
	global $domains2country_id;
	global $gps;
	global $host_ext;
	
	$countryId = 0;
	
	// Selecting using geoip
	if(($cName = geoip_country_name_by_addr($gi, $_SERVER['REMOTE_ADDR']))){
		$countryId = $gps->getIdByName(mysql_real_escape_string($cName));
	}
	// Guessing from host extension
	elseif(!empty($domains2country_id[$host_ext])){
		$countryId = $domains2country_id[$host_ext];
	}
	
	return $countryId;
}

/**
 *
 * @param array $alternateData
 */
function parseGpsFormData($alternateData = null, $parseCustoms = true){
	$data = array();
	
	$formData = $alternateData !== null ? $alternateData : $_POST;
	
	foreach($formData as $key => $value){
		if(substr($key, 0, strlen("type_")) == "type_"){
			$typeId = substr($key, strlen("type_"));
			$data[$typeId] = $value;
		}
		elseif($parseCustoms and substr($key, 0, strlen("custom_")) == "custom_"){
			$customId = substr($key, strlen("custom_"));
			$data["customs"][$customId] = $value;
		}
	}
	
	return $data;
}

/**
 * Saves user gps params when passed $userId and
 * returns an array of all params got from $_POST
 * or $alternativeData.
 * Function also finds missing fields and reports
 * through error reporting global instance: $error
 *
 * @param int $userId
 * @param array $alternativeData must be parsed by parseGpsFormData if
 *								if it is a $_POST like array
 * @example $alternativeData = array(
 * 		[5] => string(2) "59" // country
 *		[10] => string(4) "1165" // state
 *		["customs"] => array(2) { // custom fields
 *		   [2] => string(7) "MyCity"
 *		   [1] => string(4) "MyZipCode000"
 *		}
 * )
 */

function saveUserGps($userId = null, $alternativeData = null){
	global $gps;
	global $error;
	global $um;

	/**
	 * Field template path
	 *
	 * @var string $templatePath
	 */
	$data = $alternativeData!== null ? $alternativeData : parseGpsFormData();
	/**
	 * @var array $userData
	 */
	$userData = array(
		"countryId" => null,
		"leafId"	=> null,
		"customs"	=> array()
	);

	/**
	 * Setting initial leaf id to root
	 *
	 * @var int $currentLeafId
	 */
	$currentLeafId = Gps::ROOT_NODE;
	
	while(
		// Chacking if current leaf has children
		($childrenCount = $gps->getChildrenCount($currentLeafId)) |
		// Or has custom fields to show
		($customFields = $gps->fieldsToShow($currentLeafId))
	){
		
		// Reseting type value
		$type = null;
		
		if($childrenCount){
			// Current leaf children's type
			$type = $gps->getChildrenType($currentLeafId);
			
			$value = $data[$type['id']];
			/*if(empty($value)){
				$error->add('GPS_SELECT_VALUE', constant($gps->getTypeName($type['id'])));
				return;
			}*/
			
			if($type["id"] == 5){
				$userData["countryId"] = $value;
				if($userId !== null){
					$um->updateUserExtra($userId, array("country" => $value));
				}
			}
		}
		
		if(count($customFields)){
			foreach($customFields as $customId){
				if(empty($data["customs"][$customId])){
					$error->add('GPS_ENTER_CUSTOM', constant($gps->getFieldName($customId)));
					$errorAdded = true;
				}
			}
			if($errorAdded === true){
				return;
			}
			
			foreach($customFields as $customId){
				$userData["customs"][$customId] = $data["customs"][$customId];
				if($userId !== null){
					$gps->saveField($userId, $customId, $data["customs"][$customId]);
				}
			}
		}
		
		if(empty($value)){
			break;
		}
		else{
			$currentLeafId = $value;
		}
		unset($customFields);
		unset($value);
	}
	
	$userData["leafId"] = $currentLeafId;
	if($userId !== null){
		$um->updateUserExtra($userId, array("gps" => $currentLeafId));
	}
	return $userData;
}


function postLoginOperations(){
	global $usr, $info, $error;
	
	if(empty($usr->country)){
		$error->add('PLEASE_FILL_COUNTRY', SITE_PATH . "profile/edit_account");
	}
	if($usr->email_confirmed != 1){
		$info->add('INFO_YOU_HAVE_TO_CONFIRM', $usr->email, SITE_PATH . "action:resend_confirm_mail");
	}
	if(!is_profile_filled($usr->getId())){
		$info->add('PROFILE_ADVICE', SITE_PATH . 'profile/edit_profile');
	}
	
	if(!empty($_SESSION['redirect_after_login'])){
		redirect($_SESSION['redirect_after_login']);
		unset($_SESSION['redirect_after_login']);
	}
	else{
		redirect(SITE_PATH."dashboard");
	}
}

function doLogin($username, $password, $remember = false){
	global $userAuth, $error;
	try{
		$userAuth->doLogin($username, $password, $remember);
	}
	catch (RuntimeException $e){
		switch($e->getCode()){
			case YubikeyUserAuthorization::EXCEPTION_INCORRECT_LOGIN_PASSWORD:
			case YubikeyUserAuthorization::EXCEPTION_USER_NOT_IN_USERS_GROUP:
				$error->add('INC_LOGIN_PASS');
				break;
			case YubikeyUserAuthorization::EXCEPTION_ACCOUNT_DISABLED:
				$error->add('ACC_DISABLED', SITE_PATH . 'contact');
				break;
			case YubikeyUserAuthorization::EXCEPTION_INVALID_YUBIKEY:
				$_SESSION['need_yubikey'] = true;
				$_SESSION['tmp_username'] = $username;
				$_SESSION['tmp_password'] = $password;
				redirect(SITE_PATH . "login/yubikey");
				break;
		}
	}
}
?>