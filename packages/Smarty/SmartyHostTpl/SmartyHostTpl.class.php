<?
class SmartyHostTpl extends DbAccessor{
	
	const TBL_HOST_TEMPLATES = 'host_templates';
	
	public static function getTemplateByHost(Host $host){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('template'))
			->from(Tbl::get("TBL_HOST_TEMPLATES"))
			->where($qb->expr()->equal(new Field('host_id'), $host->id));
		$sql->exec($qb->getSQL());
		
		return $sql->fetchField("template");
	}
}
?>