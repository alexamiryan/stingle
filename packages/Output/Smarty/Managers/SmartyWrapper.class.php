<?php
class SmartyWrapper extends Smarty {

	const PRIMARY = 0;
	const SECONDARY = 1;
	
	/**
	 * Relative path of the module's wrappers
	 * @var string
	 */
	protected $mainTemplate = 'system/index.tpl';
	
	
	protected $wrappersDir = 'wrappers/';

	
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
	private $layoutPath = null;
	
	private $isLayoutSet = false;

	/**
	 * CSSs that should be added to the displayed page
	 * @var array
	 */
	protected $cssFiles = array ();

	/**
	 * JSs that should be added to the displayed page
	 * @var array
	 */
	protected $jsFiles = array ();

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
	 * Deny embedding site into frames
	 * @var boolean
	 */
	protected $denyEmbeddingSite = true;
	
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
	
	protected $urlCounterForClearCache = null;
	
	protected $cacheId = null;
	
	/**
	 * 
	 * Templates config
	 * @var Config
	 */
	private $templatesConfig;
	
	public $pagesPath = 'pages/';
	
	public function __construct(){
		parent::__construct();
		$this->muteExpectedErrors();
	}
	
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

		$this->loadConfig($config);

		$this->registerPlugin('modifier', 'filePath', array(&$this, 'getFilePathFromTemplate'));
		$this->registerPlugin('modifier', 'findFile', array(&$this, 'findFilePath'));
		
