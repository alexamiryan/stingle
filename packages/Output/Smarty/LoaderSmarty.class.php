<?php

class LoaderSmarty extends Loader {

	private $pluginsDirs = array();

	protected function includes() {
		$precompileCode = 'define("SMARTY_DIR", "' . dirname(__FILE__) . '/Core/' . '");';

		stingleInclude('Core/Smarty.class.php', $precompileCode);
		stingleInclude('Helpers/functions.inc.php');
		stingleInclude('Managers/SmartyWrapper.class.php');
		stingleInclude('Exceptions/TemplateFileNotFoundException.class.php');
		stingleInclude('Exceptions/ImageFileNotFoundException.class.php');
	}

	protected function loadSmarty() {
		$this->register(new SmartyWrapper());
	}

	public function hookSmartyInit() {
		$siteNavigationConfig = ConfigManager::getConfig("SiteNavigation");
		Reg::get($this->config->Objects->Smarty)->initialize($this->config->AuxConfig);
	}

	public function hookMainOutput() {
		Reg::get($this->config->Objects->Smarty)->output();
	}

	public function hookCollectSmartyPluginsDir(Array $params) {
		extract($params);

		if (is_dir(STINGLE_PATH . "packages/{$packageName}/") and is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}")) {
			if (is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/SmartyPlugins")) {
				array_push($this->pluginsDirs, STINGLE_PATH . "packages/{$packageName}/{$pluginName}/SmartyPlugins");
			}
		}

		if (is_dir(SITE_PACKAGES_PATH . "{$packageName}/") and is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}")) {
			if (is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/SmartyPlugins")) {
				array_push($this->pluginsDirs, SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/SmartyPlugins");
			}
		}
	}

	public function hookRegisterSmartyPlugins() {
		foreach ($this->pluginsDirs as $dir) {
			Reg::get($this->config->Objects->Smarty)->addPluginsDir($dir);
		}
	}

}
