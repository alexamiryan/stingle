<?
class RewriteURL{
	protected $config;

	public function __construct(Config $config){
		$this->config = $config;
	}

	/**
	 * Parse URL
	 */
	public function parseURL(){
		if(!$this->config->enableUrlRewrite){
			return;
		}
		$this->config->sitePath = "/";
		
		// grab URL query string
		$uri = $_SERVER['REQUEST_URI'];
		$sitePath = $this->config->sitePath;
		
		// Strip first slash from uri
		if(substr($uri, 0, 1) == '/'){
			$uri = substr($uri, 1);
		}
		
		// Strip first slash from sitePath
		if(substr($sitePath, 0, 1) == '/'){
			$sitePath = substr($sitePath, 1);
		}
		
		// Strip sitePath from uri if exists
		if(strpos($uri, $sitePath) == 0){
			$uri = str_replace($sitePath, "", $uri);
		}
		
		// Start parsing uri parts
		$levels = $this->config->levels->toArray();
		$explodedUri = explode("/", $uri);
		
		$parsingGetParams = false;
		for($i = 0; $i < count($explodedUri); $i++){
			if(!empty($explodedUri[$i])){
				// Still parsing levels
				if(!$parsingGetParams){
					// If there is no colon in uri part it means that it is level name
					if(strpos($explodedUri[$i], ":") === false and isset($levels[$i])){
						$_GET[$levels[$i]] = $explodedUri[$i];
					}
					else{
						// If there is a colon this means we finished parsing 
						// levels and it's time for GET params
						$parsingGetParams = true;
					}
				}
				
				// Parsing GET params, finished levels
				if($parsingGetParams){
					// For uri part to be GET param there will be two strings devided by colon
					$getParam = explode(":", $explodedUri[$i]);
					if(count($getParam) == 2){
						$_GET[$getParam[0]] = $getParam[1];
					}
				}
			}
		}
	}

	public function glink($strUrl){
		if($strUrl != '/'){
			$strUrl = $this->config->sitePath . $strUrl;
		}
		
		self::ensureLastSlash($strUrl);
		return $strUrl;
	}
	
	public static function ensureLastSlash(&$strUrl){
		if(substr($strUrl, strlen($strUrl) - 1) != '/'){
			$strUrl .= '/';
		}
	}
	
}
?>
