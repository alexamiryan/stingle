<?php
class DependencyImageManager extends Dependency
{
	public function __construct(){
		$this->addPlugin("File", "S3Transport");
		$this->addPlugin("Image", "Image");
		$this->addPlugin("Image", "ImageModificator");
		$this->addPlugin("Crypto", "Crypto");
	}
}
