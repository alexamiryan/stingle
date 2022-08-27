<?php

class MigrationsManager {
    
    const TBL_MIGRATIONS = "db_migrations";
    const SQL_FOLDER_NAME = 'SQL';
    
    
    
    public static function runMigrationsIfAny(string $packageName, string $pluginName){
        //Reg::get('packageMgr')->usePlugin("Logger", "DBLogger");
        $pluginConfig = ConfigManager::getConfig($packageName, $pluginName);
        $pluginId = $packageName . '-' . $pluginName;
        $query = MySqlDbManager::getQueryObject();
        
        if(isset($pluginConfig->Tables)){
            $pluginSqlDir = Reg::get('packageMgr')->getPluginPath($packageName, $pluginName) . self::SQL_FOLDER_NAME . DIRECTORY_SEPARATOR;
            foreach ($pluginConfig->Tables->toArray() as $tableName => $intendedVersion){
                $tableVersion = self::getTableVersionFromDb($packageName, $pluginName, $tableName);
                if($intendedVersion > $tableVersion){
                    for($i=$tableVersion+1; $i<=$intendedVersion; $i++){
                        $logMsg = ['DBMigrate', "Migrating table $tableName from version ". ($i-1) ." to $i"];
                        HookManager::callHook("DBLog", $logMsg);
                        
                        // Execute migration
                        $query->startTransaction();
    
                        $tableSQLFile = $pluginSqlDir . $i . DIRECTORY_SEPARATOR . $tableName . '.sql';
                        if(!file_exists($tableSQLFile)){
                            throw new RuntimeException("Failed to run migration for plugin $packageName-$pluginName for table $tableName version $i. File $tableSQLFile not found.");
                        }
                        
                        try {
                            $query->executeSQLFile($tableSQLFile);
                        }
                        catch (MySqlException $e){
                            throw new RuntimeException("Failed to run migration for plugin $packageName-$pluginName for table $tableName version $i. Error in SQL syntax.\n\n" . $e->getMessage());
                        }
                        
                        if (!$query->commit()) {
                            $query->rollBack();
                        }
    
                        // Post migration script
                        $postMigrateFile = $pluginSqlDir . $i . DIRECTORY_SEPARATOR . 'postMigrate.inc.php';
                        if(file_exists($postMigrateFile)){
                            require_once $postMigrateFile;
                        }
                        
                        // Insert into db-Migrations that we have excuted the migration
                        $qb = new QueryBuilder();
    
                        $insertArr = array(
                            'plugin_name' => $pluginId,
                            'table_name' => $tableName,
                            'version' => $i
                        );
    
                        $qb->insert(Tbl::get("TBL_MIGRATIONS"))
                            ->values($insertArr)
                            ->onDuplicateKeyUpdate()
                            ->set(new Field('version'), $i)
                            ->set(new Field('plugin_name'), $pluginId);
                        $query->exec($qb->getSQL());
    
                        $logMsg = ['DBMigrate', "Migrated table $tableName from version ". ($i-1) ." to $i"];
                        HookManager::callHook("DBLog", $logMsg);
                    }
                }
            }
        }
    }
    
    /**
     * @param string $packageName
     * @param string $pluginName
     * @param string $tableName
     * @return int (0 means that we haven't seen that table before, and we need to initialize it)
     * @throws MySqlException
     */
    private static function getTableVersionFromDb(string $packageName, string $pluginName, string $tableName) : int{
        $query = MySqlDbManager::getQueryObject();
        
        $qb = new QueryBuilder();
        $pluginId = $packageName . '-' . $pluginName;
        $qb->select(array(new Field('version')))
            ->from(Tbl::get('TBL_MIGRATIONS'))
            ->where($qb->expr()->equal(new Field('table_name'), $tableName ));
    
        try {
            $query->exec($qb->getSQL());
    
            if ($query->countRecords() == 1) {
                return $query->fetchField('version');
            }
        }
        catch (MySqlException $e){}
        return 0;
    }

}
