<?
class SmartyWrapper extends Smarty {

	const PRIMARY = 0;
	const SECONDARY = 1;
	
	/**
	 * Relative path of the module's wrappers
	 * @var string
	 */
	protected $wrappersDir = 'wrappers/';

	
	private $nav;
	private $includePath;
	private $fileToDisplay;
	private $overridedFileToDisplay;
	
	/**
	 * Template which will be used
	 * @var string
	 */
	private $template;
	
	/**
	 * 
	 * Template name from which extends current template
	 * @var string
	 */
	private $templates;
	
	/**
	 * The selected page layout name. One of located in /templates/layouts folder
	 */
	private $layoutName;
	
	/**
	 * The selected page layout path. One of located in /templates/layouts folder
	 */
	private $layoutPath;
	
	private $isLayoutSet = false;

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
	 * RelativePath for Template folders
	 * @var string
	 */
	private $defaultRelativeTemplatesPath;
	
	
	/**
	 * RelativePath for Tpl files
	 * @var string
	 */
	private $defaultRelativeTplPath;
	
	/**
	 * 
	 * Templates config
	 * @var Config
	 */
	private $templatesConfig;
	
	public $modulesPath = 'tpl/modules/';
	public $chunksPath = 'tpl/incs/chunks/';
	public $snippetsPath = 'tpl/incs/snippets/';
	
	
	public function isInitialized(){
		if($this->isInitialized){
			return true;
		}
		return false;
	}
	
	public function initialize($config){
		if($this->isInitialized){
			throw new RuntimeException("Smarty is already initilized");
		}
		$this->nav = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->ObjectsIgnored->Nav);

		$this->loadConfig($config);

		$this->registerPlugin('modifier', 'filePath', array(&$this, 'getFilePathFromTemplate'));
		
