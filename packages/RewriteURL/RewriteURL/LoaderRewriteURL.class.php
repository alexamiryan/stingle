<?php
class LoaderRewriteURL extends Loader{
	protected function includes(){
		require_once ('RewriteURL.class.php');
		require_once ('Helpers/helpers.inc.php');
	}
	
	protected function loadrewriteURL(){
		$this->rewriteURL =  new RewriteURL($this->config->AuxConfig);
		$this->register($this->rewriteURL);
	}
	
	public function hookParseURL(){
		// Parse URL rewriting
		if(!defined('IS_CGI')){
			Reg::get($this->config->Objects->rewriteURL)->parseURL();
		}
	}
}
