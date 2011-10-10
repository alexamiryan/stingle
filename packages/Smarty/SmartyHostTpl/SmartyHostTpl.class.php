<?
class SmartyHostTpl extends DbAccessor{
	
	const TBL_HOST_TEMPLATES = 'host_templates';
	
	public static function getTemplateByHost(Host $host){
		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec("SELECT `template` FROM `".Tbl::get("TBL_HOST_TEMPLATES")."` WHERE `host_id`='{$host->id}'");
		
		return $sql->fetchField("template");
	}
}
?>