<?php

/**
 * Get Mysql's current datetime by selecting NOW()
 * 
 * @return string
 */
function getDBCurrentDateTime($isTimestamp = false){
	$sql = MySqlDbManager::getQueryObject();
	
	$qb = new QueryBuilder();
	if($isTimestamp){
		$qb->select(new Func("UNIX_TIMESTAMP", new Func("NOW"), 'now'));
	}
	else{
		$qb->select(new Func("NOW", null, 'now'));
	}
	
	return $sql->exec($qb->getSQL())->fetchField('now');
}