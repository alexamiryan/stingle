<?
class PageInfo extends DbAccessor
{
	private $host; 		//Host object
	private $language; //Language object
	
	const TBL_PAGE_INFO = "site_pages_info";
	
	public  function __construct(Host $host, Language $language, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);

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
	
	private function getExact($module, $page){
		$this->query->exec($this->queryString($this->language->id, $this->host->id, $module, $page));
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getModuleDefault($module){
		$this->query->exec($this->queryString($this->language->id, $this->host->id, $module));
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getHostLangDefault(){
		$this->query->exec($this->queryString($this->language->id, $this->host->id));
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	private function getLangDefault(){
		$this->query->exec($this->queryString($this->language->id));
		$data = $this->query->fetchRecord();
		return $data;
	}
	private function getDefault(){
		$this->query->exec($this->queryString());
		$data = $this->query->fetchRecord();
		return $data;
	}
	
	protected function queryString($lang_id=null, $host_id=null, $module=null, $page=null){
		$lang_where = "lang_id ". ($lang_id === null ? "IS NULL " : "=".$lang_id);
		$host_where = "host_id ". ($host_id === null ? "IS NULL " : "=".$host_id);
		$module_where = "module ". ($module === null ? "IS NULL " : "='".$module."'");
		$page_where = "page ". ($page === null ? "IS NULL " : "='".$page."'");
		
		$query = "SELECT `title`,	`meta_keywords`, `meta_description` FROM `".Tbl::get('TBL_PAGE_INFO')."` 
					WHERE  ".$lang_where."
					AND ".$host_where."
					AND ".$module_where."
					AND ".$page_where;
		
		return $query;
	}
}
?>