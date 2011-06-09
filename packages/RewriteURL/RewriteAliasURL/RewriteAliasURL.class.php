<?
class RewriteAliasURL extends RewriteURL{
	
	private $_aliasMap;
	
	protected $customAliasesDir = 'incs/customUrlAliases/';
	
	public function __construct(Config $config, $aliasMap = false){
		parent::__construct($config);
		
		$this->_aliasMap = $aliasMap;
	}
	
	public function parseAliases(){
		if($this->_aliasMap !== false and isset($_SERVER['REQUEST_URI'])){
			$uri = rawurldecode($_SERVER['REQUEST_URI']);
			$uri = $this->ensureSourceLastDelimiter($uri);
			foreach($this->_aliasMap as $url_alias){
				$uri = str_replace($url_alias["alias"], $url_alias["map"], $uri);
			}
			
			$_SERVER['REQUEST_ORIGINAL_URI'] = $_SERVER['REQUEST_URI']; 
			$_SERVER['REQUEST_URI'] = $uri;
		}
	}
	
	public function callParseCustomAliases(){
		if(isset($_SERVER['REQUEST_URI'])){
			$uri = rawurldecode($_SERVER['REQUEST_URI']);
			$uri = $this->ensureSourceLastDelimiter($uri);
			if(method_exists($this, 'parseCustomAliases')){
				$uri = $this->parseCustomAliases($uri);
				$_SERVER['REQUEST_URI'] = $uri;
			}
		}
	}
	
	public function addAliasToLink($stringlink){
		$linkWithAlias = $stringlink;
		foreach($this->_aliasMap as $url_alias){
			if(strpos($stringlink, $url_alias["map"]) !== false){
				$linkWithAlias = str_replace($url_alias["map"], $url_alias["alias"], $stringlink);
				continue;
			}
		}
		return $linkWithAlias;
	}
	
	public function glink($strUrl){
		$strUrl = parent::glink($strUrl);
		if($this->config->output_link_style == 'nice'){
			$strUrl = $this->addAliasToLink($strUrl);
		}
		
		return $strUrl;
	}
}
?>