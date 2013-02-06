<?php
class DependencyImageUploader extends Dependency
{
	public function __construct(){
		$this->addPlugin("Image", "Image");
		$this->addPlugin("Crypto", "Crypto");
	}
}
