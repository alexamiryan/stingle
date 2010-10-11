<?
class RewriteURL{
	public $basename;

	protected $slashes;
	protected $config;
	protected $parts;
	protected $subfolders = array();

	protected static $module_name = "module";
	protected static $page_name = "page";

	public function __construct(Config $config){
		$this->config = $config;
	}

	/**
	 * Parse URL.
	 *
	 */
	public function parseURL($setParams = true){
		if($this->config->enable_url_rewrite == "OFF") return;
		/* grab URL query string */
		$uri = $_SERVER['REQUEST_URI'];
		/* slicing query string */
		$temp = array_slice(explode('/', $uri), 1);
		foreach($this->subfolders as $subfolder){
			$key = array_search($subfolder, $temp);
			unset($temp[$key]);
		}
		$this->parts = $temp;
		if($setParams === true){
			$this->setParams();
		}
	}

	/**
	 * Sets params for executing corresponding controler.
	 * Add params to global $_GET array
	 * By internal agreement first parameter is 'module' and second is 'page'
	 *
	 */
	protected function setParams(){
		$module_flag = false;
		$page_flag = 1;
		reset($this->parts);
		if(strpos(current($this->parts), ":") === false){
			$_GET[self::getSystemModuleName()] = current($this->parts);
			$_REQUEST[self::getSystemModuleName()] = current($this->parts);
			$module_flag = true;
		}
		if($module_flag){ // Check is module have been setted in request string
			next($this->parts);
			if(current($this->parts) and strpos(current($this->parts), ":") === false){ //Check is second parameter present and is it a page name
				$_GET[self::getSystemPageName()] = current($this->parts);
				$_REQUEST[self::getSystemPageName()] = current($this->parts);
				$page_flag = 2;
			}
			$params = array_slice($this->parts, $page_flag); //Remove module and page parameters from $this->parts
		}
		else{
			$params = $this->parts;
		}

		foreach($params as $param){ // Parse all other parameters as key:value
			list($k, $v) = explode(":", $param);
			if(!empty($k)){ // To work correct on url with "/" in the end and without it.
				$_GET[$k] = $v;
				$_REQUEST[$k] = $v;
			}
		}
	}

	public function setSystemNames($module_name, $page_name){
		if(empty($module_name)){
			throw new InvalidArgumentException("\$module_name have to be non empty string");
		}
		if(empty($page_name)){
			throw new InvalidArgumentException("\$page_name have to be non empty string");
		}
		self::$module_name = $module_name;
		self::$page_name = $page_name;
	}

	/**
	 * Add subfolders witch should be ignored
	 *
	 * @param array $folders
	 */
	public function setSubfolders($folders){
		$this->subfolders = array_merge($this->subfolders, $folders);
	}

	/**
	 * Return array of sliced query string
	 *
	 * @return array
	 */
	public function getParts(){
		return $this->parts;
	}

	/**
	 * Alter given link in default format to nice format
	 * @param string $strUrl
	 * @return string
	 */
	public static function alterLinkToNice($strUrl){
		// convert normal URL query string to clean URL
		$url = parse_url($strUrl);
		$strUrl = '';
		$vars = array();
		parse_str($url['query'], $vars);
		while(list($k, $v) = each($vars)){
			if(in_array($k, array(self::getSystemModuleName(), self::getSystemPageName()))){
				$strUrl .= $v . "/";
			}
			else{
				$strUrl .= $k . ":" . $v . "/";
			}
		}

		return $this->config->site_path . $strUrl;
	}

	/**
	 * Alter link given in nice format to default format
	 * @param $strUrl
	 * @return unknown_type
	 */
	public static function alterLinkToDefault($strUrl){
		$module_flag = false;
		$page_flag = 1;

		$config = ConfigManager::getConfig("RewriteURL");
		$return_string = $config->site_path . $config->handler_script . "?";

		$parts = explode("/", $strUrl);
		reset($parts);

		if(strpos(current($parts), ":") === false){
			$return_string .= self::getSystemModuleName() . "=" . current($parts);
			$module_flag = true;
		}
		if($module_flag){ // Check is module have been setted in request string
			next($parts);
			if(strpos(current($parts), ":") === false){
				$return_string .= "&" . self::getSystemPageName() . "=" . current($parts);
				$page_flag = 2;
			}
			$params = array_slice($parts, $page_flag);
		}
		else{
			$params = $parts;
		}

		foreach($params as $param){
			list($k, $v) = explode(":", $param);
			if(!empty($k) and !empty($v)){
				$return_string .= "&" . $k . "=" . $v;
			}
		}

		return $return_string;
	}

	public static function ensureSourceLastDelimiter($strUrl){
		$config = ConfigManager::getConfig('RewriteURL', 'RewriteURL');
		if($config->source_link_style == 'nice'){
			$delimiter = '/';
		}
		elseif($config->source_link_style == 'default'){
			$delimiter = '&';
		}
		if(!preg_match('/\\' . $delimiter . '$/', $strUrl)){
			$strUrl .= $delimiter;
		}
		return $strUrl;
	}

	public static function ensureOutputLastDelimiter($strUrl){
		$config = ConfigManager::getConfig('RewriteURL', 'RewriteURL');
		if($config->output_link_style == 'nice'){
			$delimiter = '/';
		}
		elseif($config->output_link_style == 'default'){
			$delimiter = '&';
		}
		if(!preg_match('/\\' . $delimiter . '$/', $strUrl)){
			$strUrl .= $delimiter;
		}
		$strUrl = preg_replace("/(\\$delimiter){2,}$/", $delimiter, $strUrl);
		
		return $strUrl;
	}

	public static function generateCleanBaseLink($module, $page, $default_module){
		$config = ConfigManager::getConfig('RewriteURL', 'RewriteURL');
		if($module == $page){
			if($module != $default_module){
				if($config->source_link_style == 'nice'){
					return "$module/";
				}
				elseif($config->source_link_style == 'default'){
					return RewriteURL::getSystemModuleName() . "=" . $module . "&";
				}
			}
		}
		else{
			if($config->source_link_style == 'nice'){
				return "$module/$page/";
			}
			elseif($config->source_link_style == 'default'){
				return RewriteURL::getSystemModuleName() . "=" . $module . "&" . RewriteURL::getSystemPageName() . "=" . $page . "&";
			}

		}
		return '';
	}

	public function glink($strUrl){
		if($this->config->source_link_style == 'nice'){
			if($this->config->output_link_style == 'nice'){
				$strUrl = $this->config->site_path . $strUrl;
			}
			elseif($this->config->output_link_style == 'default'){
				$strUrl = self::alterLinkToDefault($strUrl);
			}
		}
		elseif($this->config->source_link_style == 'default'){
			$strUrl = $this->config->handler_script . "?" . $strUrl;
			if($this->config->output_link_style == 'nice'){
				$strUrl = self::alterLinkToNice($strUrl);
			}
			if($this->config->output_link_style == 'default'){
				$strUrl = $this->config->site_path . $strUrl;
			}
		}
		$strUrl = self::ensureOutputLastDelimiter($strUrl);
		return $strUrl;
	}

	public static function getSystemModuleName(){
		return self::$module_name;
	}

	public static function getSystemPageName(){
		return self::$page_name;
	}

}
?>
