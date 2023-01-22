<?php
$config = ConfigManager::getConfig("Users", "Users")->AuxConfig;

$defaultGroups = $config->defaultGroups->toArray();
$query = MySqlDbManager::getQueryObject();
foreach ($defaultGroups as $group){
    try{
        $qb = new QueryBuilder();
        
        $qb->insert(Tbl::get('TBL_GROUPS', 'UserManager'))
            ->values(['name' => $group]);
        
        $query->exec($qb->getSQL());
    }
    catch (Exception $e){
        HookManager::callHookSimple("DBLog", ['DBMigrateUsers', format_exception($e)]);
    }
}