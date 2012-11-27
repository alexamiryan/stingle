<?
class PageInfoManager extends PageInfo 
{
	public static function getRecord(Language $lang=null, Host $host=null, $module, $page ){		
		
		$lang_id = ($lang === null ? null : $lang->id);
		$host_id = ($lang === null ? null : $host->id);
		
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec(static::queryString($lang_id, $host_id, $module, $page));
		$pageInfo = $sql->fetchRecord();
		return $pageInfo;		
	}
	
	public static function getLanguageHosts(Language $lang){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'h'))
			->from(Tbl::get('TBL_PAGE_INFO', 'PageInfo'), 'pi')
			->leftJoin(Tbl::get('TBL_HOSTS', 'Host'), 'h', 
						$qb->expr()->equal(new Field('id', 'h'), new Field('host_id', 'pi')))
			->where($qb->expr()->equal(new Field('lang_id', 'pi'), $lang->id))
			->andWhere($qb->expr()->isNotNull(new Field('host_id', 'pi')))
			->groupBy(new Field('id', 'h'));
			
		$sql->exec($qb->getSQL());	
		$hosts_data = $sql->fetchRecords();
		foreach ($hosts_data as $host_data){
			$host = new Host();
			Host::setData($host_data, $host);
			$hosts[]=$host;
		}
		return $hosts;		
	}
	
	public static function getModules(Language $lang, Host $host){
		$modules = array();
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('module', 'pi'))
			->from(Tbl::get('TBL_PAGE_INFO', 'PageInfo'), 'pi')
			->where($qb->expr()->equal(new Field('lang_id', 'pi'), $lang->id))
			->andWhere($qb->expr()->equal(new Field('host_id', 'pi'), $host->id))
			->andWhere($qb->expr()->isNotNull(new Field('module', 'pi')))
			->groupBy(new Field('module', 'pi'));
			
		$sql->exec($qb->getSQL());	
		if($sql->countRecords()){	
			$modules = $sql->fetchRecords();
		}
		return $modules;		
	}
	
	public static function getModulePages(Language $lang, Host $host, $module){
		$pages = array();
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('page', 'pi'))
			->from(Tbl::get('TBL_PAGE_INFO', 'PageInfo'), 'pi')
			->where($qb->expr()->equal(new Field('lang_id', 'pi'), $lang->id))
			->andWhere($qb->expr()->equal(new Field('host_id', 'pi'), $host->id))
			->andWhere($qb->expr()->equal(new Field('module', 'pi'), $module))
			->andWhere($qb->expr()->isNotNull(new Field('page', 'pi')))
			->groupBy(new Field('page', 'pi'));
			
		$sql->exec($qb->getSQL());	
		if($sql->countRecords()){	
			$pages = $sql->fetchRecords();
		}
		return $pages;
	}
	
	public static function save(array $pageInfo, Language $lang=null, Host $host=null, $module=null, $page=null ){
		$sql = MySqlDbManager::getQueryObject();
				
		if($lang === null){
			if(($id = static::exists()) != false){
				$query =  static::updateQueryString($pageInfo, $id);
			}
			else{
				$query =  static::insertQueryString($pageInfo);
			}
		}
		elseif (($host === null) != false){
			if(($id = static::exists($lang->id)) != false){
				$query =  static::updateQueryString($pageInfo, $id);
			}
			else{
				$query = static::insertQueryString($pageInfo, $lang->id);
			}			
		}
		else{
			if(($id = static::exists($lang->id,$host->id, $module, $page)) != false){
				$query =  static::updateQueryString($pageInfo, $id);
			}
			else{
				$query = static::insertQueryString($pageInfo, $lang->id, $host->id, $module, $page);
			}
		}
		$sql->exec($query);
	}
	
	/**
	 * Check if record exists
	 *
	 * @param array $pageInfo
	 * @param Language $lang
	 * @param Host $host
	 * @param string $module
	 * @param string $page
	 * @return bool
	 */
	private static function exists($lang_id=null, $host_id=null, $module=null, $page=null){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();

		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_PAGE_INFO', 'PageInfo'));
		if($lang_id === null){
			$qb->andWhere($qb->expr()->isNull(new Field('lang_id')));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('lang_id'), $lang_id));
		}	
		if($host_id === null){
			$qb->andWhere($qb->expr()->isNull(new Field('host_id')));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('host_id'), $host_id));
		}
		if($module === null){
			$qb->andWhere($qb->expr()->isNull(new Field('module')));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('module'), $module));
		}
		if($page === null){
			$qb->andWhere($qb->expr()->isNull(new Field('page')));
		}
		else{
			$qb->andWhere($qb->expr()->equal(new Field('page'), $page));
		}	

		$sql->exec($qb->getSQL());
		if($sql->countRecords()){
			return  $sql->fetchField("id");
		}
		return false;
	}
	
	/**
	 * Insert query string generator
	 *
	 * @param array $pageInfo
	 * @param int $langId
	 * @param int $hostId
	 * @param sring $module
	 * @param string $page
	 * @return string
	 */
	private static function insertQueryString(array $pageInfo, $langId=null, $hostId=null, $module=null, $page=null){
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_PAGE_INFO', 'PageInfo'))
			->values(array(
						"lang_id" 			=> ($langId === null? new Literal('NULL') : $langId), 
						"host_id" 			=> ($hostId === null? new Literal('NULL') : $hostId), 
						"module"  			=> ($module === null? new Literal('NULL') : $module), 
						"page" 	  			=> ($page 	=== null? new Literal('NULL') : $page), 
						"title"   			=> $pageInfo['title'], 
						"meta_keywords" 	=> $pageInfo['keywords'], 
						"meta_description" 	=> $pageInfo['description']
						)
					);	
		return $qb->getSQL();
	}
	
	private static function updateQueryString(array $pageInfo, $id){
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_PAGE_INFO', 'PageInfo'))
			->set(new Field('title'), $pageInfo['title'])
			->set(new Field('meta_keywords'), $pageInfo['keywords'])
			->set(new Field('meta_description'), $pageInfo['description'])
			->where($qb->expr()->equal(new Field('id'), $id));
			
		return $qb->getSQL();		
	}
}
?>