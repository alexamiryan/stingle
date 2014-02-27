<?php
function getMyPermissionsHash(){
	$permissionsList = "";
	if(isAuthorized()){
		if(isset(Reg::get('usr')->perms) and !empty(Reg::get('usr')->perms)){
			if(is_array(Reg::get('usr')->perms->permissionsList)){
				foreach(Reg::get('usr')->perms->permissionsList as $perm){
					$permissionsList .= $perm->id . ':';
				}
			}
		}
	}
	return md5($permissionsList);
}

function getSmartyCacheId($targetId){
	return $targetId . "|" . getMyPermissionsHash();
}