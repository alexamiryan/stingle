<?
class SmartyWrapper extends Smarty {

	/**
	 * Relative path of the template modules
	 * @var string
	 */
	protected $modulesDir = 'modules/';

	/**
	 * Relative path of the module's wrappers
	 * @var string
	 */
	protected $wrappersDir = 'wrappers/';

	/**
	 * Module which gone be displayed
	 * @var string
	 */
	private $module;

	/**
	 * Page which gone be displayed
	 * @var string
	 */
	private $page;

	/**
	 * The selected page layout. One of located in /templates/layouts folder
	 */
	private $layout;

	/**
	 * CSSs that should be added to the displayed page
	 * @var array
	 */
	private $cssFiles = array ();

	/**
	 * JSs that should be added to the displayed page
	 * @var array
	 */
	private $jsFiles = array ();
	private $jsLibFiles = array ();

	/**
	 * Title of the page to be displayed
	 * @var string
	 */
	private $pageTitle;

	/**
	 * A prefix with which all page titles should be prefixed.
	 * @var string
	 */
	private $pageTitlePrefix;

	/**
	 * A postfix with which all page titles should be postfixed.
	 * @var string
	 */
	private $pageTitlePostfix;

	/**
	 * A delimiter between the page title prefix and the actual title
	 * @var string
	 */
	private $pageTitleDelimiter;

	/**
	 * Keywords of the page to be displayed
	 *
	 * @var string
	 */
	private $keywords;

	/**
	 * Description of the page to be displayed
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Meta Language to be used in current page
	 * @var string
	 */
	private $pageLanguage;

	/**
	 * Meta Language-Country to be used in current page
	 * @var string
	 */
	private $pageLanguageCountry;

	/**
	 * Encoding of the current page
	 * @var string
	 */
	private $pageEncoding;

	/**
	 * Any additional html tags to be
	 * used in page's HEAD section
	 * @var array
	 */
	private $CustomHeadTags = array();

	/**
	 * Wrapper filename. Should be in
	 * module's "wrappers" forlder
	 * @var string
	 */
	private $wrapper;

	/**
	 * a relative path to the root folder (usually just ../..)
	 * doesn't contain the trailing slash
	 * @var string
	 */

	private $rootPath;

	/**
	 * Current Host Extension
	 * @var string
	 */
	private $host; // Host Object

	/**
	 * Is module, page initialized or not
	 * @var bool
	 */
	private $isInitialized = false;

	/**
	 * Is output is disabled for entire smarty
	 * @var bool
	 */
	private $isOutputDisabled = false;
	
	/**
	 * Module name for error pages
	 * @var string
	 */
	private $errorsModule;
	
	/**
	 * Page name for 404 error page
	 * @var string
	 */
	private $error404Page;

	/**
	 * Public constructor
	 */
	public function __construct() {
		parent::Smarty();
	}

	public function initialize($module, $page, $config){
		if(empty($module) or empty($page)){
			throw new InvalidArgumentException("One or both of the arguments are empty");
		}
		if($this->isInitialized){
			throw new RuntimeException("Smarty is alredy initilized");
		}

		$this->module = $module;
		$this->page = $page;

		$this->loadConfig($config);

		$this->isInitialized = true;
	}

	/**
	 * Initializes Smarty using the options in $config
	 *
	 * @param array $config SmartyWrapper configuration
	 */
	private function loadConfig($config) {
		$this->cache_dir = $config->cache_dir;
		$this->compile_dir = $config->compile_dir;
		$this->template_dir = $config->template_dir;

		// Set default layout
		$this->setLayout ( $config->default_layout );

		// Add includes/smartyPlugins to plugin dirs
		array_push($this->plugins_dir, $config->default_plugins_dir);
		
		// Set error pages paths
		$this->errorsModule = $config->errors_module;
		$this->error404Page = $config->error_404_page;
	}

	/**
	 * Add additional plugins dir
	 * @param $pluginDir
	 */
	public function addPluginsDir($pluginDir) {
		if(empty($pluginDir)){
			throw new InvalidArgumentException("Plugin Dir is not specified");
		}
		array_push($this->plugins_dir, $pluginDir);
	}

