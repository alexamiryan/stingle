<?php
class MinifySmartyWrapper extends SmartyWrapper {

	const TYPE_JS = 0;
	const TYPE_CSS = 1;
	const TYPE_JS_SMART = 2;
	const TYPE_CSS_SMART = 3;
	
	protected $jsFiles = array ();
	protected $cssFiles = array ();
	protected $customJsFiles = array ();
	protected $customCssFiles = array ();
	
	public function addPrimaryJsSmart($fileName, $fromTop = false) {
		$this->addJsAtPosSmart($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addJsSmart($fileName, $fromTop = false) {
		$this->addJsAtPosSmart($fileName, static::SECONDARY, $fromTop);
	}
	
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
	
		$item = array('type'=> self::TYPE_JS, 'path'=>$this->getCurrentPagePath(), 'src' => $fileName);
		
	
		if($fromTop){
			array_splice($this->jsFiles[$position], 0, 0, $item);
		}
		else{
			array_push($this->jsFiles[$position], $item);
		}
	}
	
	public function addJsAtPosSmart($fileName, $position = null, $fromTop = false){
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
		
		if(!isset($this->jsFiles[$position]) or !is_array($this->jsFiles[$position])){
			$this->jsFiles[$position] = array();
		}

		$item = array('type'=> self::TYPE_JS_SMART, 'path'=>$this->getCurrentPagePath(), 'src' => $fileName);
		
		if($fromTop){
			array_splice($this->jsFiles[$position], 0, 0, $item);
		}
		else{
			array_push($this->jsFiles[$position], $item);
		}
	}
	
	public function addPrimaryCssSmart($fileName, $fromTop = false) {
		$this->addCssAtPosSmart($fileName, static::PRIMARY, $fromTop);
	}
	
	public function addCssSmart($fileName, $fromTop = false) {
		$this->addCssAtPosSmart($fileName, static::SECONDARY, $fromTop);
	}
	
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
	
		$item = array('type'=> self::TYPE_CSS, 'path'=>$this->getCurrentPagePath(), 'src' => $fileName);
		
		if($fromTop){
			array_splice($this->cssFiles[$position], 0, 0, $item);
		}
		else{
			array_push($this->cssFiles[$position], $item);
		}
	}
	
	public function addCssAtPosSmart($fileName, $position = null, $fromTop = false){
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}
		if($position === null){
			$position = static::PRIMARY;
		}
		
		if(!isset($this->cssFiles[$position]) or !is_array($this->cssFiles[$position])){
			$this->cssFiles[$position] = array();
		}
		
		$item = array('type'=> self::TYPE_CSS_SMART, 'path'=>$this->getCurrentPagePath(), 'src' => $fileName);
		
		if($fromTop){
			array_splice($this->cssFiles[$position], 0, 0, $item);
		}
		else{
			array_push($this->cssFiles[$position], $item);
		}
	}
	
	public function addCustomJs($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
	
		$filePath = $this->getJsFilePath($fileName);
		array_push($this->customJsFiles, $filePath);
	}
	public function addCustomCss($fileName) {
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
	
		$filePath = $this->getCssFilePath($fileName);
		array_push($this->customCssFiles, $filePath);
	}
	
	public function addCustomJsSmart($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("JS filename is not specified");
		}
	
		$originalFilename = str_replace("/", "(slash)", $fileName);
	
		$filePath = Reg::get('rewriteURL')->glink("get/js/name:" . base64_encode($fileName) . "/path:" . base64_encode($this->getCurrentPagePath()) . "/originalName:$originalFilename");
	
		if($this->urlCounterForClearCache != null){
			$filePath .= "?cnt={$this->urlCounterForClearCache}";
		}
	
		array_push($this->customJsFiles, $filePath);
	}
	
	public function addCustomCssSmart($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("CSS filename is not specified");
		}
	
		$originalFilename = str_replace("/", "(slash)", $fileName);
	
		$filePath = Reg::get('rewriteURL')->glink("get/css/name:" . base64_encode($fileName) . "/path:" . base64_encode($this->getCurrentPagePath()) . "/originalName:$originalFilename");
	
		if($this->urlCounterForClearCache != null){
			$filePath .= "?cnt={$this->urlCounterForClearCache}";
		}
	
		array_push($this->customCssFiles, $filePath);
	}
	
	protected function getCurrentPagePath(){
		$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
		$pagePath = Reg::get('smarty')->pagesPath;
		for($i = 0; $i < count($levels); $i++){
			$level = $levels[$i];
			if(isset(Reg::get('nav')->$level) and !empty(Reg::get('nav')->$level)){
				$pagePath .= Reg::get('nav')->$level . '/';
			}
			if(isset($levels[$i+1]) and !is_dir($this->getFilePathFromTemplate($pagePath . Reg::get('nav')->$levels[$i+1], true))){
				break;
			}
		}
	
		return $pagePath;
	}
	
	protected function defaultAssingns(){
		parent::defaultAssingns();

		$this->assign ( '__jsFiles',  base64_url_encode(gzcompress(serialize($this->jsFiles))));
		$this->assign ( '__cssFiles', base64_url_encode(gzcompress(serialize($this->cssFiles ))));
		$this->assign ( '__customJsFiles',  $this->customJsFiles);
		$this->assign ( '__customCssFiles', $this->customCssFiles);
	}
	
	protected function handlePagerNotFound(){
		redirect(SITE_PATH);
	}
}
