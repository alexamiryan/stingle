<?php
class DependencyImageModificator extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Image", "Image");
	}
}
