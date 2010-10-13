<?php
class UsersGps extends Gps
{
	const TBL_USERS_GPS = 'users_gps';

	public function fillUsersGps($userId, $leafId){
		$this->query->exec("delete from `".Tbl::get('TBL_USERS_GPS')."` where `user_id`='$userId'");
			
		$gpsTree = $this->getNodeTree($leafId);
		foreach($gpsTree as $treeNode){
			$this->query->exec("INSERT INTO `".Tbl::get('TBL_USERS_GPS')."` (`user_id`,`node_id`) VALUES('$userId','{$treeNode["node_id"]}')");
		}
	}
}
?>