	/**
	 * Set the page layout.
	 * Is one of files located in /templates/layouts folder
	 *
	 * @param string $layout selected layout Example: general.tpl, axaj.tpl
	 */
	public function setLayout($layout) {
		if(empty($layout)){
			throw new InvalidArgumentException("Layout is not specified");
		}

		$this->assign ( '__pageLayout', 'layouts/' . $layout . '.tpl' );
	}

	/**
	 * Adds a CSS file to the header section of the page displayed. Filename should be path relative to /res/css
	 * @param $fileName name of the file relative to /res/css
	 * @param $ieOnly this file will be loaded only if the client's browser is IE
	 */
	public function addCss($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}

		$this->cssFiles[] = $fileName;
	}

	/**
	 * Removes a CSS file from the header section of the page displayed. Filename should be path relative to /res/css
	 * @param $fileName name of the file relative to /res/css
	 * @param $ieOnly this file will be loaded only if the client's browser is IE
	 */
	public function removeCss($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}
		
		$key = array_search($fileName, $this->cssFiles);
		if($key !== false){
			unset($this->cssFiles[$key]);
		}
		else{
			throw new InvalidArgumentException("Can't remove CSS file, because it was not added");
		}
	}
	
	/**
	 * Adds a JS file to the header section of the page displayed. filenamed should be path relative to /res/js
	 * @param $fileName name of the file relative to /res/js
	 * @param $ieOnly this file will be loaded only if the client's browser is IE
	 */
	public function addJs($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}

		$this->jsFiles[] = $fileName;
	}
	
	public function addJsLib($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		$this->jsLibFiles[] = $fileName;
	}
	/**
	 * Removes a JS file from the header section of the page displayed. Filename should be path relative to /res/js
	 * @param $fileName name of the file relative to /res/css
	 */
	public function removeJs($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		
		$key = array_search($fileName, $this->jsFiles);
		if($key !== false){
			unset($this->jsFiles[$key]);
		}
		else{
			throw new InvalidArgumentException("Can't remove JS file, because it was not added");
		}
	}
	public function removeJsLib($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		$key = array_search($fileName, $this->jsLibFiles);
		if($key !== false){
			unset($this->jsLibFiles[$key]);
		}
		else{
			throw new InvalidArgumentException("Can't remove JS Lib file, because it was not added");
		}
	}

	/**
	 * Sets the title of the page to be displayed
	 * Should be called after invocations of setPageTitlePrefix and setPageTitlePostfix
	 * @param $title the new page title
	 */
	public function setPageTitle($title, $ignoreTitlePostfix = false){
		if(isset($this->pageTitlePostfix) && (!$ignoreTitlePostfix)) {
			$this->pageTitle = $this->pageTitlePrefix . $title . $this->pageTitleDelimiter . $this->pageTitlePostfix;
		}
		else {
			$this->pageTitle = $this->pageTitlePrefix . $title;
		}
	}

	/**
	 * Adds the specified $postfix to all page titles
	 * @param $postfix string Postfix for all page titles
	 * @param $delimiter string A string that will act as a delimiter between the prefix and postfix
	 */
	public function setPageTitlePostfix($postfix, $delimiter = ' - ') {
		$this->pageTitlePostfix = $postfix;
		$this->pageTitleDelimiter = $delimiter;
	}

	/**
	 * Adds the specified additional $prefix to page title
	 * @param $prefix string Prefix for all page titles
	 */
	public function setPageTitlePrefix($prefix) {
		$this->pageTitlePrefix = $prefix;
	}

	/**
	 * Sets the specified keywords to the page
	 * @param $keywords
	 */
	public function setPageKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
	 * Sets the specified description to the page
	 * @param $description
	 */
	public function setPageDescription($description) {
		$this->description = $description;
	}

	/**
	 * Sets the specified language to the page's meta tags
	 * @param $language
	 */
	public function setPageLanguage($language) {
		$this->pageLanguage = $language;
	}

	/**
	 * Sets the specified language-country to the page's meta tags
	 * @param $languageCountry
	 */
	public function setPageLanguageCountry($languageCountry) {
		$this->pageLanguageCountry = $languageCountry;
	}

	/**
	 * Sets the current page's encoding
	 * @param $encoding
	 */
	public function setPageEncoding($encoding) {
		$this->pageEncoding = $encoding;
	}

	/**
	 * Adds the specified custom html tag to the page's head section
	 * @param $customTag
	 */
	public function addCustomHeadTag($customTag) {
		$this->CustomHeadTags[] = $customTag;
	}

	/**
	 * Set alternate page tpl name
	 * @param $pageTplName string
	 */
	public function setPageTpl($pageTplName){
		if(empty($pageTplName)){
			throw new InvalidArgumentException("Page filename is not specified");
		}
		if(!file_exists($this->template_dir . $this->modulesDir . $this->module . "/" . $pageTplName . ".tpl")){
			throw new RuntimeException("Specified page is not found in current module");
		}
		$this->page = $pageTplName;
	}

	/**
	 * Set wrapper for non standard pages. Wrapper tpl file
	 * should be located in module's "wrappers" directory
	 * @param $wrapperName
	 */
	public function setWrapper($wrapperName){
		if(empty($wrapperName)){
			throw new InvalidArgumentException("Wrapper name is not specified");
		}

		$wrapperPath = $this->template_dir . $this->modulesDir . $this->module . "/" . $this->wrappersDir . $wrapperName . ".tpl";

		if(!file_exists($wrapperPath)){

			throw new RuntimeException("Wrapper($wrapperPath) is not found. All wrappers should be located in module's \"{$this->wrappersDir}\" directory");
		}

		$this->wrapper = $wrapperName;
	}
	
	/**
	 * Removes previously set wrapper
	 */
	public function removeWrapper(){
		$this->wrapper = null;
	}

	/**
	 * Disable output for smarty
	 */
	public function disableOutput(){
		$this->isOutputDisabled = true;
	}

	/**
	 * Display a module page. <b>The $tpl var should be relative to /templates/modules
	 * folder without a '/' at the begining.</b>
	 * Examples:
	 * home/home.tpl
	 * users/profile.tpl
	 *
	 * @param string $tpl
	 * @return SmartyWrapper
	 */
	public function display() {
		// Do not display anything if output is disabled
		if($this->isOutputDisabled){
			return;
		}
		
		// Check if page exists and if not show 404 error page
		if(!file_exists("{$this->template_dir}{$this->modulesDir}{$this->module}/{$this->page}.tpl")){
			header("HTTP/1.0 404 Not Found");
			$this->module = $this->errorsModule;
			$this->page = $this->error404Page;
			$this->removeWrapper();
		}
		
		// CSS & JS files
		$this->assign ( '__cssFiles', $this->cssFiles );
		$this->assign ( '__jsLibFiles', $this->jsLibFiles );
		$this->assign ( '__jsFiles', $this->jsFiles );

		// Other options
		$this->assign( '__pageTitle', $this->pageTitle );
		$this->assign( '__pageDescription', $this->description );
		$this->assign( '__pageKeywords', $this->keywords );

		$this->assign ( '__pageLanguage', $this->pageLanguage );
		$this->assign ( '__pageLanguageCountry', $this->pageLanguageCountry );

		$this->assign ( '__pageEncoding', $this->pageEncoding );

		$this->assign ( '__CustomHeadTags', $this->CustomHeadTags );
		
		// Check if wrapper is set and if yes include it
		if(!empty($this->wrapper)){
			$this->assign ( 'modulePageTpl', $this->modulesDir . $this->module . "/" . $this->page . ".tpl" );
			$this->assign ( '__modulePageTpl', $this->modulesDir . $this->module . "/" . $this->wrappersDir . $this->wrapper . ".tpl" );
		}
		else{
			$this->assign ( '__modulePageTpl', $this->modulesDir . $this->module . "/" . $this->page . ".tpl" );
		}
		
		// Finally display
		parent::display ( 'index.tpl' );
	}
}
?>