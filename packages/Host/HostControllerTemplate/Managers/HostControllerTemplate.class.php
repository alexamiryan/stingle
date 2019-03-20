<?php

class HostControllerTemplate extends DbAccessor {

	const TBL_HOST_CONTROLLER_TEMPLATE = 'host_controller_template';

	public static function getControllerTemplateByHost(Host $host, $cacheMinutes = null) {
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get("TBL_HOST_CONTROLLER_TEMPLATE"))
			->where($qb->expr()->equal(new Field('host_id'), $host->id));
		$sql->exec($qb->getSQL(), $cacheMinutes);

		return $sql->fetchRecord();
	}

	public static function setControllerTemplateByHost(Host $host, $controller, $template) {
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();

		if (!empty($controller) or ! empty($template)) {
			$qb->insert(Tbl::get('TBL_HOST_CONTROLLER_TEMPLATE'))
				->values(array(
					'host_id' => $host->id,
					'controller' => $controller,
					'template' => $template
				))
				->onDuplicateKeyUpdate()
				->set(new Field('controller'), $controller)
				->set(new Field('template'), $template);
		}
		else {
			$qb->delete(Tbl::get('TBL_HOST_CONTROLLER_TEMPLATE'))
				->where($qb->expr()->equal(new Field('host_id'), $host->id));
		}
		$sql->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag("HostControllerTemplate");
		return $sql->affected();
	}

}
