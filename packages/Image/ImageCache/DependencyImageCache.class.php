<?php
class DependencyImageCache extends Dependency
{
	public function __construct(){
		$this->addPlugin("Image", "Image");
	}
}
