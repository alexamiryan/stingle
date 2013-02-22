<?php
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
		
		// grab URL query string
		$uri = $this->getStripedUri();
		
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
	
	/**
	 * Strip SITE_PATH from URI and return it.
	 */
	public function getStripedUri(){
		// grab URL query string
		$uri = trim($_SERVER['REQUEST_URI'], '/');
		$sitePath = $this->getSitePath();
		
		// Strip sitePath from uri if exists
		if(!empty($sitePath) and strpos($uri, $sitePath) == 0){
			$uri = trim(str_replace($sitePath, "", $uri), '/');
		}
		
		return $uri;
	}
	
	public function getSitePath(){
		return trim($this->config->sitePath, '/');
	}
	
	public static function ensureLastSlash(&$strUrl){
		if(substr($strUrl, strlen($strUrl) - 1) != '/'){
			$strUrl .= '/';
		}
	}
	
}