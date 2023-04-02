<?php
class LoaderAPIVersioning extends Loader{
	protected function includes(){
		stingleInclude ('Managers/APIVersioning.class.php');
	}
    
    public function hookAPIUrlParse(){
        ConfigManager::addConfig(['SiteNavigation', 'SiteNavigation', 'AuxConfig'], 'applyDefaultValueFromLevel', 1);
        APIVersioning::parseApiVersioningURL($this->config->AuxConfig);
    }
}
