<?php
$config = ConfigManager::getConfig("Users", "Users")->AuxConfig;

$defaultGroups = $config->defaultGroups->toArray();
$query = MySqlDbManager::getQueryObject();
foreach ($defaultGroups as $group){
    $qb = new QueryBuilder();
    
    $qb->insert(Tbl::get('TBL_GROUPS', 'UserManager'))
        ->values(['name' => $group]);
    
    $query->exec($qb->getSQL());
}