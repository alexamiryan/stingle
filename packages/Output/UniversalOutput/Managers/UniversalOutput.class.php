<?php
/**
 * UniversalOutput will give output in HTML or JSON
 * Decision is made by looking on $_GET['ajax'] parameter.
 * If $_GET['ajax'] == 1 then ouput is given in JSON format.
 * Otherwise HTML is being outputed.
 */
class UniversalOutput extends Model{
	
	const STATUS_NOK = 'nok';
	const STATUS_OK = 'ok';
	
	const TYPE_HTML = 0;
	const TYPE_JSON = 1;
	const TYPE_BARE = 2;

	private $smarty = null;
	private $error = null;
	private $info = null;
	
	private $outputType = self::TYPE_HTML;
	
	private $jsFiles = array();
	private $jsSmartFiles = array();
	
	private $cssFiles = array();
	private $cssSmartFiles = array();
	
	private $parts = array();
	
	private $redirectUrl = null;
	private $redirectPageUrl = null;
	
	private $disableContentOutput = false;
	private $disableAutoMainPart = false;
	
	private $status = self::STATUS_OK;
	
	public function __construct() {
		$this->smarty = Reg::get(ConfigManager::getConfig('Output', 'Smarty')->Objects->Smarty);
		$this->error = Reg::get(ConfigManager::getConfig('Info', 'Info')->Objects->Error);
		$this->info = Reg::get(ConfigManager::getConfig('Info', 'Info')->Objects->Info);
	}
	
	public function setStatusOk(){
		$this->setStatus(self::STATUS_OK);
	}
	
	public function setStatusNotOk(){
		$this->setStatus(self::STATUS_NOK);
	}
	
	public function setStatus($status){
		if(!in_array($status, self::getConstsArray("STATUS"))){
			throw new InvalidArgumentException("Invalid \$status given");
		}
		$this->status = $status;
	}
	
	public function getStatus(){
		return $this->status;
	}
	
	public function addJs($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty");
		}
		array_push($this->jsFiles, $fileName);
	}
	
	public function addJsSmart($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty");
		}
		array_push($this->jsSmartFiles, $fileName);
	}
	
	public function addCss($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty");
		}
		array_push($this->cssFiles, $fileName);
	}
	
	public function addCssSmart($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty");
		}
		array_push($this->cssSmartFiles, $fileName);
	}
	
	public function set($partName, $partValue){
		if(empty($partName)){
			throw new InvalidArgumentException("\$partName is empty");
		}
		$this->parts[$partName] = $partValue;
	}
	
	public function assign($var, $value){
		$this->smarty->assign($var, $value);
	}
	
	public function setWrapper($wrapper){
		$this->smarty->setWrapper($wrapper);
	}
	
	public function redirect($url, $doGlink = false){
		if($doGlink){
			$rewriteUrl = Reg::get(ConfigManager::getConfig('RewriteURL', 'RewriteURL')->Objects->rewriteURL);
			$this->redirectUrl = $rewriteUrl->glink($url);
		}
		else{
			$this->redirectUrl = $url;
		}
		
		if($this->getOutputType() == self::TYPE_HTML){
			redirect($this->redirectUrl);
		}
	}
	
	public function redirectPage($url, $doGlink = false){
		if($doGlink){
			$rewriteUrl = Reg::get(ConfigManager::getConfig('RewriteURL', 'RewriteURL')->Objects->rewriteURL);
			$this->redirectPageUrl = $rewriteUrl->glink($url);
		}
		else{
			$this->redirectPageUrl = $url;
		}
	}
	
	public function disableContentOutput(){
		$this->disableContentOutput = true;
	}
	public function enableContentOutput(){
		$this->disableContentOutput = false;
	}
	
	public function disableAutoMainPart(){
		$this->disableAutoMainPart = true;
	}
	public function enableAutoMainPart(){
		$this->disableAutoMainPart = false;
	}
	
	public function setHTMLOutput(){
		$this->outputType = self::TYPE_HTML;
	}
	
	public function setJSONOutput(){
		$this->outputType = self::TYPE_JSON;
	}
	
	public function setBareOutput(){
		$this->outputType = self::TYPE_BARE;
	}
	
	public function getOutputType(){
		return $this->outputType;
	}
	
	public function output(){
		if($this->outputType == self::TYPE_JSON){
			$output = array();
			$output['parts'] = array();
			
			$output['status'] = $this->status;
			if($this->redirectUrl !== null){
				$output['redirect'] = $this->redirectUrl;
			}
			elseif($this->redirectPageUrl !== null){
				$output['redirectPage'] = $this->redirectPageUrl;
			}
			else{
				$output['infos'] = $this->info->getAll();
				$output['errors'] = $this->error->getAll();
			
				if(!$this->disableContentOutput){
					foreach($this->parts as $partName => $partValue){
						$output['parts'][$partName] = $partValue;
					}
					
					if(!isset($this->parts['main']) and !$this->disableAutoMainPart){
						try{
							$main = $this->smarty->output(true);
							if($main != null){
								$output['parts']['main'] = $main;
								$inlineJs = $this->smarty->getInlineJs();
								if(is_string($main) and !empty($inlineJs)){
									$output['parts']['main'] .= "\n" . $inlineJs;
								}
							}
						}
						catch(TemplateFileNotFoundException $e){
							$this->error->add($e->getMessage());
						}
					}
					elseif(!empty($this->parts['main']) and is_string($this->parts['main'])){
						$inlineJs = $this->smarty->getInlineJs();
						if(!empty($inlineJs)){
							$output['parts']['main'] .= "\n" . $inlineJs;
						}
					}
					$output['scripts'] = array();
					$output['scriptsSmart'] = array();
					$output['css'] = array();
					$output['cssSmart'] = array();
					foreach($this->jsFiles as $fileName){
						array_push($output['scripts'], $this->smarty->getJsPath($fileName));
					}
					foreach($this->jsSmartFiles as $fileName){
						array_push($output['scriptsSmart'], base64_encode($fileName));
					}
					foreach($this->cssFiles as $fileName){
						array_push($output['css'], $this->smarty->getCssPath($fileName));
					}
					foreach($this->cssSmartFiles as $fileName){
						array_push($output['cssSmart'], base64_encode($fileName));
					}
				}
			}
			JSON::jsonOutput($output);
		}
		elseif($this->outputType == self::TYPE_BARE or ($this->outputType == null && isset($_GET['ajaxm']) && $_GET['ajaxm'] == 1)){
			echo $this->smarty->output(true) . "\n" . $this->smarty->getInlineJs();
		}
		else{
			if($this->redirectUrl !== null){
				redirect($this->redirectUrl);
			}
			elseif($this->redirectPageUrl !== null){
				redirect($this->redirectPageUrl);
			}
			elseif(!$this->disableContentOutput){
				foreach($this->parts as $partName => $partValue){
					$this->smarty->assign($partName, $partValue);
				}
				
				foreach($this->jsFiles as $fileName){
					$this->smarty->addJs($fileName);
				}
				foreach($this->jsSmartFiles as $fileName){
					$this->smarty->addJsSmart($fileName);
				}
				foreach($this->cssFiles as $fileName){
					$this->smarty->addCss($fileName);
				}
				foreach($this->cssSmartFiles as $fileName){
					$this->smarty->addCssSmart($fileName);
				}
				
				$this->smarty->output();
			}
		}
	}
}
