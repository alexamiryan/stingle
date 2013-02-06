<?php
class DependencyUserPhotos extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Image", "ImageUploader");
	}
}
