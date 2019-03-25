<?php
class DependencyConversations extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Users", "Users");
		$this->addPlugin("File", "FileUploader");
		$this->addPlugin("Image", "ImageUploader");
		$this->addPlugin("Comet", "Comet");
	}
}
