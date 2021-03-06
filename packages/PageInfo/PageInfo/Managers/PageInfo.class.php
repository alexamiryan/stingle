<?php
class PageInfo extends DbAccessor
{
	private $host; 		//Host object
	private $language; //Language object
	
	const TBL_PAGE_INFO = "site_pages_info";
	
	public  function __construct(Host $host, Language $language, $instanceName = null){
		parent::__construct($instanceName);

		$this->host = $host;
		$this->language = $language;
	}
	
		
	public function getInfo($module, $page){
		if(empty($module) or empty($page)){
			throw new InvalidArrayArgumentException("module or page can not be empty");
		}
		if(($info = $this->getExact($module, $page)) != false){
			return $info;
		}
		if(($info = $this->getModuleDefault($module)) != false){
			return $info;
		}
		if(($info = $this->getHostLangDefault()) != false){
			return $info;
		}
		if(($info = $this->getLangDefault()) != false){
			return $info;
		}
		if(($info = $this->getDefault()) != false){
			return $info;
		}
		return array("title"=>'',"meta_keywords"=>'',"meta_description"=>'');
	}
	
	private function getExact($module, $page, $cacheMinutes = null){
		$this->query->exec(static::queryString($this->language->id, $this->host->id, $module, $page), $cacheMinutes);
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getModuleDefault($module, $cacheMinutes = null){
		$this->query->exec(static::queryString($this->language->id, $this->host->id, $module), $cacheMinutes);
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getHostLangDefault($cacheMinutes = null){
		$this->query->exec(static::queryString($this->language->id, $this->host->id), $cacheMinutes);
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getLangDefault($cacheMinutes = null){
		$this->query->exec(static::queryString($this->language->id), $cacheMinutes);
		$data = $this->query->fetchRecord();
		return $data;
	}
	private function getDefault($cacheMinutes = null){
		$this->query->exec(static::queryString(), $cacheMinutes);
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	protected static function queryString($lang_id=null, $host_id=null, $module=null, $page=null, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('title'), new Field('meta_keywords'), new Field('meta_description'))
			->from(Tbl::get('TBL_PAGE_INFO'));
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
		
		return $qb->getSQL();
	}
}