		$this->isInitialized = true;
	}

	/**
	 * Initializes Smarty using the options in $config
	 *
	 * @param array $config SmartyWrapper configuration
	 */
	private function loadConfig($config) {
		$this->setCacheDir($config->cacheDir);
		$this->setCompileDir($config->compileDir);
		$this->setTemplateDir($config->templateDir);
		
		$this->setCaching($config->caching);
		$this->setCacheLifetime($config->defaultCacheTime);
		$this->setCompileCheck($config->compileCheck);
		$this->setUseSubDirs(true);
		
		if($config->memcacheSupport){
			Reg::get('packageMgr')->usePlugin("Db", "Memcache");
			if(ConfigManager::getConfig("Db", "Memcache")->AuxConfig->enabled){
				$this->caching_type = 'memcache';
			}
		}

		$this->defaultRelativeTemplatesPath = $config->defaultRelativeTemplatesPath;
		$this->defaultRelativeTplPath = $config->defaultRelativeTplPath;
		
		$this->urlCounterForClearCache = $config->urlCounterForClearCache;
		
		// Set default template
		$this->templatesConfig = $config->templatesConfig;
		$this->templates = $this->templatesConfig->templates;
		$this->setTemplate($this->templatesConfig->defaultTemplateName);
		
		// Set default layout
		$this->setLayout ( $config->defaultLayout);
		// Reset isLayoutSet
		$this->isLayoutSet = false;

		// Add includes/smartyPlugins to plugin dirs
		$this->addPluginsDir($config->defaultPluginsDir);
		
		// Set error pages paths
		$this->error404Page = $config->error404Page;
		
		$this->denyEmbeddingSite = $config->denyEmbeddingSite;
	}

	/**
	 * Set template
	 * 
	 * @param string $template
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function setTemplate($template){
		if(!is_dir($this->getTemplateDir(0)."templates/".$template)){
			throw new InvalidArgumentException("Specified templates directory (".$this->getTemplateDir(0)."templates/".$template.") doesn't exist");
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
		$templatePathPrefix = $this->getTemplateDir(0) . $this->defaultRelativeTemplatesPath;
		if($withAbsolutePath){
			$returnTemplatePathPrefix = $this->getTemplateDir(0) . $this->defaultRelativeTemplatesPath;
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
	 * Find file that suits best for our needs.
	 * First tries to find it in the closest module, going up by the tree, 
	 * then tries to find in template's global path and finally using optional alternate path if given.
	 * If not suceeded repeats same search in parent template if there is any.
	 * 
	 *  @param string $fileName
	 *  @param string $alternatePath
	 *  @return string
	 */
	public function findFilePath($fileName, $alternatePath = null){
		$templatePathPrefix = $this->getTemplateDir(0) . $this->defaultRelativeTemplatesPath;
		$currentTemplate = $this->template;
		$nav = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->ObjectsIgnored->Nav);
		
		// while loop for iterating through templates hierarchy
		while(true){
			$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
			
			$pagesPath = $this->pagesPath;
			// Go up
			for($i = count($levels)-1; $i >= 1 ; $i--){
				if(isset($nav->{$levels[$i]}) and !empty($nav->{$levels[$i]})){
					$pagesPath = $this->pagesPath;
					// Build module path for current nav level
					for($j=0; $j<$i; $j++){
						$pagesPath .= $nav->{$levels[$j]} . '/';
					}
					
					// Check if there is file in this level
					if(file_exists($templatePathPrefix . $currentTemplate .'/'. $pagesPath . $fileName)){
						return $templatePathPrefix . $currentTemplate .'/'. $pagesPath . $fileName;
					}
				}
			}
			// Look in template in general
			if(file_exists($templatePathPrefix . $currentTemplate .'/'. $fileName)){
				return $templatePathPrefix . $currentTemplate .'/'. $fileName;
			}
			
			// Look in optional alternate lookup location
			if(!empty($alternatePath) and file_exists($templatePathPrefix . $currentTemplate .'/'. $alternatePath . $fileName)){
				return $templatePathPrefix . $currentTemplate .'/'. $alternatePath . $fileName;
			}
			
			// If there is parent template let's look there too
			if(isset($this->templates->$currentTemplate) and !empty($this->templates->$currentTemplate)){
				$currentTemplate = $this->templates->$currentTemplate;
			}
			else{
				// No parent template left, good bye
				break;
			}
		}
		throw new TemplateFileNotFoundException("Unable to find given filename ($fileName) in all available lookup locations!");
	}
	
	public function getChunkPath($fileName){
		return $this->findFilePath("chunks/" . $fileName);
	}
	
	/**
	 * @param string $fileName
	 * @param array $params
	 * @return string
	 */
	public function getChunk($fileName, $params = array()){
		foreach ($params as $key=>$value){
			$this->assign($key, $value);
		}
		return $this->fetch($this->getChunkPath($fileName));
	}

	/**
	 * Set the page layout.
	 * Is one of files located in /templates/[current_template]/layouts/ folder or in system/layouts/
	 *
	 * @param string $layout selected layout Example: general.tpl, axaj.tpl
	 */
	public function setLayout($layout, $override = false) {
		if(empty($layout)){
			throw new InvalidArgumentException("Layout is not specified");
		}
		
		if($this->isLayoutSet == true and $override == false){
			return;
		}
		
		if(file_exists($this->getFilePathFromTemplate('layouts/' . $layout . '.tpl', true))){
			$this->layoutPath = $this->getFilePathFromTemplate('layouts/' . $layout . '.tpl');
		}
		elseif(file_exists($this->getTemplateDir(0) . "system/layouts/" . $layout . '.tpl')){
			$this->layoutPath = "system/layouts/" . $layout . '.tpl';
		}
		else{
			throw new RuntimeException("Layout $layout doesn't exist");
		}
		
		$this->layoutName = $layout;
		
		$this->isLayoutSet = true;
	}
	
	public function getLayout(){
		return $this->layoutName;
	}
	
	protected function addCacheCounterToPath(&$path, $isSlash = false){
		if($this->urlCounterForClearCache != null){
			$path .= ($isSlash ? '/' : '?') . "cnt={$this->urlCounterForClearCache}";
		}
	}
	
	protected function getCssFilePath($fileName){
		$resultingFilePath = $fileName;
		if(strpos($fileName, "http://") === false and substr($fileName,0,1) != "/"){
			$resultingFilePath = SITE_PATH . $this->findFilePath('css/' . $fileName);
			if($resultingFilePath === false){
				throw new TemplateFileNotFoundException("CSS file '$fileName' not found.");
			}
			
			$this->addCacheCounterToPath($resultingFilePath);
		}
		return $resultingFilePath;
	}
	
	
	public function addPrimaryCss($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addCss($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::SECONDARY, $fromTop);
	}
	
	public function addPrimaryCssSmart($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::PRIMARY, $fromTop, true);
	}
	
	public function addCssSmart($fileName, $fromTop = false) {
		$this->addCssAtPos($fileName, static::SECONDARY, $fromTop, true);
	}
	
	/**
	 * Adds a CSS file to the header section of the page displayed.
	 * @param $fileName
	 */
	public function addCssAtPos($fileName, $position = null, $fromTop = false, $isSmart = false) {
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
	
		if(!isset($this->cssFiles[$position]) or !is_array($this->cssFiles[$position])){
			$this->cssFiles[$position] = array();
		}
		
		if($isSmart){
			$filePath = base64_encode($fileName);
			$this->addCacheCounterToPath($filePath, true);
		}
		else{
			$filePath = $this->getCssFilePath($fileName);
		}
		
		if(!empty($filePath) and $filePath != '/'){
			if($fromTop){
				array_splice($this->cssFiles[$position], 0, 0, array('path' => $filePath, 'isSmart'=>$isSmart));
			}
			else{
				array_push($this->cssFiles[$position], array('path' => $filePath, 'isSmart'=>$isSmart));
			}
		}
		else{
			throw new SmartyException("Can't find CSS file $fileName in current template or ony parent templates");
		}
	}
	
	/**
	 * Get correct path of CSS file
	 * @param unknown_type $fileName 
	 * @throws InvalidArgumentException
	 */
	public function getCssPath($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		return $this->getCssFilePath($fileName);
	}
	
	protected function getJsFilePath($fileName){
		$resultingFilePath = $fileName;
		if((strpos($fileName, "https://") === false and strpos($fileName, "http://") === false) and substr($fileName,0,1) != "/"){
			$resultingFilePath = SITE_PATH . $this->findFilePath('js/' . $fileName);
			if($resultingFilePath === false){
				throw new TemplateFileNotFoundException("JS file '$fileName' not found.");
			}
			
			$this->addCacheCounterToPath($resultingFilePath);
		}
		return $resultingFilePath;
	}
	
	
	public function addPrimaryJs($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addJs($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::SECONDARY, $fromTop);
	}
	
	public function addPrimaryJsSmart($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::PRIMARY, $fromTop, true);
	}
	
	public function addJsSmart($fileName, $fromTop = false) {
		$this->addJsAtPos($fileName, static::SECONDARY, $fromTop, true);
	}
	
	/**
	 * Adds a JS file to the header section of the page displayed.
	 * @param $fileName
	 */
	public function addJsAtPos($fileName, $position = null, $fromTop = false, $isSmart = false) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
		
		if(!isset($this->jsFiles[$position]) or !is_array($this->jsFiles[$position])){
			$this->jsFiles[$position] = array();
		}
		
		if($isSmart){
			$filePath = base64_encode($fileName);
			$this->addCacheCounterToPath($filePath, true);
		}
		else{
			$filePath = $this->getJsFilePath($fileName);
		}
		
		if(!empty($filePath) and $filePath != '/'){
			if($fromTop){
				array_splice($this->jsFiles[$position], 0, 0, array('path' => $filePath, 'isSmart'=>$isSmart));
			}
			else{
				array_push($this->jsFiles[$position], array('path' => $filePath, 'isSmart'=>$isSmart));
			}
		}
		else{
			throw new SmartyException("Can't find JS file $fileName in current template or ony parent templates");
		}
	}

	/**
	 * Get js file correct path
	 * @param string $fileName
	 * @throws InvalidArgumentException
	 */
	public function getJsPath($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		return $this->getJsFilePath($fileName);
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
		
		$result = $this->getFilePathFromTemplate($this->pagesPath . $path . ".tpl");
		
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

	protected function defaultAssingns(){
		
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
		$this->assign ( '__ViewDirPath', $this->getTemplateDir(0) );
		$this->assign ( '__PagesPath', $this->pagesPath );
	}
	
	protected function handlePagerNotFound(){
		header("HTTP/1.0 404 Not Found");
		$this->fileToDisplay = $this->getFilePathFromTemplate($this->pagesPath . $this->error404Page . ".tpl");
		
		if(empty($this->fileToDisplay)){
			$this->fileToDisplay = 'system/common/404.tpl';
		}
		$this->setPageTitle("404 Not Found");
		
		$this->removeWrapper();
	}
	
	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false){
		$this->defaultAssingns();
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
	}
	
	public function setCachingOn($isPerPage = true){
		if($isPerPage){
			$this->setCaching(self::CACHING_LIFETIME_SAVED);
		}
		else{
			$this->setCaching(self::CACHING_LIFETIME_CURRENT);
		}
	}
	
	public function setCachingOff(){
		$this->setCaching(self::CACHING_OFF);
	}
	
	public function setCacheTime($time){
		if(!is_int($time)){
			throw new InvalidArgumentException("\$time have to be integer");
		}
		
		$this->setCacheLifetime($time);
	}
	
	public function setCacheId($cacheId){
		$this->cacheId = $cacheId;
	}
	
	public function isPageCached(){
		if($this->cacheId !== null and parent::isCached($this->mainTemplate, $this->cacheId)){
			return true;
		}
		return false;
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
	public function output($return = false){
		if($this->denyEmbeddingSite){
			header("Content-Security-Policy: frame-ancestors 'none'");
			header("X-Frame-Options: DENY");
		}
		// Do not display anything if output is disabled
		if($this->isOutputDisabled){
			return;
		}
		
		$navConfig = ConfigManager::getConfig("SiteNavigation")->AuxConfig;
		$nav = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->ObjectsIgnored->Nav);
		
		if(isset($nav->{$navConfig->actionName}) and !empty($nav->{$navConfig->actionName})){
			return;
		}
		
		// Call template init hook if there is any
		$currentTemplate = $this->template;
		while(true){
			$hookName = 'initTemplate_' . $currentTemplate;
			if(function_exists($hookName)){
				// Do not continue if function returned true
				if(call_user_func($hookName) === true){
					break;
				}
			}
				
			// Check if current templete has parent and call panret's init function too
			if(isset($this->templates->$currentTemplate) and !empty($this->templates->$currentTemplate)){
				$currentTemplate = $this->templates->$currentTemplate;
			}
			else{
				break;
			}
		}
		
		// Find path that exists for tpls inclusion
		$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
		$this->includePath = $this->pagesPath;
		for($i = 0; $i < count($levels)-1; $i++){
			$level = $levels[$i];
			if(isset($nav->$level) and !empty($nav->$level)){
				$this->includePath .= $nav->$level . '/';
				if(isset($levels[$i+1]) and !is_dir($this->getFilePathFromTemplate($this->includePath . $nav->$levels[$i+1], true))){
					break;
				}
			}
		}
		
		$foundLevelsCount = $i;

		$this->fileToDisplay = $this->includePath . "{$nav->{$levels[$i+1]}}.tpl";
		
		if(empty($this->overridedFileToDisplay)){
			$this->fileToDisplay = $this->getFilePathFromTemplate($this->fileToDisplay);
		}
		else{
			$this->fileToDisplay = $this->overridedFileToDisplay;
		}
		
		// Check if page exists and if not show 404 error page
		$requiredLevelsCount = $nav->existentLevelsCount - 2;
		if(empty($this->fileToDisplay) or $foundLevelsCount < $requiredLevelsCount){
			if($return == false){
				$this->handlePagerNotFound();
			}
			else{
				throw new TemplateFileNotFoundException("Can't find matching template to display: " . $this->fileToDisplay);
			}
		}
		
		$this->defaultAssingns();
		// Check if wrapper is set and if yes include it
		if(!empty($this->wrapper)){
			$wrapperPath = $this->findFilePath($this->wrappersDir . $this->wrapper . ".tpl");
			if($wrapperPath === false){
				throw new TemplateFileNotFoundException("Wrapper($wrapperName) is not found. All wrappers should be located in module's \"{$this->wrappersDir}\" directory");
			}
			
			$this->assign ( 'modulePageTpl', $this->fileToDisplay);
			$this->assign ( '__modulePageTpl', $wrapperPath);
			$this->fileToDisplay = $wrapperPath;
		}
		else{
			$this->assign ( '__modulePageTpl', $this->fileToDisplay);
		}
		
		$this->assign ( '__layoutTpl', $this->layoutPath);
		
		//$return = true;
		// Finally display
		if($return){
			if($this->cacheId !== null){
				return parent::fetch($this->fileToDisplay, $this->cacheId);
			}
			else{
				return parent::fetch($this->fileToDisplay);
			}
		}
		else{
			if($this->cacheId !== null){
				parent::display ( $this->mainTemplate, $this->cacheId);
			}
			else{
				parent::display ( $this->mainTemplate );
			}
		}
	}
}
