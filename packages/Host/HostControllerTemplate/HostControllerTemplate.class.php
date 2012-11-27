<?
class HostControllerTemplate extends DbAccessor{
	
	const TBL_HOST_CONTROLLER_TEMPLATE = 'host_controller_template';
	
	public static function getControllerTemplateByHost(Host $host){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get("TBL_HOST_CONTROLLER_TEMPLATE"))
			->where($qb->expr()->equal(new Field('host_id'), $host->id));
		$sql->exec($qb->getSQL());
		
		return $sql->fetchRecord();
	}
}
?>