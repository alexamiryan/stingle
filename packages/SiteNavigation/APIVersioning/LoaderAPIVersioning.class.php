<?php
class LoaderAPIVersioning extends Loader{
	protected function includes(){
		stingleInclude ('Managers/APIVersioning.class.php');
	}
    
    public function hookAPIUrlParse(){
        APIVersioning::parseApiVersioningURL($this->config->AuxConfig);
    }
}
