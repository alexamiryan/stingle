<?php
class DependencyConversations1on1 extends Dependency
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
