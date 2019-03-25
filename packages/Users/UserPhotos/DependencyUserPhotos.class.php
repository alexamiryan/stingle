<?php
class DependencyUserPhotos extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Db", "Memcache");
		$this->addPlugin("Image", "ImageManager");
		$this->addPlugin("Image", "ImageModificator");
	}
}
