<?php
class DependencySmartyMarkdown extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output", "Smarty");
	}
}