		$this->isInitialized = true;
	}

	/**
	 * Initializes Smarty using the options in $config
	 *
	 * @param array $config SmartyWrapper configuration
	 */
	private function loadConfig($config) {
		$this->cache_dir = $config->cacheDir;
		$this->compile_dir = $config->compileDir;
		$this->template_dir = $config->templateDir;

		$this->defaultRelativeTemplatesPath = $config->defaultRelativeTemplatesPath;
		$this->defaultRelativeTplPath = $config->defaultRelativeTplPath;
		
		// Set default template
		$this->templatesConfig = $config->templatesConfig;
		$this->templates = $this->templatesConfig->templates;
		$this->setTemplate($this->templatesConfig->defaultTemplateName);
		
		// Set default layout
		$this->setLayout ( $config->defaultLayout, true );

		// Add includes/smartyPlugins to plugin dirs
		$this->addPluginsDir($config->defaultPluginsDir);
		
		// Set error pages paths
		$this->error404Page = $config->error404Page;
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
	 * Set template
	 * 
	 * @param string $template
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function setTemplate($template){
		if(!is_dir($this->template_dir."templates/".$template)){
			throw new InvalidArgumentException("Specified templates directory (".$this->template_dir."templates/".$template.") doesn't exist");
		}
		
		if(!isset($this->templatesConfig->templates->$template)){
			throw new RuntimeException("Given template name $template is unknown");
		}
		
		$this->template = $template;
	}
	
	/**
	 * Get current template name
	 * @return string
	 */
	public function getTemplate(){
		return $this->template;
	}
	
	
	/**
	 * Returns file path from current template 
	 * otherwise if template is extended from 
	 * another template return path from parent 
	 * template.
	 * If function is used within file_exists pass 
	 * second parameter true 
	 * 
	 * @param string $filename
	 * @param boolean $withAbsolutePath
	 * @return string
	 * @throws RuntimeException
	 */
	public function getFilePathFromTemplate($filename, $withAbsolutePath = false){
		$templatePathPrefix = $this->template_dir . $this->defaultRelativeTemplatesPath;
		if($withAbsolutePath){
			$returnTemplatePathPrefix = $this->template_dir . $this->defaultRelativeTemplatesPath;
		}
		else{
			$returnTemplatePathPrefix = $this->defaultRelativeTemplatesPath;
		}

		$currentTemplate = $this->template;
		while(true){
			if(file_exists($templatePathPrefix . $currentTemplate . '/' . $filename)){
				return $returnTemplatePathPrefix . $currentTemplate . '/' . $filename;
			}
			elseif(isset($this->templates->$currentTemplate) and !empty($this->templates->$currentTemplate)){
				$currentTemplate = $this->templates->$currentTemplate;
			}
			else{
				return false;
			}
		}
	}

	/**
	 * Set the page layout.
	 * Is one of files located in /templates/[current_template]/layouts/ folder or in system/layouts/
	 *
	 * @param string $layout selected layout Example: general.tpl, axaj.tpl
	 */
	public function setLayout($layout, $isSystem = false) {
		if(empty($layout)){
			throw new InvalidArgumentException("Layout is not specified");
		}
		
		if(file_exists($this->getFilePathFromTemplate('layouts/' . $layout . '.tpl', true))){
			$this->layoutPath = $this->getFilePathFromTemplate('layouts/' . $layout . '.tpl');
		}
		elseif(file_exists($this->template_dir . "system/layouts/" . $layout . '.tpl')){
			$this->layoutPath = "system/layouts/" . $layout . '.tpl';
		}
		else{
			throw new RuntimeException("Layout $layout doesn't exist");
		}
		
		$this->layoutName = $layout;
		
		if(!$isSystem){
			$this->isLayoutSet = true;
		}
	}
	
	public function getLayout(){
		return $this->layoutName;
	}
	
	public function isLayoutSet(){
		return $this->isLayoutSet;
	}

	private function getCssFilePath($fileName){
		$resultingFileName = $fileName;
		if(strpos($fileName, "http://") === false and substr($fileName,0,1) != "/"){
			$resultingFileName = SITE_PATH . $this->getFilePathFromTemplate('css/' . $fileName, true);
			if($resultingFileName === false){
				throw new TemplateFileNotFoundException("CSS file '$fileName' not found.");
			}
		}
		return $resultingFileName;
	}
	
	
	public function addCss($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addSecondaryCss($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::SECONDARY, $fromTop);
	}
	
	/**
	 * Adds a CSS file to the header section of the page displayed.
	 * @param $fileName
	 */
	public function addCssAtPos($fileName, $position = null, $fromTop = false) {
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
	
		if(!isset($this->cssFiles[$position]) or !is_array($this->cssFiles[$position])){
			$this->cssFiles[$position] = array();
		}
	
		$filePath = $this->getCssFilePath($fileName);
		if(!empty($filePath) and $filePath != '/'){
			if(!$fromTop){
				array_push($this->cssFiles[$position], $filePath);
			}
			else{
				array_splice($this->cssFiles[$position], 0, 0, $filePath);
			}
		}
		else{
			throw new SmartyException("Can't find CSS file $fileName in current template or ony parent templates");
		}
	}
	
	private function getJsFilePath($fileName){
		$resultingFileName = $fileName;
		if(strpos($fileName, "http://") === false and substr($fileName,0,1) != "/"){
			$resultingFileName = SITE_PATH . $this->getFilePathFromTemplate('js/' . $fileName, true);
			if($resultingFileName === false){
				throw new TemplateFileNotFoundException("JS file '$fileName' not found.");
			}
		}
		return $resultingFileName;
	}
	
	
	public function addJs($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addSecondaryJs($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::SECONDARY, $fromTop);
	}
	
	/**
	 * Adds a JS file to the header section of the page displayed.
	 * @param $fileName
	 */
	public function addJsAtPos($fileName, $position = null, $fromTop = false) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
		
		if(!isset($this->jsFiles[$position]) or !is_array($this->jsFiles[$position])){
			$this->jsFiles[$position] = array();
		}
		
		$filePath = $this->getJsFilePath($fileName);
		if(!empty($filePath) and $filePath != '/'){
			if(!$fromTop){
				array_push($this->jsFiles[$position], $filePath);
			}
			else{
				array_splice($this->jsFiles[$position], 0, 0, $filePath);
			}
		}
		else{
			throw new SmartyException("Can't find JS file $fileName in current template or ony parent templates");
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
	 * Adds the specified custom html tag to the page's head section
	 * @param $customTag
	 */
	public function addCustomHeadTag($customTag) {
		$this->CustomHeadTags[] = $customTag;
	}

	/**
	 * Set alternate path to display
	 * @param $path string (example: 'my_profile/edit/biography')
	 */
	public function setPath($path){
		if(empty($path)){
			throw new InvalidArgumentException("Path is not specified");
		}
		
		$result = $this->getFilePathFromTemplate($this->modulesPath . $path . ".tpl");
		
		if($result === false){
			throw new RuntimeException("Specified path is invalid");
		}

		$this->overridedFileToDisplay = $result;
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

	private function defaultAssingns(){
		
		ksort($this->jsFiles);
		$jsFiles = array();
		foreach($this->jsFiles as $files){
			$jsFiles = array_merge($jsFiles, $files);
		}
		
		// CSS & JS files
		$this->assign ( '__jsFiles',  $jsFiles);
		
		ksort($this->cssFiles);
		$cssFiles = array();
		foreach($this->cssFiles as $files){
			$cssFiles = array_merge($cssFiles, $files);
		}
		$this->assign ( '__cssFiles', $cssFiles );
		
		// Other options
		$this->assign( '__pageTitle', $this->pageTitle );
		$this->assign( '__pageDescription', $this->description );
		$this->assign( '__pageKeywords', $this->keywords );
		
		$this->assign ( '__CustomHeadTags', $this->CustomHeadTags );
		
		// Template Paths
		$this->assign ( '__ViewDirPath', $this->template_dir );
		$this->assign ( '__ModulesPath', $this->modulesPath );
		$this->assign ( '__ChunksPath', $this->chunksPath );
		$this->assign ( '__SnippetsPath', $this->snippetsPath );
	}
	
	public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false){
		$this->defaultAssingns();
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display);
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
	public function output(){
		// Do not display anything if output is disabled
		if($this->isOutputDisabled){
			return;
		}
		
		// Call template init hook if there is any
		$hookFunctionName = 'initTemplate_' . $this->template;
		if(function_exists($hookFunctionName)){
			$hookName = 'initTemplate_' . $this->template;
			$templateHook = new Hook($hookName, $hookFunctionName);
			HookManager::registerHook($templateHook);
				
			HookManager::callHook($hookName);
		}
		
		// Call layout init hook if there is any
		$hookFunctionName = 'initLayout_' . $this->layoutName;
		if(function_exists($hookFunctionName)){
			$hookName = 'hookInitLayout_' . $this->layoutName;
			$layoutHook = new Hook($hookName, $hookFunctionName);
			HookManager::registerHook($layoutHook);
			
			HookManager::callHook($hookName);
		}
		
		// Find path that exists for tpls inclusion
		$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
		$this->includePath = $this->modulesPath;
		for($i = 0; $i < count($levels)-1; $i++){
			$level = $levels[$i];
			if(isset($this->nav->$level) and !empty($this->nav->$level)){
				$this->includePath .= $this->nav->$level . '/';
				if(isset($levels[$i+1]) and !is_dir($this->getFilePathFromTemplate($this->includePath . $this->nav->$levels[$i+1], true))){
					break;
				}
			}
		}
		
		$this->fileToDisplay = $this->includePath . "{$this->nav->{$levels[$i+1]}}.tpl";
		
		if(empty($this->overridedFileToDisplay)){
			$this->fileToDisplay = $this->getFilePathFromTemplate($this->fileToDisplay);
		}
		else{
			$this->fileToDisplay = $this->overridedFileToDisplay;
		}
		
		// Check if page exists and if not show 404 error page
		if(empty($this->fileToDisplay)){
			header("HTTP/1.0 404 Not Found");
			$this->fileToDisplay = $this->getFilePathFromTemplate($this->modulesPath . $this->error404Page . ".tpl");
			$this->removeWrapper();
		}
		
		$this->defaultAssingns();
		
		// Check if wrapper is set and if yes include it
		if(!empty($this->wrapper)){
			$wrapperPath = $this->getFilePathFromTemplate($this->includePath . $this->wrappersDir . $this->wrapper . ".tpl", true);
			if($wrapperPath === false){
				throw new TemplateFileNotFoundException("Wrapper($wrapperName) is not found. All wrappers should be located in module's \"{$this->wrappersDir}\" directory");
			}
			
			$this->assign ( 'modulePageTpl', $this->fileToDisplay);
			$this->assign ( '__modulePageTpl', $this->getFilePathFromTemplate($this->includePath . $this->wrappersDir . $this->wrapper . ".tpl" ));
		}
		else{
			$this->assign ( '__modulePageTpl', $this->fileToDisplay);
		}
		
		// Finally display
		parent::display ( $this->layoutPath );
	}
}
?>