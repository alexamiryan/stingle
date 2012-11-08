<?
class UsersGps extends Gps
{
	const TBL_USERS_GPS = 'users_gps';

	public function fillUsersGps($userId, $leafId){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_USERS_GPS'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		
		$this->query->exec($qb->getSQL());
			
		$gpsTree = $this->getNodeTree($leafId);
		foreach($gpsTree as $treeNode){
			$qb = new QueryBuilder();
			$qb->insert(Tbl::get('TBL_USERS_GPS'))
				->values(array(
						'user_id' => $userId,
						'node_id' => $treeNode["node_id"]
						));
			
			$this->query->exec($qb->getSQL());
		}
	}
}
?>